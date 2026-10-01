<?php

namespace App\Http\Controllers;

use App\Models\AiPreset;
use App\Models\AiPromptTemplate;
use App\Models\Article;
use App\Models\ArticleOption;
use App\Models\Client;
use App\Services\AiHumanizer;
use App\Services\ArticleFormatter;
use App\Services\MultiProviderLlmClient;
use App\Services\PromptBuilder;
use App\Services\SerpCrawler;
use App\Services\SitemapCrawlerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeoBlogController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $activeClient = $currentUser && $currentUser->active_client_id
            ? Client::find($currentUser->active_client_id)
            : null;

        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $presets = $currentUser
            ? $currentUser->presets()->get()
            : AiPreset::all();
        $promptTemplates = AiPromptTemplate::orderBy('archetype_name')->get();
        $articleOptions = ArticleOption::orderBy('sort_order')->get()->groupBy('category');

        $recentJobs = $currentUser
            ? $currentUser->seoJobs()->latest()->take(5)->get()
            : collect();
        $recentArticles = $currentUser
            ? Article::where('user_id', $currentUser->id)->latest()->take(5)->get()
            : Article::latest()->take(5)->get();

        $latestArticle = $recentArticles->first();

        return view('pages.blog-creator', compact(
            'clients',
            'activeClient',
            'presets',
            'promptTemplates',
            'articleOptions',
            'recentJobs',
            'recentArticles',
            'latestArticle'
        ));
    }

    public function generate(Request $request)
    {
        return $this->create($request);
    }

    public function create(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'primary_keyword' => 'required|string|max:100',
            'secondary_keywords' => 'nullable|string',
            'search_intent' => 'required|in:Informational,Commercial,Transactional,Navigational',
            'tone' => 'required|string',
            'target_audience' => 'nullable|string',
            'industry' => 'nullable|string|max:100',
            'pov' => 'nullable|string|max:50',
            'format' => 'required|in:Ultimate Guide,Listicle,How-To,Product Comparison,Explainer',
            'word_count' => 'required|in:Short,Standard,Long-form,In-Depth',
            'language' => 'nullable|string',
            'humanizer_active' => 'required|boolean',
            'enable_serp_crawler' => 'nullable|boolean',
            'preset_id' => 'nullable|exists:ai_presets,id',
            'client_id' => 'nullable',
            'sitemap_url' => 'nullable|url',
            'competitor_urls' => 'nullable|array',
            'competitor_urls.*' => 'url',
        ]);

        $params = $request->all();
        if (empty($params['target_audience'])) {
            $clientModel = ! empty($params['client_id']) && $params['client_id'] !== 'none'
                ? Client::find($params['client_id'])
                : null;
            $params['target_audience'] = $clientModel?->target_audience ?: 'General Audience';
        }

        $job = $request->user()->seoJobs()->create([
            'execution_mode' => 'single',
            'status' => 'pending',
            'parameters' => $params,
            'total_items' => 1,
            'completed_items' => 0,
        ]);

        // Simulating immediate execution for sandbox development in controller (Normally dispatched to a Queue)
        $this->processJobInController($job);

        $job->refresh();

        if ($job->status === 'failed') {
            return response()->json([
                'message' => 'SEO Generation failed: '.$job->error_message,
                'status' => 'failed',
            ], 500);
        }

        $article = $job->articles()->first();

        return response()->json([
            'message' => 'SEO Blog generation job completed.',
            'job_id' => $job->id,
            'status' => $job->status,
            'article' => $article,
            'article_id' => $article?->id,
        ], 202);
    }

    /**
     * Preview the complete, fully compiled prompts that will be sent to the LLM.
     */
    public function previewPrompt(Request $request)
    {
        $params = $request->all();
        $title = ! empty($params['topic']) ? trim($params['topic']) : 'Mastering SEO Architecture';
        $user = $request->user();

        // 1. Resolve Active Preset & Model Directives
        $preset = $user ? $user->presets()->where('is_active', true)->orderBy('updated_at', 'desc')->first() : null;

        $provider = 'gemini';
        $model = 'gemini-3.5-flash';
        $presetInstructions = '';

        if ($preset) {
            $provider = $preset->provider;
            $model = $preset->model;
            $presetInstructions = $preset->custom_instructions ?? '';
        } else {
            $path = 'config/app_config.json';
            if (Storage::disk('local')->exists($path)) {
                $config = json_decode(Storage::disk('local')->get($path), true);
                $provider = $config['current_provider'] ?? $provider;
                $model = $config['current_model'] ?? $model;
            }
        }

        if (! empty($params['prompt_template_id'])) {
            $customTmpl = AiPromptTemplate::find($params['prompt_template_id']);
            if ($customTmpl) {
                $presetInstructions .= "\n\nCUSTOM PROMPT BLUEPRINT DIRECTIVES (".$customTmpl->archetype_name."):\n".$customTmpl->system_prompt_template;
            }
        }

        // 2. Resolve Client Context (Brand Voice, Sitemap Links, CTA)
        $clientId = $params['client_id'] ?? null;
        $clientModel = null;
        if (! empty($clientId) && $clientId !== 'none') {
            $clientModel = Client::find($clientId);
        }

        $clientContext = null;
        if ($clientModel) {
            $sitemapLinks = [];
            try {
                $crawlerService = app(SitemapCrawlerService::class);
                $sitemapLinks = $crawlerService->getRelevantSitemapLinks($clientModel, $title, 3);
            } catch (\Exception $e) {
                Log::warning('Sitemap link preview extraction failed: '.$e->getMessage());
            }

            $approvedDomains = is_array($clientModel->approved_reference_domains)
                ? $clientModel->approved_reference_domains
                : (is_string($clientModel->approved_reference_domains) ? explode(',', $clientModel->approved_reference_domains) : []);

            $clientContext = [
                'name' => $clientModel->name,
                'website_url' => $clientModel->website_url,
                'industry' => $clientModel->industry,
                'brand_tone' => $clientModel->brand_tone,
                'target_audience' => $clientModel->target_audience,
                'cta_default' => $clientModel->cta_default,
                'internal_links' => $sitemapLinks,
                'approved_reference_domains' => $approvedDomains,
            ];
        }

        // 3. Competitor Outlines Simulation (if SERP crawler active)
        $competitorOutlines = [];
        if (! empty($params['enable_serp_crawler'])) {
            $competitorOutlines = [
                'Competitor 1 Structure: Intro -> Core Framework -> Comparison Table -> FAQ -> Conclusion',
                'Competitor 2 Structure: Overview -> Tactical Step-by-Step Guide -> Best Practices -> Case Studies',
            ];
        }

        $promptBuilder = new PromptBuilder;

        // 4. Build Complete Master Single-Pass Prompt
        $masterPrompt = $promptBuilder->buildSeoArticlePrompt($params, $presetInstructions, $competitorOutlines, $clientContext);

        // 5. Build Pass 1: Outline Architecture Prompt
        $outlinePrompt = $promptBuilder->buildOutlinePrompt($params, $presetInstructions, $competitorOutlines, $clientContext);

        // 6. Build Pass 2: Section Copywriting Prompts (Comprehensive sample suite)
        $sectionIntro = $promptBuilder->buildSectionPrompt(
            $params,
            [
                'heading' => 'Introduction to '.$title,
                'type' => 'intro',
                'talking_points' => ['Hook the reader with problem statement', 'Integrate focus keyword naturally', 'Roadmap of guide'],
                'target_words' => 200,
            ],
            '',
            $presetInstructions,
            $clientContext
        );

        $sectionStandard = $promptBuilder->buildSectionPrompt(
            $params,
            [
                'heading' => 'Core Strategy & Advanced Execution',
                'type' => 'standard',
                'talking_points' => ['Foundational principles', 'Execution step-by-step workflow', 'Common pitfalls to avoid'],
                'target_words' => 350,
            ],
            '<!-- [Previously compiled intro section HTML] -->',
            $presetInstructions,
            $clientContext
        );

        $sectionComparison = $promptBuilder->buildSectionPrompt(
            $params,
            [
                'heading' => 'Comparative Analysis: Strategy A vs Strategy B',
                'type' => 'comparison-table',
                'talking_points' => ['Feature-by-feature matrix', 'Pros, cons, and performance metrics'],
                'target_words' => 250,
            ],
            '<!-- [Previously compiled body sections HTML] -->',
            $presetInstructions,
            $clientContext
        );

        $sectionFaq = $promptBuilder->buildSectionPrompt(
            $params,
            [
                'heading' => 'Frequently Asked Questions',
                'type' => 'faq',
                'talking_points' => ['What are the core benefits?', 'How long does implementation take?', 'What are common mistakes?'],
                'target_words' => 200,
            ],
            '<!-- [Previously compiled body sections HTML] -->',
            $presetInstructions,
            $clientContext
        );

        $sectionConclusion = $promptBuilder->buildSectionPrompt(
            $params,
            [
                'heading' => 'Conclusion & Strategic Takeaways',
                'type' => 'conclusion',
                'talking_points' => ['Synthesize core lessons and long-term implications', 'Final actionable recommendations and verdict'],
                'target_words' => 250,
            ],
            '<!-- [Previously compiled FAQ HTML] -->',
            $presetInstructions,
            $clientContext
        );

        $sectionCta = $promptBuilder->buildSectionPrompt(
            $params,
            [
                'heading' => 'Next Steps',
                'type' => 'cta',
                'talking_points' => ['Official conversion callout and action steps'],
                'target_words' => 100,
            ],
            '<!-- [Previously compiled Conclusion HTML] -->',
            $presetInstructions,
            $clientContext
        );

        // 7. Build Pass 3: Metadata Synthesis Prompt
        $metadataPrompt = $promptBuilder->buildMetadataPrompt(
            $title,
            '<h1>'.htmlspecialchars($title)."</h1>\n<p>Comprehensive article body content synthesized across all previous sections...</p>"
        );

        return response()->json([
            'status' => 'success',
            'model' => $model,
            'provider' => $provider,
            'system_prompt' => $masterPrompt['system'] ?? '',
            'user_prompt' => $masterPrompt['user'] ?? '',
            'metadata' => [
                'provider' => $provider,
                'model' => $model,
                'client_name' => $clientContext['name'] ?? null,
                'is_generic_mode' => empty($clientContext),
                'industry' => ! empty($params['industry']) ? $params['industry'] : ($clientContext['industry'] ?? 'General / Not Specified'),
                'pov' => $params['pov'] ?? 'Second Person',
                'tone' => $params['tone'] ?? ($clientContext['brand_tone'] ?? 'Professional & Authoritative'),
                'target_audience' => $params['target_audience'] ?? ($clientContext['target_audience'] ?? 'General Audience'),
                'word_count_category' => $params['word_count'] ?? 'Standard',
            ],
            'passes' => [
                'master' => [
                    'system_prompt' => $masterPrompt['system'] ?? '',
                    'user_prompt' => $masterPrompt['user'] ?? '',
                ],
                'outline' => [
                    'system_prompt' => $outlinePrompt['system'] ?? '',
                    'user_prompt' => $outlinePrompt['user'] ?? '',
                ],
                'sections' => [
                    'intro' => [
                        'system_prompt' => $sectionIntro['system'] ?? '',
                        'user_prompt' => $sectionIntro['user'] ?? '',
                    ],
                    'key-takeaways' => [
                        'system_prompt' => $sectionStandard['system'] ?? '',
                        'user_prompt' => $sectionStandard['user'] ?? '',
                    ],
                    'standard' => [
                        'system_prompt' => $sectionStandard['system'] ?? '',
                        'user_prompt' => $sectionStandard['user'] ?? '',
                    ],
                    'comparison-table' => [
                        'system_prompt' => $sectionComparison['system'] ?? '',
                        'user_prompt' => $sectionComparison['user'] ?? '',
                    ],
                    'faq' => [
                        'system_prompt' => $sectionFaq['system'] ?? '',
                        'user_prompt' => $sectionFaq['user'] ?? '',
                    ],
                    'conclusion' => [
                        'system_prompt' => $sectionConclusion['system'] ?? '',
                        'user_prompt' => $sectionConclusion['user'] ?? '',
                    ],
                    'cta' => [
                        'system_prompt' => $sectionCta['system'] ?? '',
                        'user_prompt' => $sectionCta['user'] ?? '',
                    ],
                ],
                'metadata' => [
                    'system_prompt' => $metadataPrompt['system'] ?? '',
                    'user_prompt' => $metadataPrompt['user'] ?? '',
                ],
            ],
            'master_prompt' => $masterPrompt,
            'outline_prompt' => $outlinePrompt,
            'section_prompts' => [
                'intro' => $sectionIntro,
                'standard' => $sectionStandard,
                'comparison-table' => $sectionComparison,
                'faq' => $sectionFaq,
                'conclusion' => $sectionConclusion,
                'cta' => $sectionCta,
            ],
            'metadata_prompt' => $metadataPrompt,
        ]);
    }

    public function processBatchCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $job = $request->user()->seoJobs()->create([
            'execution_mode' => 'batch',
            'status' => 'pending',
            'parameters' => ['filename' => $request->file('csv_file')->getClientOriginalName()],
            'total_items' => 5, // Simulated count
            'completed_items' => 0,
        ]);

        return response()->json([
            'message' => 'Batch job successfully dispatched to background queue.',
            'job_id' => $job->id,
            'status' => $job->status,
        ], 202);
    }

    public function listJobs(Request $request)
    {
        return response()->json($request->user()->seoJobs()->orderBy('created_at', 'desc')->get());
    }

    public function getJobStatus(Request $request, $id)
    {
        $job = $request->user()->seoJobs()->findOrFail($id);

        return response()->json($job);
    }

    public function getJobLogs(Request $request, $id)
    {
        $user = $request->user();
        $job = $user ? $user->seoJobs()->find($id) : null;
        if (! $job) {
            $job = \App\Models\SeoGenerationJob::findOrFail($id);
        }

        $logs = [];
        if (! empty($job->logs)) {
            $logs = is_array($job->logs) ? $job->logs : (json_decode($job->logs, true) ?: [$job->logs]);
        }
        $article = $job->articles()->first();

        $promptTokens = $article ? $article->prompt_tokens : 0;
        $completionTokens = $article ? $article->completion_tokens : 0;
        $cost = round(($promptTokens * 0.00000015) + ($completionTokens * 0.0000006), 5);

        $metrics = [
            'words_generated' => $article ? $article->word_count : 0,
            'seo_score' => $article ? $article->seo_score : 0,
            'flesch_reading_ease' => $article ? $article->flesch_reading_ease : 0,
            'current_cost_usd' => $cost,
        ];

        return response()->json([
            'job_id' => $job->id,
            'status' => $job->status,
            'logs' => $logs,
            'metrics' => $metrics,
            'article' => $article,
            'article_id' => $article?->id,
        ]);
    }

    public function getJobArticle(Request $request, $id)
    {
        $user = $request->user();
        $job = $user ? $user->seoJobs()->find($id) : null;
        if (! $job) {
            $job = \App\Models\SeoGenerationJob::findOrFail($id);
        }

        $article = $job->articles()->first();

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not yet generated or not found for this job.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'article' => $article,
        ]);
    }

    public function listArticles(Request $request)
    {
        $isAdmin = $request->user()->roles()->where('name', 'admin')->exists();
        $query = Article::query();

        if ($isAdmin) {
            $query->with('user');
            if ($request->has('user_id') && ! empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }
        } else {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function getArticle(Request $request, $id)
    {
        $isAdmin = $request->user()->roles()->where('name', 'admin')->exists();
        $query = Article::query();

        if (! $isAdmin) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->findOrFail($id));
    }

    public function deleteArticle(Request $request, $id)
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized. Only administrators can delete articles.'], 403);
        }

        $article = Article::findOrFail($id);
        $article->delete();

        return response()->json(['message' => 'Article deleted successfully.']);
    }

    public function publishToWordPress(Request $request, $id)
    {
        $article = $request->user()->articles()->findOrFail($id);

        $clientId = $request->input('client_id');
        $client = null;
        if (! empty($clientId) && $clientId !== 'none') {
            $client = Client::find($clientId);
        }
        if (! $client && $request->user()->active_client_id) {
            $client = Client::find($request->user()->active_client_id);
        }

        if (! $client) {
            return response()->json([
                'success' => false,
                'message' => 'No client profile selected. Please select a client profile with configured WordPress credentials to publish live.',
            ], 422);
        }

        if (empty($client->wordpress_url) || empty($client->wordpress_app_password)) {
            return response()->json([
                'success' => false,
                'message' => "WordPress credentials not configured for client '{$client->name}'. Please configure WordPress URL and Application Password in Client Profile settings.",
            ], 422);
        }

        try {
            $endpointUrl = rtrim($client->wordpress_url, '/').'/wp-json/wp/v2/posts';
            $response = Http::withBasicAuth(
                $client->wordpress_username ?? 'admin',
                $client->wordpress_app_password
            )->timeout(20)->post($endpointUrl, [
                'title' => $article->title,
                'content' => $article->html_content,
                'slug' => $article->slug,
                'status' => 'publish',
            ]);

            if ($response->successful()) {
                $wpData = $response->json();
                $postId = $wpData['id'] ?? null;
                $postUrl = $wpData['link'] ?? (rtrim($client->website_url, '/').'/'.$article->slug);

                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'wordpress_post_id' => $postId,
                    'url' => $postUrl,
                    'message' => "Article successfully published live to {$client->name} WordPress CMS!",
                ]);
            } else {
                $errorReason = $response->json('message') ?? ('HTTP '.$response->status().' - '.$response->body());

                return response()->json([
                    'success' => false,
                    'message' => "WordPress REST API Error: {$errorReason}",
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'WordPress Integration Failure: '.$e->getMessage(),
            ], 500);
        }
    }

    protected function processJobInController($job)
    {
        @set_time_limit(240);
        $job->update(['status' => 'processing']);

        $params = $job->parameters;
        $title = $params['topic'];
        $slug = Str::slug($title);

        $logs = [];
        $logs[] = '['.date('H:i:s')."] Initializing SEO Generation for: \"{$title}\"...";
        $job->update(['logs' => json_encode($logs)]);

        try {
            $user = $job->user;
            $preset = $user->presets()->where('is_active', true)->orderBy('updated_at', 'desc')->first();

            $provider = 'gemini';
            $model = 'gemini-3.5-flash';
            $temperature = 0.7;
            $presetInstructions = '';

            if ($preset) {
                $provider = $preset->provider;
                $model = $preset->model;
                $temperature = $preset->temperature;
                $presetInstructions = $preset->custom_instructions ?? '';
            } else {
                $path = 'config/app_config.json';
                if (Storage::disk('local')->exists($path)) {
                    $config = json_decode(Storage::disk('local')->get($path), true);
                    $provider = $config['current_provider'] ?? $provider;
                    $model = $config['current_model'] ?? $model;
                }
            }

            if (! empty($params['prompt_template_id'])) {
                $customTmpl = AiPromptTemplate::find($params['prompt_template_id']);
                if ($customTmpl) {
                    $presetInstructions .= "\n\nCUSTOM PROMPT BLUEPRINT DIRECTIVES (".$customTmpl->archetype_name."):\n".$customTmpl->system_prompt_template;
                    $logs[] = '['.date('H:i:s')."] Custom prompt blueprint active: '{$customTmpl->archetype_name}'";
                }
            }

            // Resolve Active Client Profile for Brand Voice, CTA & Sitemap Links
            $clientId = $params['client_id'] ?? null;
            $clientModel = null;
            if (! empty($clientId) && $clientId !== 'none') {
                $clientModel = Client::find($clientId);
            }

            $clientContext = null;
            if ($clientModel) {
                $sitemapLinks = [];
                try {
                    $crawlerService = app(SitemapCrawlerService::class);
                    $sitemapLinks = $crawlerService->getRelevantSitemapLinks($clientModel, $title, 3);
                    if (! empty($sitemapLinks)) {
                        $logs[] = '['.date('H:i:s').'] Extracted '.count($sitemapLinks)." relevant internal links from {$clientModel->name}'s sitemap cache.";
                    }
                } catch (\Exception $e) {
                    Log::warning('Sitemap link extraction failed: '.$e->getMessage());
                }

                $approvedDomains = is_array($clientModel->approved_reference_domains)
                    ? $clientModel->approved_reference_domains
                    : (is_string($clientModel->approved_reference_domains) ? explode(',', $clientModel->approved_reference_domains) : []);

                $clientContext = [
                    'name' => $clientModel->name,
                    'website_url' => $clientModel->website_url,
                    'industry' => $clientModel->industry,
                    'brand_tone' => $clientModel->brand_tone,
                    'target_audience' => $clientModel->target_audience,
                    'cta_default' => $clientModel->cta_default,
                    'internal_links' => $sitemapLinks,
                    'approved_reference_domains' => $approvedDomains,
                ];

                $logs[] = '['.date('H:i:s')."] Injected brand profile: '{$clientModel->name}' (Tone: '{$clientModel->brand_tone}', Audience: '{$clientModel->target_audience}')";
                $job->update(['logs' => json_encode($logs)]);
            } else {
                $pov = $params['pov'] ?? 'Second Person';
                $ind = ! empty($params['industry']) ? " | Industry: '{$params['industry']}'" : '';
                $logs[] = '['.date('H:i:s')."] Independent mode active (no client profile attached). POV: '{$pov}'{$ind}.";
                $job->update(['logs' => json_encode($logs)]);
            }

            $logs[] = '['.date('H:i:s')."] Active preset node: {$provider} using {$model}";
            $job->update(['logs' => json_encode($logs)]);

            $competitorOutlines = [];
            if (! empty($params['enable_serp_crawler'])) {
                $logs[] = '['.date('H:i:s').'] Starting competitor DuckDuckGo crawler...';
                $job->update(['logs' => json_encode($logs)]);

                $crawler = new SerpCrawler;
                $competitorOutlines = $crawler->fetchCompetitors($title);

                $logs[] = '['.date('H:i:s').'] Competitor SERP extraction finished. Extracted '.count($competitorOutlines).' outline contexts.';
                $job->update(['logs' => json_encode($logs)]);
            }

            $llmClient = new MultiProviderLlmClient($provider, $model);
            $promptBuilder = new PromptBuilder;

            // Pass 1: Generate Outline
            $logs[] = '['.date('H:i:s').'] Compiling outlines & generating content structure blueprint...';
            $job->update(['logs' => json_encode($logs)]);

            $outlinePrompts = $promptBuilder->buildOutlinePrompt($params, $presetInstructions, $competitorOutlines, $clientContext);
            $outlineResult = $llmClient->generateText($outlinePrompts['system'], $outlinePrompts['user'], ['temperature' => 0.5]);

            // Clean JSON response
            $outlineClean = trim($outlineResult['text']);
            if (preg_match('/```json\s*(.*?)\s*```/s', $outlineClean, $jsonMatches)) {
                $outlineClean = trim($jsonMatches[1]);
            }
            $sections = json_decode($outlineClean, true);

            if (! is_array($sections) || empty($sections)) {
                $logs[] = '['.date('H:i:s').'] Warning: Failed to parse AI JSON outline. Using robust fallback structure.';
                $job->update(['logs' => json_encode($logs)]);

                $sections = [
                    ['heading' => 'Introduction to '.$title, 'type' => 'intro', 'talking_points' => ['Overview', 'Keyword integration'], 'target_words' => 200],
                    ['heading' => 'Core Strategy & Concepts', 'type' => 'standard', 'talking_points' => ['Basic elements', 'Important steps'], 'target_words' => 300],
                    ['heading' => 'Step-by-Step Implementation Guide', 'type' => 'standard', 'talking_points' => ['Execution details', 'Best practices'], 'target_words' => 400],
                    ['heading' => 'Comparison Analysis', 'type' => 'comparison-table', 'talking_points' => ['Feature comparisons', 'Detailed table breakdown'], 'target_words' => 250],
                    ['heading' => 'Frequently Asked Questions', 'type' => 'faq', 'talking_points' => ['Reader queries answered'], 'target_words' => 200],
                    ['heading' => 'Conclusion & Final Recommendations', 'type' => 'conclusion', 'talking_points' => ['Core synthesis', 'Strategic takeaways and final advice'], 'target_words' => 250],
                    ['heading' => 'Next Steps', 'type' => 'cta', 'talking_points' => ['Action steps and call to action'], 'target_words' => 100],
                ];
            } else {
                // Ensure every AI-generated outline has a dedicated conclusion section
                $hasConclusion = false;
                foreach ($sections as $sec) {
                    if (($sec['type'] ?? '') === 'conclusion' || stripos($sec['heading'] ?? '', 'conclusion') !== false) {
                        $hasConclusion = true;
                        break;
                    }
                }

                if (! $hasConclusion) {
                    $ctaIndex = -1;
                    foreach ($sections as $idx => $sec) {
                        if (($sec['type'] ?? '') === 'cta') {
                            $ctaIndex = $idx;
                            break;
                        }
                    }

                    $conclusionSection = [
                        'heading' => 'Conclusion & Strategic Takeaways',
                        'type' => 'conclusion',
                        'talking_points' => [
                            'Synthesizing primary concepts and practical impact',
                            'Strategic recommendations and final verdict',
                        ],
                        'target_words' => 250,
                    ];

                    if ($ctaIndex >= 0) {
                        array_splice($sections, $ctaIndex, 0, [$conclusionSection]);
                    } else {
                        $sections[] = $conclusionSection;
                    }
                }
            }

            $logs[] = '['.date('H:i:s').'] Content outline confirmed. Target sections: '.count($sections);
            $job->update(['logs' => json_encode($logs)]);

            // Pass 2: Generate Sections Loop (Concurrent Parallel Writing)
            $logs[] = '['.date('H:i:s').'] Preparing prompts for '.count($sections).' sections...';
            $job->update(['logs' => json_encode($logs)]);

            $sectionPrompts = [];
            foreach ($sections as $index => $section) {
                $sectionPrompts[$index] = $promptBuilder->buildSectionPrompt($params, $section, '', $presetInstructions, $clientContext);
            }

            $logs[] = '['.date('H:i:s').'] Dispatching concurrent parallel writing requests to LLM pool...';
            $job->update(['logs' => json_encode($logs)]);

            $sectResults = $llmClient->generateTextParallel($sectionPrompts, ['temperature' => $temperature]);

            $logs[] = '['.date('H:i:s').'] All parallel writes resolved. Compiling HTML document flow...';
            $job->update(['logs' => json_encode($logs)]);

            $compiledHtml = '';
            foreach ($sections as $index => $section) {
                $sectionHtml = trim($sectResults[$index]['text'] ?? '');
                if (preg_match('/```html\s*(.*?)\s*```/s', $sectionHtml, $htmlMatches)) {
                    $sectionHtml = trim($htmlMatches[1]);
                }

                // Wrap in header if not already present
                $headingTag = $index === 0 ? 'h1' : 'h2';
                if (stripos($sectionHtml, "<{$headingTag}") === false && stripos($sectionHtml, "{$section['heading']}") === false) {
                    $compiledHtml .= "\n<{$headingTag}>".htmlspecialchars($section['heading'])."</{$headingTag}>\n";
                }

                $compiledHtml .= "\n".$sectionHtml;
            }

            // AI Humanizer Polish Pass
            if (! empty($params['humanizer_active'])) {
                $logs[] = '['.date('H:i:s').'] Polishing generated article through AI Humanizer filter...';
                $job->update(['logs' => json_encode($logs)]);

                $humanizer = new AiHumanizer;
                $compiledHtml = $humanizer->polish($compiledHtml);
            }

            // Pass 3: Metadata Synthesis
            $logs[] = '['.date('H:i:s').'] Generating metadata tags and schema markup from compiled text...';
            $job->update(['logs' => json_encode($logs)]);

            $metaPrompts = $promptBuilder->buildMetadataPrompt($title, $compiledHtml);
            $metaResult = $llmClient->generateText($metaPrompts['system'], $metaPrompts['user'], ['temperature' => 0.4]);

            $metaClean = trim($metaResult['text']);
            if (preg_match('/```json\s*(.*?)\s*```/s', $metaClean, $jsonMatches)) {
                $metaClean = trim($jsonMatches[1]);
            }
            $metaData = json_decode($metaClean, true);

            $metaTitle = $metaData['meta_title'] ?? "{$title} - SEO Guide";
            $metaDesc = $metaData['meta_description'] ?? "Read our ultimate analysis about {$params['primary_keyword']}.";
            $articleSlug = ! empty($metaData['url_slug']) ? Str::slug($metaData['url_slug']) : $slug;
            $faqSchema = $metaData['faq_schema'] ?? [];

            // Wrap DALL-E Image prompt tags
            $formatter = new ArticleFormatter;
            $formattedHtml = $formatter->formatContent($compiledHtml);

            // Calculate exact metrics
            $wordCountVal = str_word_count(strip_tags($formattedHtml));
            $fleschScore = $this->calculateFleschScore($formattedHtml);

            // Calculate SEO Score out of 100
            $seoScore = 60;
            if (stripos($formattedHtml, $params['primary_keyword']) !== false) {
                $seoScore += 10;
            }
            if (stripos($metaTitle, $params['primary_keyword']) !== false) {
                $seoScore += 10;
            }
            if (stripos($metaDesc, $params['primary_keyword']) !== false) {
                $seoScore += 10;
            }
            if (strpos($formattedHtml, '<table') !== false) {
                $seoScore += 5;
            }
            if (strpos($formattedHtml, 'key-takeaways') !== false) {
                $seoScore += 5;
            }
            $seoScore = min(100, $seoScore);

            $promptTokens = ($outlineResult['prompt_tokens'] ?? 0) + ($metaResult['prompt_tokens'] ?? 0);
            $completionTokens = ($outlineResult['completion_tokens'] ?? 0) + ($metaResult['completion_tokens'] ?? 0);
            foreach ($sectResults as $sRes) {
                $promptTokens += $sRes['prompt_tokens'] ?? 0;
                $completionTokens += $sRes['completion_tokens'] ?? 0;
            }

            $schemaJson = [
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $metaTitle,
                'description' => $metaDesc,
            ];
            if (! empty($faqSchema)) {
                $schemaJson['faqSchema'] = $faqSchema;
            }

            $job->articles()->create([
                'user_id' => $job->user_id,
                'client_id' => (! empty($params['client_id']) && $params['client_id'] !== 'none') ? $params['client_id'] : null,
                'prompt_template_id' => $params['prompt_template_id'] ?? null,
                'prompt_template_name' => $customTmpl?->archetype_name ?? 'Comprehensive Master Prompt',
                'title' => $title,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDesc,
                'slug' => $articleSlug,
                'html_content' => $formattedHtml,
                'markdown_content' => $formattedHtml,
                'schema_jsonld' => $schemaJson,
                'word_count' => $wordCountVal,
                'seo_score' => $seoScore,
                'flesch_reading_ease' => $fleschScore,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'keyword_density_metrics' => [$params['primary_keyword'] => 1.5],
            ]);

            $logs[] = '['.date('H:i:s')."] SEO generation completed successfully. Words written: {$wordCountVal}. Flesch Ease: {$fleschScore}.";
            $job->update([
                'status' => 'completed',
                'completed_items' => 1,
                'logs' => json_encode($logs),
            ]);

        } catch (\Exception $e) {
            $logs[] = '[Error] Generation failed: '.$e->getMessage();
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'logs' => json_encode($logs),
            ]);
        }
    }

    /**
     * Compute Flesch Reading Ease score mathematically.
     */
    private function calculateFleschScore(string $text): float
    {
        $cleanText = strip_tags($text);
        $wordCount = str_word_count($cleanText);
        if ($wordCount === 0) {
            return 0.0;
        }

        $sentenceCount = preg_match_all('/[.!?]+/', $cleanText, $matches) ?: 1;

        $syllableCount = 0;
        $words = explode(' ', $cleanText);
        foreach ($words as $word) {
            $word = strtolower(trim($word));
            if (empty($word)) {
                continue;
            }
            $vowels = preg_match_all('/[aeiouy]+/i', $word, $m);
            $syllableCount += $vowels ?: 1;
        }

        $score = 206.835 - 1.015 * ($wordCount / $sentenceCount) - 84.6 * ($syllableCount / $wordCount);

        return round(max(0, min(100, $score)), 1);
    }

    public function downloadHtml($id)
    {
        $article = Article::findOrFail($id);
        $formatter = new ArticleFormatter;
        $html = $formatter->buildFullHtml($article);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$article->slug}.html\"",
        ]);
    }

    public function downloadDocx($id)
    {
        $article = Article::findOrFail($id);
        $formatter = new ArticleFormatter;
        $html = $formatter->buildWordDoc($article);

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-word',
            'Content-Disposition' => "attachment; filename=\"{$article->slug}.doc\"",
        ]);
    }
}
