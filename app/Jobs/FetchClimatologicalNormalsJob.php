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
 * Worker job to fetch climatological normals for a specific station.
 */
class FetchClimatologicalNormalsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $weatherBatchId,
        protected string $stationId,
    ) {}

    public function handle(AemetService $aemet): void
    {
        try {
            Log::debug('FetchClimatologicalNormalsJob: starting', [
                'batchId' => $this->weatherBatchId,
                'stationId' => $this->stationId,
            ]);

            $data = $aemet->getClimateNormals($this->stationId);

            $this->storeStationResult($data);
            $this->incrementCompletedCount();

            Log::info('FetchClimatologicalNormalsJob: completed', [
                'batchId' => $this->weatherBatchId,
                'stationId' => $this->stationId,
            ]);
        } catch (\Exception $e) {
            $this->recordJobFailure($e);
            throw $e;
        }
    }

    protected function storeStationResult(array $data): void
    {
        $resultsKey = "batch_results_{$this->weatherBatchId}";
        $results = Cache::get($resultsKey, []);

        if (! isset($results['climatological_normals_by_station'])) {
            $results['climatological_normals_by_station'] = [];
        }

        $results['climatological_normals_by_station'][$this->stationId] = $data;
        Cache::put($resultsKey, $results, 24 * 60 * 60);
    }

    protected function recordJobFailure(\Exception $e): void
    {
        Log::error('FetchClimatologicalNormalsJob failed', [
            'batchId' => $this->weatherBatchId,
            'stationId' => $this->stationId,
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

        Log::debug('FetchClimatologicalNormalsJob: incremented completed count', [
            'batchId' => $this->weatherBatchId,
            'stationId' => $this->stationId,
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
