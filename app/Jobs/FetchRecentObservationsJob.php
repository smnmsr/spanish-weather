<?php

namespace App\Jobs;

use App\Services\AemetService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Worker job to fetch recent observations from AEMET.
 */
class FetchRecentObservationsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $weatherBatchId,
        protected array $stationIds,
    ) {}

    public function handle(AemetService $aemet): void
    {
        try {
            Log::debug('FetchRecentObservationsJob: starting', ['batchId' => $this->weatherBatchId]);

            $observations = $aemet->getRecentObservations();

            Log::debug('FetchRecentObservationsJob: fetched observations', [
                'batchId' => $this->weatherBatchId,
                'totalObservations' => count($observations),
                'requestedStationIds' => $this->stationIds,
            ]);

            // Filter to only selected stations
            $filtered = array_filter($observations, function ($obs) {
                $stationId = $obs['idema'] ?? $obs['indicativo'] ?? null;

                return $stationId && in_array($stationId, $this->stationIds);
            });

            // Reindex array to avoid gaps in numeric keys
            $filtered = array_values($filtered);

            Log::debug('FetchRecentObservationsJob: filtered observations', [
                'batchId' => $this->weatherBatchId,
                'filteredCount' => count($filtered),
                'filteredStationIds' => array_map(fn ($obs) => $obs['idema'] ?? $obs['indicativo'] ?? 'unknown', $filtered),
            ]);

            $this->storeResult('current_observations', $filtered);
            $this->incrementCompletedCount();

            Log::info('FetchRecentObservationsJob: completed', [
                'batchId' => $this->weatherBatchId,
                'count' => count($filtered),
            ]);
        } catch (\Exception $e) {
            $this->recordJobFailure($e);
            throw $e;
        }
    }

    protected function storeResult(string $key, array $data): void
    {
        $resultsKey = "batch_results_{$this->weatherBatchId}";
        $results = Cache::get($resultsKey, []);
        $results[$key] = $data;
        Cache::put($resultsKey, $results, 24 * 60 * 60);
    }

    protected function recordJobFailure(\Exception $e): void
    {
        Log::error('FetchRecentObservationsJob failed', [
            'batchId' => $this->weatherBatchId,
            'error' => $e->getMessage(),
        ]);

        // Check if it's an AEMET API outage
        if ($this->isAemetOutage($e)) {
            $metadataKey = "batch_metadata_{$this->weatherBatchId}";
            $metadata = Cache::get($metadataKey, []);
            $metadata['aemetOutage'] = true;
            $metadata['error'] = 'AEMET API nicht erreichbar';
            Cache::put($metadataKey, $metadata, 24 * 60 * 60);
        }

        $this->incrementFailureCount();
    }

    protected function isAemetOutage(\Exception $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, '503')
            || str_contains($message, '500')
            || str_contains($message, 'timeout')
            || str_contains($message, 'Connection refused')
            || str_contains($message, 'Could not resolve host');
    }

    protected function incrementCompletedCount(): void
    {
        $metadataKey = "batch_metadata_{$this->weatherBatchId}";
        $metadata = Cache::get($metadataKey, []);
        $metadata['completedJobs'] = ($metadata['completedJobs'] ?? 0) + 1;
        Cache::put($metadataKey, $metadata, 24 * 60 * 60);

        Log::debug('FetchRecentObservationsJob: incremented completed count', [
            'batchId' => $this->weatherBatchId,
            'completedJobs' => $metadata['completedJobs'],
        ]);
    }

    protected function incrementFailureCount(): void
    {
        $metadataKey = "batch_metadata_{$this->weatherBatchId}";
        $metadata = Cache::get($metadataKey, []);
        $metadata['failedJobs'] = ($metadata['failedJobs'] ?? 0) + 1;
        Cache::put($metadataKey, $metadata, 24 * 60 * 60);
    }
}
