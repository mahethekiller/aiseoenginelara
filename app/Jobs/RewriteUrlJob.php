<?php

namespace App\Jobs;

use App\Exceptions\LlmApiException;
use App\Models\RewriterJob;
use App\Services\LayoutPreservingRewriter;
use App\Services\MultiProviderLlmClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class RewriteUrlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes

    protected $jobRecord;

    /**
     * Create a new job instance.
     */
    public function __construct(RewriterJob $jobRecord)
    {
        $this->jobRecord = $jobRecord;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->jobRecord->update(['status' => 'processing']);

        try {
            $user = $this->jobRecord->user;
            $preset = $user->presets()->where('is_active', true)->orderBy('updated_at', 'desc')->first();

            $provider = 'gemini';
            $model = 'gemini-2.0-flash';
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

            $client = new MultiProviderLlmClient($provider, $model);
            $rewriter = new LayoutPreservingRewriter($client);

            $instructions = trim($presetInstructions."\n".($this->jobRecord->custom_instructions ?? ''));

            $rewrittenHtml = $rewriter->rewriteUrl($this->jobRecord->source_url, $instructions, $this->jobRecord->rewriter_mode ?? 'layout-preserving');

            $this->jobRecord->update([
                'status' => 'completed',
                'original_html' => $rewriter->getCleanOriginalHtml(),
                'rewritten_html' => $rewrittenHtml,
                'prompt_tokens' => $rewriter->getPromptTokens(),
                'completion_tokens' => $rewriter->getCompletionTokens(),
            ]);
        } catch (\Throwable $e) {
            $apiException = LlmApiException::fromThrowable($e, $provider ?? 'AI');
            $this->jobRecord->update([
                'status' => 'failed',
                'error_message' => json_encode($apiException->toStructuredArray()),
            ]);
        }
    }
}
