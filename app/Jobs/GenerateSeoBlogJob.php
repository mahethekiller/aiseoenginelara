<?php

namespace App\Jobs;

use App\Exceptions\LlmApiException;
use App\Models\SeoGenerationJob;
use App\Services\AiHumanizer;
use App\Services\ArticleFormatter;
use App\Services\MultiProviderLlmClient;
use App\Services\PromptBuilder;
use App\Services\SerpCrawler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateSeoBlogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected SeoGenerationJob $job;

    public function __construct(SeoGenerationJob $job)
    {
        $this->job = $job;
    }

    public function handle(SerpCrawler $crawler, AiHumanizer $humanizer): void
    {
        @set_time_limit(240);
        $this->job->update(['status' => 'processing']);

        $params = $this->job->parameters;
        $title = $params['topic'];
        $slug = Str::slug($title);

        $logs = [];
        $logs[] = '['.date('H:i:s').'] Dispatching Job in background: Generation starting...';
        $this->job->update(['logs' => json_encode($logs)]);

        try {
            $user = $this->job->user;
            $preset = $user->presets()->where('is_active', true)->orderBy('updated_at', 'desc')->first();

            $provider = 'gemini';
            $model = 'gemini-2.0-flash';
            $temperature = 0.7;
            $presetInstructions = '';

            if ($preset) {
                $provider = $preset->provider;
                $model = $preset->model;
                $temperature = $preset->temperature;
                $presetInstructions = $preset->custom_instructions ?? '';
            }

            $competitorOutlines = [];
            if (! empty($params['enable_serp_crawler'])) {
                $logs[] = '['.date('H:i:s').'] Scraping competitor outlines...';
                $this->job->update(['logs' => json_encode($logs)]);
                $competitorOutlines = $crawler->fetchCompetitors($title);
            }

            $client = new MultiProviderLlmClient($provider, $model);
            $promptBuilder = new PromptBuilder;

            // Pass 1: Outline
            $logs[] = '['.date('H:i:s').'] Generating detailed section-by-section outline...';
            $this->job->update(['logs' => json_encode($logs)]);

            $outlinePrompts = $promptBuilder->buildOutlinePrompt($params, $presetInstructions, $competitorOutlines);
            $outlineResult = $client->generateText($outlinePrompts['system'], $outlinePrompts['user'], ['temperature' => 0.5]);

            $outlineClean = trim($outlineResult['text']);
            if (preg_match('/```json\s*(.*?)\s*```/s', $outlineClean, $jsonMatches)) {
                $outlineClean = trim($jsonMatches[1]);
            }
            $sections = json_decode($outlineClean, true);

            if (! is_array($sections) || empty($sections)) {
                $sections = [
                    ['heading' => 'Introduction to '.$title, 'type' => 'intro', 'talking_points' => ['Overview'], 'target_words' => 200],
                    ['heading' => 'Key Strategies & Walkthrough', 'type' => 'standard', 'talking_points' => ['Main elements'], 'target_words' => 400],
                    ['heading' => 'Comparison Summary', 'type' => 'comparison-table', 'talking_points' => ['Comparison Table'], 'target_words' => 250],
                    ['heading' => 'Frequently Asked Questions', 'type' => 'faq', 'talking_points' => ['Questions'], 'target_words' => 200],
                    ['heading' => 'Conclusion & Final Recommendations', 'type' => 'conclusion', 'talking_points' => ['Synthesize core lessons and strategic takeaways'], 'target_words' => 250],
                    ['heading' => 'Next Steps', 'type' => 'cta', 'talking_points' => ['Conversion callout'], 'target_words' => 100],
                ];
            } else {
                // Ensure every outline has a dedicated conclusion section
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

            // Pass 2: Section loops (Concurrent Parallel Writing)
            $logs[] = '['.date('H:i:s').'] Preparing prompts for '.count($sections).' sections...';
            $this->job->update(['logs' => json_encode($logs)]);

            $sectionPrompts = [];
            foreach ($sections as $index => $section) {
                $sectionPrompts[$index] = $promptBuilder->buildSectionPrompt($params, $section, '', $presetInstructions);
            }

            $logs[] = '['.date('H:i:s').'] Dispatching concurrent parallel writing requests to LLM pool...';
            $this->job->update(['logs' => json_encode($logs)]);

            $sectResults = $client->generateTextParallel($sectionPrompts, ['temperature' => $temperature]);

            $logs[] = '['.date('H:i:s').'] All parallel writes resolved. Compiling HTML document flow...';
            $this->job->update(['logs' => json_encode($logs)]);

            $compiledHtml = '';
            foreach ($sections as $index => $section) {
                $sectionHtml = trim($sectResults[$index]['text'] ?? '');
                if (preg_match('/```html\s*(.*?)\s*```/s', $sectionHtml, $htmlMatches)) {
                    $sectionHtml = trim($htmlMatches[1]);
                }

                $headingTag = $index === 0 ? 'h1' : 'h2';
                if (stripos($sectionHtml, "<{$headingTag}") === false && stripos($sectionHtml, "{$section['heading']}") === false) {
                    $compiledHtml .= "\n<{$headingTag}>".htmlspecialchars($section['heading'])."</{$headingTag}>\n";
                }

                $compiledHtml .= "\n".$sectionHtml;
            }

            if (! empty($params['humanizer_active'])) {
                $compiledHtml = $humanizer->polish($compiledHtml);
            }

            // Pass 3: Metadata
            $logs[] = '['.date('H:i:s').'] Optimizing meta titles, meta descriptions, and url slug...';
            $this->job->update(['logs' => json_encode($logs)]);

            $metaPrompts = $promptBuilder->buildMetadataPrompt($title, $compiledHtml);
            $metaResult = $client->generateText($metaPrompts['system'], $metaPrompts['user'], ['temperature' => 0.4]);

            $metaClean = trim($metaResult['text']);
            if (preg_match('/```json\s*(.*?)\s*```/s', $metaClean, $jsonMatches)) {
                $metaClean = trim($jsonMatches[1]);
            }
            $metaData = json_decode($metaClean, true);

            $metaTitle = $metaData['meta_title'] ?? "{$title} - SEO Guide";
            $metaDesc = $metaData['meta_description'] ?? "Read details about {$title}";
            $articleSlug = ! empty($metaData['url_slug']) ? Str::slug($metaData['url_slug']) : $slug;
            $faqSchema = $metaData['faq_schema'] ?? [];

            $formatter = new ArticleFormatter;
            $formattedHtml = $formatter->formatContent($compiledHtml);

            $wordCountVal = str_word_count(strip_tags($formattedHtml));
            $fleschScore = $this->calculateFleschScore($formattedHtml);

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

            // Save Article
            $this->job->articles()->create([
                'user_id' => $this->job->user_id,
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

            $logs[] = '['.date('H:i:s').'] Generation finished successfully. File compiled.';
            $this->job->update([
                'status' => 'completed',
                'completed_items' => 1,
                'logs' => json_encode($logs),
            ]);
        } catch (\Throwable $e) {
            $apiException = LlmApiException::fromThrowable($e, $provider ?? 'AI');
            $logs[] = '[Error] Generation failed in background: '.$apiException->getMessage();
            $this->job->update([
                'status' => 'failed',
                'error_message' => json_encode($apiException->toStructuredArray()),
                'logs' => json_encode($logs),
            ]);
        }
    }

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
}
