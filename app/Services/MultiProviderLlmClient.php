<?php

namespace App\Services;

use App\Models\AiUsageLog;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MultiProviderLlmClient
{
    protected string $provider;

    protected string $model;

    protected ?string $apiKey;

    protected ?string $baseUrl;

    public function __construct(string $provider, string $model, ?string $apiKey = null, ?string $baseUrl = null)
    {
        $this->provider = $provider;
        $this->model = $model;
        $this->apiKey = $apiKey ?? $this->resolveApiKey($provider);
        $this->baseUrl = $baseUrl ?? $this->resolveBaseUrl($provider);
    }

    public function generateText(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $startTime = microtime(true);
        $pipelineStep = $options['pipeline_step'] ?? 'AI Content Task';
        $userId = $options['user_id'] ?? (auth()->id() ?? 1);
        $clientId = $options['client_id'] ?? null;

        if (empty($this->apiKey) || $this->apiKey === 'demo_key') {
            throw new \Exception("API key for provider '{$this->provider}' is not configured. Please enter your API key in Settings & Presets.");
        }

        try {
            $result = match ($this->provider) {
                'openai', 'deepseek', 'groq', 'ollama' => $this->callOpenAiCompatibleApi($systemPrompt, $userPrompt, $options),
                'gemini' => $this->callGeminiApi($systemPrompt, $userPrompt, $options),
                'anthropic' => $this->callAnthropicApi($systemPrompt, $userPrompt, $options),
                default => throw new \Exception("Unsupported LLM provider: {$this->provider}"),
            };

            $executionTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $this->logUsage($userId, $clientId, $pipelineStep, $result['prompt_tokens'] ?? 0, $result['completion_tokens'] ?? 0, $executionTimeMs, 'success');

            return $result;
        } catch (\Exception $e) {
            $executionTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $this->logUsage($userId, $clientId, $pipelineStep, 0, 0, $executionTimeMs, 'failed', $e->getMessage());
            throw $e;
        }
    }

    public function logUsage(int $userId, ?int $clientId, string $pipelineStep, int $promptTokens, int $completionTokens, int $executionTimeMs, string $status = 'success', ?string $errorMessage = null): void
    {
        try {
            $totalTokens = $promptTokens + $completionTokens;

            // Pricing matrix per 1,000 tokens
            $inputRatePer1k = match ($this->provider) {
                'gemini' => 0.0015,
                'openai' => 0.0025,
                'anthropic' => 0.0030,
                'deepseek' => 0.0007,
                default => 0.0015,
            };

            $outputRatePer1k = match ($this->provider) {
                'gemini' => 0.0045,
                'openai' => 0.0100,
                'anthropic' => 0.0150,
                'deepseek' => 0.0028,
                default => 0.0045,
            };

            $costUsd = (($promptTokens / 1000) * $inputRatePer1k) + (($completionTokens / 1000) * $outputRatePer1k);
            $inrRate = (float) env('USD_TO_INR_RATE', 84.00);
            $costInr = $costUsd * $inrRate;

            AiUsageLog::create([
                'user_id' => $userId,
                'client_id' => $clientId,
                'pipeline_step' => $pipelineStep,
                'provider' => $this->provider,
                'model' => $this->model,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'estimated_cost_usd' => $costUsd,
                'estimated_cost_inr' => $costInr,
                'usd_to_inr_rate' => $inrRate,
                'execution_time_ms' => $executionTimeMs,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log AI usage to database: '.$e->getMessage());
        }
    }

    protected function callOpenAiCompatibleApi(string $system, string $user, array $opts): array
    {
        $response = Http::timeout(240)->withOptions([
            'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
        ])->withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => $opts['temperature'] ?? 0.7,
        ]);

        if ($response->failed()) {
            throw new \Exception('LLM Request Failed: '.$response->body());
        }

        $data = $response->json();

        return [
            'text' => $data['choices'][0]['message']['content'],
            'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
        ];
    }

    protected function callGeminiApi(string $system, string $user, array $opts): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::timeout(240)->withOptions([
            'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
        ])->post($url, [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $user]]],
            ],
            'generationConfig' => [
                'temperature' => $opts['temperature'] ?? 0.7,
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('Gemini Request Failed: '.$response->body());
        }

        $data = $response->json();

        return [
            'text' => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
            'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
            'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
        ];
    }

    protected function callAnthropicApi(string $system, string $user, array $opts): array
    {
        // Note: temperature is deprecated on newer Anthropic models (claude-opus-4, claude-sonnet-4+)
        // We intentionally omit it to ensure compatibility across all model variants.
        $payload = [
            'model' => $this->model,
            'max_tokens' => 4000,
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $user],
            ],
        ];

        $response = Http::timeout(240)->withOptions([
            'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
        ])->withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', $payload);

        if ($response->failed()) {
            throw new \Exception('Anthropic Request Failed: '.$response->body());
        }

        $data = $response->json();

        return [
            'text' => $data['content'][0]['text'] ?? '',
            'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
            'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
        ];
    }

    protected function getMockStructuredResponse(string $userPrompt, ?string $notice = null): array
    {
        preg_match('/Topic: (.*?)\n/i', $userPrompt, $topicMatches);
        $topic = trim($topicMatches[1] ?? 'SEO Best Practices');

        preg_match('/Primary Keyword: (.*?)\n/i', $userPrompt, $kwMatches);
        $kw = trim($kwMatches[1] ?? 'SEO Optimization');

        preg_match('/Secondary Keywords: (.*?)\n/i', $userPrompt, $secMatches);
        $secondaryKw = trim($secMatches[1] ?? 'None specified');

        preg_match('/Search Intent: (.*?)\n/i', $userPrompt, $intentMatches);
        $searchIntent = trim($intentMatches[1] ?? 'Informational');

        preg_match('/Article Format: (.*?)\n/i', $userPrompt, $fmtMatches);
        $format = trim($fmtMatches[1] ?? 'Ultimate Guide');

        preg_match('/Target Audience: (.*?)\n/i', $userPrompt, $audMatches);
        $targetAudience = trim($audMatches[1] ?? 'General Audience');

        preg_match('/Target Word Count: (.*?)\n/i', $userPrompt, $wcMatches);
        $wordCount = trim($wcMatches[1] ?? 'Standard');

        preg_match('/Tone: (.*?)\n/i', $userPrompt, $toneMatches);
        $tone = trim($toneMatches[1] ?? 'Conversational');

        $noticeHtml = $notice ? "<div style=\"background-color:#334155;color:#f8fafc;padding:8px 12px;border-radius:6px;font-size:12px;margin-bottom:12px;\">{$notice}</div>" : '';

        $mockText = "```json_metadata\n"
            ."{\n"
            ."  \"meta_title\": \"{$topic} - {$format} for {$targetAudience}\",\n"
            ."  \"meta_description\": \"Explore {$kw} with our {$searchIntent} {$format}. Custom-tailored for {$targetAudience} in a {$tone} tone.\",\n"
            .'  "url_slug": "'.Str::slug($kw)."-{$format}\",\n"
            ."  \"primary_keyword\": \"{$kw}\",\n"
            ."  \"faq_schema\": [\n"
            ."    {\"question\": \"What is the main goal of {$kw}?\", \"answer\": \"{$kw} is engineered for {$searchIntent} search intent targeting {$targetAudience}.\"},\n"
            ."    {\"question\": \"How does {$topic} benefit {$targetAudience}?\", \"answer\": \"It delivers actionable strategies tailored in a {$tone} tone for optimum results.\"}\n"
            ."  ]\n"
            ."}\n"
            ."```\n"
            ."{$noticeHtml}\n"
            ."<h1>{$topic}: {$format}</h1>\n"
            ."<p>Crafted specifically for <strong>{$targetAudience}</strong>, this <em>{$format}</em> addresses <strong>{$kw}</strong> with an <em>{$searchIntent}</em> intent. Written in a <strong>{$tone}</strong> tone across a <strong>{$wordCount}</strong> word count profile.</p>\n"
            ."<div class=\"key-takeaways\">\n"
            ."  <h3>Key Takeaways for {$targetAudience}</h3>\n"
            ."  <ul>\n"
            ."    <li><strong>Primary Focus:</strong> Mastering {$kw} effectively.</li>\n"
            ."    <li><strong>LSI & Secondary Terms:</strong> Weaving in {$secondaryKw}.</li>\n"
            ."    <li><strong>Execution Strategy:</strong> Tailored for {$searchIntent} queries with clear steps.</li>\n"
            ."  </ul>\n"
            ."</div>\n"
            ."<h2>1. Core Overview of {$kw}</h2>\n"
            ."<p>When addressing <strong>{$targetAudience}</strong>, maintaining a <strong>{$tone}</strong> perspective ensures maximal reader retention and engagement.</p>\n"
            ."<!-- IMAGE_PROMPT: Fundamentals | Infographic detailing {$kw} for {$targetAudience} | Modern vector diagram -->\n"
            ."<table>\n"
            ."  <thead>\n"
            ."    <tr><th>Configuration Metric</th><th>Applied Value</th><th>Optimization Goal</th></tr>\n"
            ."  </thead>\n"
            ."  <tbody>\n"
            ."    <tr><td>Search Intent</td><td>{$searchIntent}</td><td>Relevance alignment</td></tr>\n"
            ."    <tr><td>Article Format</td><td>{$format}</td><td>Structure consistency</td></tr>\n"
            ."    <tr><td>Tone & Audience</td><td>{$tone} ({$targetAudience})</td><td>Reader retention</td></tr>\n"
            ."    <tr><td>Secondary Keywords</td><td>{$secondaryKw}</td><td>LSI keyword density</td></tr>\n"
            ."  </tbody>\n"
            ."</table>\n"
            ."<h2>Frequently Asked Questions</h2>\n"
            ."<h3>What is the main goal of {$kw}?</h3>\n"
            ."<p>{$kw} is engineered for {$searchIntent} search intent targeting {$targetAudience}.</p>\n"
            ."<h3>How does {$topic} benefit {$targetAudience}?</h3>\n"
            ."<p>It delivers actionable strategies tailored in a {$tone} tone for optimum results.</p>\n"
            ."<div class=\"cta-box\">\n"
            ."  <h2>Take Action Today</h2>\n"
            ."  <p>Ready to master <strong>{$kw}</strong>? Start applying these principles to transform your results.</p>\n"
            .'</div>';

        return [
            'text' => $mockText,
            'prompt_tokens' => 240,
            'completion_tokens' => 480,
        ];
    }

    protected function resolveApiKey(string $provider): string
    {
        $path = 'config/app_config.json';
        if (Storage::disk('local')->exists($path)) {
            $config = json_decode(Storage::disk('local')->get($path), true);
            if (isset($config['api_keys'][$provider]) && $config['api_keys'][$provider] && ! str_contains($config['api_keys'][$provider], '••••')) {
                return $config['api_keys'][$provider];
            }
        }

        return env(strtoupper($provider).'_API_KEY', 'demo_key');
    }

    protected function resolveBaseUrl(string $provider): string
    {
        return match ($provider) {
            'deepseek' => 'https://api.deepseek.com/v1',
            'groq' => 'https://api.groq.com/openai/v1',
            'ollama' => 'http://localhost:11434/v1',
            default => 'https://api.openai.com/v1',
        };
    }

    public function generateTextParallel(array $prompts, array $options = []): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'demo_key') {
            throw new \Exception("API key for provider '{$this->provider}' is not configured. Please add a valid key in settings.");
        }

        $responses = Http::pool(function (Pool $pool) use ($prompts, $options) {
            $poolRequests = [];
            foreach ($prompts as $index => $prompt) {
                $system = $prompt['system'];
                $user = $prompt['user'];
                $temperature = $options['temperature'] ?? 0.7;

                if ($this->provider === 'gemini') {
                    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
                    $poolRequests[$index] = $pool->timeout(240)->withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                    ])->post($url, [
                        'systemInstruction' => [
                            'parts' => [['text' => $system]],
                        ],
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => $user]]],
                        ],
                        'generationConfig' => [
                            'temperature' => $temperature,
                        ],
                    ]);
                } elseif ($this->provider === 'anthropic') {
                    // temperature omitted intentionally — deprecated on newer Anthropic models
                    $poolRequests[$index] = $pool->timeout(240)->withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                    ])->withHeaders([
                        'x-api-key' => $this->apiKey,
                        'anthropic-version' => '2023-06-01',
                        'Content-Type' => 'application/json',
                    ])->post('https://api.anthropic.com/v1/messages', [
                        'model' => $this->model,
                        'max_tokens' => 4000,
                        'system' => $system,
                        'messages' => [
                            ['role' => 'user', 'content' => $user],
                        ],
                    ]);
                } else {
                    $poolRequests[$index] = $pool->timeout(240)->withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                    ])->withHeaders([
                        'Authorization' => 'Bearer '.$this->apiKey,
                        'Content-Type' => 'application/json',
                    ])->post("{$this->baseUrl}/chat/completions", [
                        'model' => $this->model,
                        'messages' => [
                            ['role' => 'system', 'content' => $system],
                            ['role' => 'user', 'content' => $user],
                        ],
                        'temperature' => $temperature,
                    ]);
                }
            }

            return $poolRequests;
        });

        $results = [];
        foreach ($prompts as $index => $prompt) {
            $response = $responses[$index] ?? null;
            if (! $response || $response instanceof \Exception || ! $response->successful()) {
                $err = ($response instanceof \Exception) ? $response->getMessage() : ($response ? $response->body() : 'No response received.');
                throw new \Exception("Parallel LLM generation failed for segment {$index}: {$err}");
            }

            $data = $response->json();
            if ($this->provider === 'gemini') {
                $results[$index] = [
                    'text' => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
                    'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                    'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                ];
            } elseif ($this->provider === 'anthropic') {
                $results[$index] = [
                    'text' => $data['content'][0]['text'] ?? '',
                    'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
                ];
            } else {
                $results[$index] = [
                    'text' => $data['choices'][0]['message']['content'] ?? '',
                    'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                ];
            }
        }

        return $results;
    }
}
