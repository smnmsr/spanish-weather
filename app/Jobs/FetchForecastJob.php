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
 * Worker job to fetch forecast data for a specific municipality.
 */
class FetchForecastJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $weatherBatchId,
        protected string $municipalityId,
    ) {}

    public function handle(AemetService $aemet): void
    {
        try {
            Log::debug('FetchForecastJob: starting', [
                'batchId' => $this->weatherBatchId,
                'municipalityId' => $this->municipalityId,
            ]);

            $data = $aemet->getMunicipalityDailyForecast($this->municipalityId);

            $this->storeMunicipalityResult($data);
            $this->incrementCompletedCount();

            Log::info('FetchForecastJob: completed', [
                'batchId' => $this->weatherBatchId,
                'municipalityId' => $this->municipalityId,
            ]);
        } catch (\Exception $e) {
            $this->recordJobFailure($e);
            throw $e;
        }
    }

    protected function storeMunicipalityResult(array $data): void
    {
        $resultsKey = "batch_results_{$this->weatherBatchId}";
        $results = Cache::get($resultsKey, []);

        if (! isset($results['forecast_by_municipality'])) {
            $results['forecast_by_municipality'] = [];
        }

        $results['forecast_by_municipality'][$this->municipalityId] = $data;
        Cache::put($resultsKey, $results, 24 * 60 * 60);
    }

    protected function recordJobFailure(\Exception $e): void
    {
        Log::error('FetchForecastJob failed', [
            'batchId' => $this->weatherBatchId,
            'municipalityId' => $this->municipalityId,
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

        Log::debug('FetchForecastJob: incremented completed count', [
            'batchId' => $this->weatherBatchId,
            'municipalityId' => $this->municipalityId,
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
