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
 * Worker job to fetch monthly/yearly trends for a specific station and year.
 * Fetches per-year to maximize cache reuse across queries.
 */
class FetchMonthlyYearlyTrendsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $weatherBatchId,
        protected string $stationId,
        protected int $year,
        protected int $month,
    ) {}

    public function handle(AemetService $aemet): void
    {
        try {
            Log::debug('FetchMonthlyYearlyTrendsJob: starting', [
                'batchId' => $this->weatherBatchId,
                'stationId' => $this->stationId,
                'year' => $this->year,
            ]);

            // Fetch data for the full year (cached per-year)
            $data = $aemet->getMonthlyAnnualDataForYear($this->stationId, $this->year);

            // Filter to only the requested month
            $filtered = array_filter($data, function (array $entry) {
                $fecha = $entry['fecha'] ?? '';
                if (empty($fecha)) {
                    return false;
                }

                // Extract month from YYYY-MM format
                $parts = explode('-', $fecha);
                if (count($parts) !== 2) {
                    return false;
                }

                $entryMonth = (int) $parts[1];

                return $entryMonth === $this->month;
            });

            // Normalize and ensure idema is set
            $normalized = array_map(function (array $entry) {
                return array_merge($entry, [
                    'idema' => $entry['idema'] ?? $entry['indicativo'] ?? $this->stationId,
                ]);
            }, $filtered);

            $this->storeStationYearResult($normalized);
            $this->incrementCompletedCount();

            Log::info('FetchMonthlyYearlyTrendsJob: completed', [
                'batchId' => $this->weatherBatchId,
                'stationId' => $this->stationId,
                'year' => $this->year,
                'month' => $this->month,
                'count' => count($normalized),
            ]);
        } catch (\Exception $e) {
            $this->recordJobFailure($e);
            throw $e;
        }
    }

    protected function storeStationYearResult(array $data): void
    {
        $resultsKey = "batch_results_{$this->weatherBatchId}";
        $results = Cache::get($resultsKey, []);

        if (! isset($results['monthly_yearly_trends_by_station'])) {
            $results['monthly_yearly_trends_by_station'] = [];
        }

        $stationKey = "{$this->stationId}_{$this->year}";
        if (! isset($results['monthly_yearly_trends_by_station'][$stationKey])) {
            $results['monthly_yearly_trends_by_station'][$stationKey] = [];
        }

        $results['monthly_yearly_trends_by_station'][$stationKey] = array_merge(
            $results['monthly_yearly_trends_by_station'][$stationKey],
            $data
        );

        Cache::put($resultsKey, $results, 24 * 60 * 60);
    }

    protected function recordJobFailure(\Exception $e): void
    {
        Log::error('FetchMonthlyYearlyTrendsJob failed', [
            'batchId' => $this->weatherBatchId,
            'stationId' => $this->stationId,
            'year' => $this->year,
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

        Log::debug('FetchMonthlyYearlyTrendsJob: incremented completed count', [
            'batchId' => $this->weatherBatchId,
            'stationId' => $this->stationId,
            'year' => $this->year,
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
