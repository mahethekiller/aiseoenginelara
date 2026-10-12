<?php

namespace App\Jobs;

use App\Models\RankCheckBatch;
use App\Services\KeywordRankCheckerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Backoff([5, 15, 60])]
#[FailOnTimeout]
class ProcessKeywordRankBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 1800; // 30 minutes max execution time for large CSV batches

    /**
     * Create a new job instance. Pass ID only to avoid stale serialized state.
     */
    public function __construct(public readonly int $batchRecordId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(KeywordRankCheckerService $checkerService): void
    {
        $batch = RankCheckBatch::find($this->batchRecordId);
        if (! $batch) {
            Log::warning("ProcessKeywordRankBatchJob aborted: Batch #{$this->batchRecordId} not found.");
            return;
        }

        if ($batch->status === 'cancelled') {
            Log::info("ProcessKeywordRankBatchJob aborted: Batch #{$this->batchRecordId} was cancelled.");
            return;
        }

        $batch->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        $keywords = $batch->extracted_keywords ?? [];
        if (empty($keywords)) {
            $batch->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            return;
        }

        $completedCount = 0;
        $failedCount = 0;
        $top3Count = 0;
        $top10Count = 0;
        $page1Count = 0;
        $unrankedCount = 0;

        foreach ($keywords as $index => $item) {
            // Cancellation check every 5 keywords
            if ($index % 5 === 0) {
                $batch->refresh();
                if ($batch->status === 'cancelled') {
                    Log::info("Batch {$batch->batch_id} cancelled during processing at item {$index}.");
                    break;
                }
            }

            $kw = is_array($item) ? ($item['keyword'] ?? '') : (string) $item;
            $kw = trim($kw);
            if (empty($kw)) {
                continue;
            }

            // Keyword specific overrides or fall back to batch defaults
            $targetDomain = (! empty($item['target_domain'])) ? trim($item['target_domain']) : $batch->target_domain;
            $location = (! empty($item['location'])) ? trim($item['location']) : $batch->location;
            $targetUrl = (! empty($item['target_url'])) ? trim($item['target_url']) : $batch->target_url;
            $matchType = (! empty($item['match_type'])) ? trim($item['match_type']) : $batch->match_type;

            $options = [
                'target_url' => $targetUrl,
                'match_type' => $matchType,
                'location' => $location,
                'country' => $batch->country,
                'language' => $batch->language,
                'device' => $batch->device,
                'depth' => $batch->depth,
                'force_refresh' => true,
            ];

            try {
                $res = $checkerService->checkKeyword(
                    $kw,
                    $targetDomain,
                    $options,
                    $batch->user_id,
                    $batch->client_id,
                    $batch->batch_id
                );

                $completedCount++;
                $pos = $res['position'] ?? null;
                if ($pos !== null) {
                    if ($pos <= 3) {
                        $top3Count++;
                    }
                    if ($pos <= 10) {
                        $top10Count++;
                        $page1Count++;
                    }
                } else {
                    $unrankedCount++;
                }

                // Throttle pause between live calls to avoid hammering SerpApi
                if (empty($res['is_cached']) && $index < count($keywords) - 1) {
                    usleep(180000); // 180ms delay
                }
            } catch (Throwable $e) {
                $failedCount++;
                Log::error("Bulk keyword check failed for '{$kw}' in batch {$batch->batch_id}: " . $e->getMessage());
            }

            // Periodically persist progress in database
            if ($index % 2 === 0 || $index === count($keywords) - 1) {
                $batch->update([
                    'completed_keywords' => $completedCount,
                    'failed_keywords' => $failedCount,
                    'top_3_count' => $top3Count,
                    'top_10_count' => $top10Count,
                    'page_1_count' => $page1Count,
                    'unranked_count' => $unrankedCount,
                ]);
            }
        }

        // Final completion update
        $batch->refresh();
        if ($batch->status !== 'cancelled') {
            $batch->update([
                'status' => 'completed',
                'completed_keywords' => $completedCount,
                'failed_keywords' => $failedCount,
                'top_3_count' => $top3Count,
                'top_10_count' => $top10Count,
                'page_1_count' => $page1Count,
                'unranked_count' => $unrankedCount,
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Throwable $e): void
    {
        Log::error("ProcessKeywordRankBatchJob permanent failure for Batch #{$this->batchRecordId}: " . $e->getMessage());
        RankCheckBatch::find($this->batchRecordId)?->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'completed_at' => now(),
        ]);
    }
}
