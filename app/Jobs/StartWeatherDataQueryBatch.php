<?php

namespace App\Jobs;

use App\Services\AemetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrator job that determines what API requests need to be made
 * and creates a batch of worker jobs to fetch them.
 */
class StartWeatherDataQueryBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $batchId;

    protected array $options;

    /**
     * When the remaining queueable jobs are at or below this threshold, run them inline
     * to avoid queue overhead for already-cached or tiny batches.
     * Loaded from config at construction time.
     */
    protected int $inlineThreshold;

    public function __construct(
        string $batchId,
        array $options
    ) {
        $this->batchId = $batchId;
        $this->options = $options;
        $this->inlineThreshold = (int) config('aemet.batch.sync_threshold', 5);
    }

    public function handle(): void
    {
        Log::info('Starting weather data query batch', [
            'batchId' => $this->batchId,
            'type' => $this->options['type'] ?? null,
        ]);

        // Store batch metadata
        $this->storeBatchMetadata([
            'status' => 'orchestrating',
            'type' => $this->options['type'],
            'startedAt' => now(),
            'totalJobs' => 0,
            'completedJobs' => 0,
            'failedJobs' => 0,
            'error' => null,
        ]);

        try {
            $jobs = $this->determineRequiredJobs();

            if (empty($jobs)) {
                $this->storeBatchMetadata([
                    'status' => 'completed',
                    'completedAt' => now(),
                    'totalJobs' => 0,
                    'completedJobs' => 0,
                ]);

                Log::info('Weather data query batch had no jobs to execute', [
                    'batchId' => $this->batchId,
                ]);

                return;
            }

            $totalJobs = count($jobs);

            // Update job count early so progress math works even for inline jobs
            $this->storeBatchMetadata([
                'status' => 'processing',
                'totalJobs' => $totalJobs,
            ]);

            $aemet = app(AemetService::class);

            // Separate jobs into cached and uncached
            $inlineJobs = [];
            $queueableJobs = [];

            foreach ($jobs as $jobDef) {
                $cacheKey = $jobDef['cacheKey'] ?? null;
                $job = $jobDef['job'];

                if ($cacheKey && Cache::has($cacheKey)) {
                    // Cached data exists; run inline to avoid queue overhead
                    $inlineJobs[] = $job;

                    continue;
                }

                $queueableJobs[] = $job;
            }

            // Decision logic based on remaining uncached jobs:
            // 1. If ALL jobs are cached, complete immediately
            // 2. If remaining jobs <= threshold, run inline
            // 3. Otherwise, dispatch to queue

            $remainingUncachedJobs = count($queueableJobs);

            if ($remainingUncachedJobs === 0) {
                // Everything is cached! Run all inline and complete immediately.
                Log::info('All jobs are cached, executing inline', [
                    'batchId' => $this->batchId,
                    'totalJobs' => $totalJobs,
                ]);

                foreach ($inlineJobs as $job) {
                    $job->handle($aemet);
                }

                $this->storeBatchMetadata([
                    'status' => 'completed',
                    'completedAt' => now(),
                    'completedJobs' => $totalJobs,
                ]);

                return;
            }

            if ($remainingUncachedJobs <= $this->inlineThreshold) {
                // Small batch; run everything inline
                Log::info('Small batch, executing all inline', [
                    'batchId' => $this->batchId,
                    'totalJobs' => $totalJobs,
                    'cachedJobs' => count($inlineJobs),
                    'uncachedJobs' => $remainingUncachedJobs,
                    'threshold' => $this->inlineThreshold,
                ]);

                $inlineJobs = array_merge($inlineJobs, $queueableJobs);

                foreach ($inlineJobs as $job) {
                    $job->handle($aemet);
                }

                $this->storeBatchMetadata([
                    'status' => 'completed',
                    'completedAt' => now(),
                    'completedJobs' => $totalJobs,
                ]);

                return;
            }

            // Large batch with uncached data; dispatch to queue
            Log::info('Large batch, dispatching to queue', [
                'batchId' => $this->batchId,
                'totalJobs' => $totalJobs,
                'cachedJobs' => count($inlineJobs),
                'queuedJobs' => $remainingUncachedJobs,
            ]);

            // Execute cached jobs inline first
            foreach ($inlineJobs as $job) {
                $job->handle($aemet);
            }

            // Dispatch uncached jobs to queue
            foreach ($queueableJobs as $job) {
                dispatch($job)->onQueue('default');
            }

            // Dispatch a checker job to monitor completion
            dispatch(new CheckBatchCompletion($this->batchId, $totalJobs))
                ->delay(1) // Start checking after 1 second
                ->onQueue('default');
        } catch (\Exception $e) {
            Log::error('Error orchestrating weather data batch', [
                'batchId' => $this->batchId,
                'error' => $e->getMessage(),
            ]);

            $this->storeBatchMetadata([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'failedAt' => now(),
            ]);
        }
    }

    /**
     * Determine which API requests need to be made and which are already cached.
     *
     * @return array Array of job definitions with job instance and cacheKey
     */
    protected function determineRequiredJobs(): array
    {
        $type = $this->options['type'];
        $jobs = [];

        switch ($type) {
            case 'current-observations':
                $jobs = $this->jobsForCurrentObservations();
                break;

            case 'daily-values':
                $jobs = $this->jobsForDailyValues();
                break;

            case 'monthly-yearly-trends':
                $jobs = $this->jobsForMonthlyYearlyTrends();
                break;

            case 'extreme-values':
                $jobs = $this->jobsForExtremeValues();
                break;

            case 'climatological-normals':
                $jobs = $this->jobsForClimatologicalNormals();
                break;

            case 'forecast':
                $jobs = $this->jobsForForecast();
                break;
        }

        return $jobs;
    }

    /**
     * Current observations require only a single fetch (not split by station).
     */
    protected function jobsForCurrentObservations(): array
    {
        $stationIds = $this->options['stationIds'] ?? [];

        // Only one job needed to fetch all recent observations
        return [
            [
                'job' => new FetchRecentObservationsJob($this->batchId, $stationIds),
                'cacheKey' => 'aemet_recent_observations',
            ],
        ];
    }

    /**
     * Daily values require per-station fetch jobs.
     */
    protected function jobsForDailyValues(): array
    {
        $stationIds = $this->options['stationIds'] ?? [];
        $startDate = $this->options['dateRange']['startDate'] ?? null;
        $endDate = $this->options['dateRange']['endDate'] ?? null;

        $jobs = [];
        foreach ($stationIds as $stationId) {
            $jobs[] = [
                'job' => new FetchDailyValuesJob(
                    $this->batchId,
                    $stationId,
                    $startDate,
                    $endDate
                ),
                'cacheKey' => "aemet_daily_climate_{$stationId}_{$startDate}_{$endDate}",
            ];
        }

        return $jobs;
    }

    /**
     * Monthly-yearly trends split by year (not 3-year chunks).
     * This maximizes cache reuse across queries.
     */
    protected function jobsForMonthlyYearlyTrends(): array
    {
        $stationIds = $this->options['stationIds'] ?? [];
        $month = $this->options['monthYearRange']['month'] ?? null;
        $startYear = $this->options['monthYearRange']['startYear'] ?? null;
        $endYear = $this->options['monthYearRange']['endYear'] ?? null;

        $jobs = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            foreach ($stationIds as $stationId) {
                $jobs[] = [
                    'job' => new FetchMonthlyYearlyTrendsJob(
                        $this->batchId,
                        $stationId,
                        $year,
                        $month
                    ),
                    'cacheKey' => "aemet_monthly_annual_{$stationId}_{$year}",
                ];
            }
        }

        return $jobs;
    }

    /**
     * Extreme values require per-station fetch jobs.
     */
    protected function jobsForExtremeValues(): array
    {
        $stationIds = $this->options['stationIds'] ?? [];

        $jobs = [];
        foreach ($stationIds as $stationId) {
            $jobs[] = [
                'job' => new FetchExtremeValuesJob($this->batchId, $stationId),
                'cacheKey' => "aemet_extreme_values_{$stationId}",
            ];
        }

        return $jobs;
    }

    /**
     * Climatological normals require per-station fetch jobs.
     */
    protected function jobsForClimatologicalNormals(): array
    {
        $stationIds = $this->options['stationIds'] ?? [];

        $jobs = [];
        foreach ($stationIds as $stationId) {
            $jobs[] = [
                'job' => new FetchClimatologicalNormalsJob($this->batchId, $stationId),
                'cacheKey' => "aemet_climate_normals_{$stationId}",
            ];
        }

        return $jobs;
    }

    /**
     * Forecast requires per-municipality fetch jobs.
     */
    protected function jobsForForecast(): array
    {
        $municipalityIds = $this->options['municipalityIds'] ?? [];

        $jobs = [];
        foreach ($municipalityIds as $municipalityId) {
            $jobs[] = [
                'job' => new FetchForecastJob($this->batchId, $municipalityId),
                'cacheKey' => "aemet_forecast_municipality_{$municipalityId}",
            ];
        }

        return $jobs;
    }

    protected function storeBatchMetadata(array $metadata): void
    {
        $cacheKey = "batch_metadata_{$this->batchId}";
        $existingMetadata = Cache::get($cacheKey, []);
        $merged = array_merge($existingMetadata, $metadata);

        // Store for 24 hours
        Cache::put($cacheKey, $merged, 24 * 60 * 60);
    }
}
