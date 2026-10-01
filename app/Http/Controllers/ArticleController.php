<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiPromptTemplate;
use App\Models\Article;
use App\Models\Client;
use App\Services\ArticleFormatter;
use App\Services\PromptBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleFormatter $formatter
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasAnyRole(['admin', 'super_admin']);

        $query = Article::with(['user', 'generationJob', 'promptTemplate', 'client'])->latest();

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('meta_description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('client_id')) {
            $clientFilter = $request->input('client_id');
            if ($clientFilter === 'none') {
                $query->whereNull('client_id');
            } else {
                $query->where('client_id', $clientFilter);
            }
        }

        if ($request->filled('min_score')) {
            $query->where('seo_score', '>=', (int) $request->input('min_score'));
        }

        if ($request->filled('prompt_template')) {
            $templateFilter = $request->input('prompt_template');
            if ($templateFilter === 'master') {
                $query->where(function ($q) {
                    $q->whereNull('prompt_template_id')
                        ->orWhere('prompt_template_name', 'Comprehensive Master Prompt');
                });
            } else {
                $query->where('prompt_template_id', $templateFilter);
            }
        }

        $articles = $query->paginate(15)->withQueryString();

        // Telemetry metrics scoped to user permissions
        $metricsQuery = $isAdmin
            ? ($request->filled('user_id') ? Article::where('user_id', $request->input('user_id')) : Article::query())
            : Article::where('user_id', $user->id);

        $metrics = [
            'total_articles' => (clone $metricsQuery)->count(),
            'avg_seo_score' => round((float) (clone $metricsQuery)->avg('seo_score'), 1),
            'avg_flesch_score' => round((float) (clone $metricsQuery)->avg('flesch_reading_ease'), 1),
            'total_words' => (int) (clone $metricsQuery)->sum('word_count'),
            'total_published' => (clone $metricsQuery)->whereNotNull('wordpress_post_id')->count(),
        ];

        $users = $isAdmin ? \App\Models\User::orderBy('name')->get(['id', 'name', 'email']) : collect();
        $promptTemplates = AiPromptTemplate::orderBy('archetype_name')->get();
        $clients = Client::orderBy('name')->get(['id', 'name', 'industry', 'brand_tone']);

        return view('pages.articles.index', compact('articles', 'metrics', 'users', 'isAdmin', 'promptTemplates', 'clients'));
    }

    public function show(Request $request, int|string $id)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasAnyRole(['admin', 'super_admin']);

        $article = Article::with(['user', 'generationJob', 'promptTemplate', 'client'])->findOrFail($id);

        if (! $isAdmin && $article->user_id !== $user->id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthorized access to this article.'], 403);
            }
            abort(403, 'Unauthorized access to this article.');
        }

        if ($request->expectsJson() || $request->ajax() || ! view()->exists('pages.articles.show')) {
            return response()->json([
                'success' => true,
                'article' => $article,
            ]);
        }

        return view('pages.articles.show', compact('article'));
    }

    public function destroy(Request $request, int|string $id)
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['admin', 'super_admin'])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthorized. Only administrators can delete articles.'], 403);
            }
            abort(403, 'Unauthorized. Only administrators can delete articles.');
        }

        $article = Article::findOrFail($id);
        $article->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Article deleted successfully.']);
        }

        return redirect()->route('articles.index')->with('success', 'Article deleted successfully.');
    }

    public function download(Request $request, int|string $id, string $format)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasAnyRole(['admin', 'super_admin']);

        $article = Article::findOrFail($id);

        if (! $isAdmin && $article->user_id !== $user?->id) {
            abort(403, 'Unauthorized access to download this article.');
        }

        if (strtolower($format) === 'docx' || strtolower($format) === 'doc') {
            $content = $this->formatter->buildWordDoc($article);

            return response($content, 200, [
                'Content-Type' => 'application/vnd.ms-word',
                'Content-Disposition' => "attachment; filename=\"{$article->slug}.doc\"",
            ]);
        }

        $html = $this->formatter->buildFullHtml($article);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$article->slug}.html\"",
        ]);
    }

    public function publishToWordPress(Request $request, int|string $id)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasAnyRole(['admin', 'super_admin']);

        $article = Article::findOrFail($id);

        if (! $isAdmin && $article->user_id !== $user?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to publish this article.',
            ], 403);
        }

        $client = $user?->active_client_id ? Client::find($user->active_client_id) : null;

        if (! $client || empty($client->wordpress_url)) {
            return response()->json([
                'success' => false,
                'message' => 'No active client selected or client does not have WordPress credentials configured.',
            ], 422);
        }

        if (empty($client->wordpress_username) || empty($client->wordpress_app_password)) {
            return response()->json([
                'success' => false,
                'message' => 'WordPress username or application password missing in client settings.',
            ], 422);
        }

        try {
            $endpoint = rtrim($client->wordpress_url, '/').'/wp-json/wp/v2/posts';
            $response = Http::withBasicAuth($client->wordpress_username, $client->wordpress_app_password)
                ->timeout(30)
                ->post($endpoint, [
                    'title' => $article->title,
                    'content' => $article->html_content,
                    'status' => 'draft',
                    'slug' => $article->slug,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $article->update([
                    'wordpress_post_id' => $data['id'] ?? null,
                    'wordpress_post_url' => $data['link'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Article published to WordPress as draft successfully!',
                    'post_url' => $data['link'] ?? null,
                    'post_id' => $data['id'] ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'WordPress API returned error: '.$response->body(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to WordPress REST API: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getPrompt(Request $request, int|string $id)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasAnyRole(['admin', 'super_admin']);

        $article = Article::with(['user', 'generationJob', 'promptTemplate'])->findOrFail($id);

        if (! $isAdmin && $article->user_id !== $user?->id) {
            return response()->json(['message' => 'Unauthorized access to article prompt.'], 403);
        }

        $job = $article->generationJob;
        $params = $job?->parameters ?? [];

        // Fill fallback parameters if needed
        $topic = ! empty($params['topic']) ? $params['topic'] : $article->title;
        $primaryKeyword = ! empty($params['primary_keyword']) 
            ? $params['primary_keyword'] 
            : (! empty($article->keyword_density_metrics) ? array_key_first($article->keyword_density_metrics) : $article->title);

        $params['topic'] = $topic;
        $params['primary_keyword'] = $primaryKeyword;
        if (! isset($params['format'])) {
            $params['format'] = 'Ultimate Guide';
        }
        if (! isset($params['tone'])) {
            $params['tone'] = 'Authoritative, Informative, Engaging';
        }
        if (! isset($params['pov'])) {
            $params['pov'] = 'Second Person';
        }
        if (! isset($params['word_count'])) {
            $params['word_count'] = 'Standard (~1500w)';
        }

        // 1. Resolve Prompt Blueprint Archetype (Template)
        $templateId = $article->prompt_template_id ?? ($params['prompt_template_id'] ?? null);
        $template = $templateId ? AiPromptTemplate::find($templateId) : null;
        $templateName = $article->prompt_template_name 
            ?: ($template?->archetype_name ?? 'Comprehensive Master Prompt');
        $presetInstructions = '';

        if ($template) {
            $presetInstructions .= "\n\nCUSTOM PROMPT BLUEPRINT DIRECTIVES ({$template->archetype_name}):\n".$template->system_prompt_template;
        }

        // 2. Resolve Client Context if applicable
        $clientId = $params['client_id'] ?? null;
        $client = ($clientId && $clientId !== 'none') ? Client::find($clientId) : null;
        $clientContext = null;

        if ($client) {
            $approvedDomains = is_array($client->approved_reference_domains)
                ? $client->approved_reference_domains
                : (is_string($client->approved_reference_domains) ? explode(',', $client->approved_reference_domains) : []);

            $clientContext = [
                'name' => $client->name,
                'website_url' => $client->website_url,
                'industry' => $client->industry,
                'brand_tone' => $client->brand_tone,
                'target_audience' => $client->target_audience,
                'cta_default' => $client->cta_default,
                'internal_links' => [],
                'approved_reference_domains' => $approvedDomains,
            ];
        }

        // 3. Build Full Prompts Suite via PromptBuilder
        $promptBuilder = new PromptBuilder;
        $competitorOutlines = ! empty($params['enable_serp_crawler']) ? [
            'Competitor 1 Structure: Intro -> Core Framework -> Comparison Table -> FAQ -> Conclusion',
            'Competitor 2 Structure: Overview -> Tactical Step-by-Step Guide -> Best Practices -> Case Studies',
        ] : [];

        $masterPrompt = $promptBuilder->buildSeoArticlePrompt($params, $presetInstructions, $competitorOutlines, $clientContext);
        $outlinePrompt = $promptBuilder->buildOutlinePrompt($params, $presetInstructions, $competitorOutlines, $clientContext);

        $sectionIntro = $promptBuilder->buildSectionPrompt(
            $params,
            [
                'heading' => 'Introduction to '.$topic,
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
                'heading' => 'Conclusion & Final Recommendations',
                'type' => 'conclusion',
                'talking_points' => ['Core synthesis', 'Strategic takeaways and final advice'],
                'target_words' => 250,
            ],
            '<!-- [Previously compiled body sections HTML] -->',
            $presetInstructions,
            $clientContext
        );

        $sectionCta = $promptBuilder->buildSectionPrompt(
            $params,
            [
                'heading' => 'Next Steps',
                'type' => 'cta',
                'talking_points' => ['Action steps and call to action'],
                'target_words' => 100,
            ],
            '<!-- [Previously compiled body sections HTML] -->',
            $presetInstructions,
            $clientContext
        );

        $metadataPrompt = $promptBuilder->buildMetadataPrompt($topic, Str::limit($article->html_content, 1000));

        return response()->json([
            'success' => true,
            'article_id' => $article->id,
            'article_title' => $article->title,
            'template_name' => $templateName,
            'is_custom' => $template ? ! $template->is_system : false,
            'template_archetype_key' => $template?->archetype_key ?? 'master_seo_directive',
            'custom_prompt_directives' => $template?->system_prompt_template,
            'parameters' => [
                'topic' => $topic,
                'primary_keyword' => $primaryKeyword,
                'secondary_keywords' => $params['secondary_keywords'] ?? 'None',
                'format' => $params['format'] ?? 'Ultimate Guide',
                'tone' => $params['tone'] ?? 'Authoritative',
                'pov' => $params['pov'] ?? 'Second Person',
                'word_count' => $params['word_count'] ?? 'Standard',
                'industry' => $params['industry'] ?? ($client?->industry ?? 'General'),
                'target_audience' => $params['target_audience'] ?? ($client?->target_audience ?? 'General Audience'),
                'client_name' => $client?->name ?? 'Independent (No Client Profile)',
                'humanizer_active' => ! empty($params['humanizer_active']),
                'serp_crawler_active' => ! empty($params['enable_serp_crawler']),
                'model' => 'gemini-3.5-flash',
                'prompt_tokens' => $article->prompt_tokens,
                'completion_tokens' => $article->completion_tokens,
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
}
