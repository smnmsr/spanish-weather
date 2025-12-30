<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Polls batch metadata to detect when all worker jobs have completed.
 * Dispatches itself recursively until all jobs are done.
 */
class CheckBatchCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $checkCount = 0;

    public function __construct(
        protected string $batchId,
        protected int $totalJobs,
        protected int $maxChecks = 120, // 2 minutes with 1s delay
    ) {}

    public function handle(): void
    {
        $this->checkCount++;

        $metadataKey = "batch_metadata_{$this->batchId}";
        $metadata = Cache::get($metadataKey, []);

        $failedJobs = $metadata['failedJobs'] ?? 0;
        $completedJobs = $metadata['completedJobs'] ?? 0;
        $totalProcessed = $failedJobs + $completedJobs;

        Log::info('CheckBatchCompletion: checking batch status', [
            'batchId' => $this->batchId,
            'totalJobs' => $this->totalJobs,
            'completedJobs' => $completedJobs,
            'failedJobs' => $failedJobs,
            'totalProcessed' => $totalProcessed,
            'checkCount' => $this->checkCount,
            'maxChecks' => $this->maxChecks,
        ]);

        // Check if all jobs have completed (success + failure)
        if ($totalProcessed >= $this->totalJobs) {
            // All jobs done!
            if ($failedJobs === 0) {
                // All succeeded
                Cache::put($metadataKey, array_merge($metadata, [
                    'status' => 'completed',
                    'completedAt' => now(),
                ]), 24 * 60 * 60);

                Log::info('CheckBatchCompletion: batch completed successfully', [
                    'batchId' => $this->batchId,
                    'totalJobs' => $this->totalJobs,
                    'completedJobs' => $completedJobs,
                ]);
            } else {
                // Some failed
                Cache::put($metadataKey, array_merge($metadata, [
                    'status' => 'completed',
                    'completedAt' => now(),
                ]), 24 * 60 * 60);

                Log::warning('CheckBatchCompletion: batch completed with failures', [
                    'batchId' => $this->batchId,
                    'failedJobs' => $failedJobs,
                    'completedJobs' => $completedJobs,
                ]);
            }

            return;
        }

        // Still processing, dispatch another checker job to run after 1 second
        if ($this->checkCount < $this->maxChecks) {
            Log::debug('CheckBatchCompletion: dispatching next check', [
                'batchId' => $this->batchId,
                'checkCount' => $this->checkCount,
            ]);

            dispatch(new self($this->batchId, $this->totalJobs, $this->maxChecks))
                ->delay(1)
                ->onQueue('default');
        } else {
            // Timeout after 2 minutes
            Log::error('CheckBatchCompletion: batch processing timeout', [
                'batchId' => $this->batchId,
                'totalJobs' => $this->totalJobs,
                'totalProcessed' => $totalProcessed,
                'checkCount' => $this->checkCount,
            ]);

            Cache::put($metadataKey, array_merge($metadata, [
                'status' => 'failed',
                'error' => 'Batch processing timeout',
                'failedAt' => now(),
            ]), 24 * 60 * 60);
        }
    }
}
