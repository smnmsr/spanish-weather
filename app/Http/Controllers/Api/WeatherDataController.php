<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\StartWeatherDataQueryBatch;
use App\Services\AemetService;
use App\Services\BatchResultsFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WeatherDataController extends Controller
{
    public function __construct(
        protected AemetService $aemet,
        protected BatchResultsFormatter $formatter
    ) {}

    /**
     * Get recent observational data for all stations.
     */
    public function recent(): JsonResponse
    {
        try {
            $data = $this->aemet->getRecentObservations();

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => count($data),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent observational data for a specific station.
     */
    public function station(string $stationId): JsonResponse
    {
        try {
            $data = $this->aemet->getStationObservations($stationId);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get daily climate data for a specific station and date range.
     */
    public function dailyClimate(Request $request): JsonResponse
    {
        $request->validate([
            'station_id' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $data = $this->aemet->getDailyClimateData(
                $request->input('station_id'),
                $request->input('start_date'),
                $request->input('end_date')
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => count($data),
                'period' => [
                    'start' => $request->input('start_date'),
                    'end' => $request->input('end_date'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get climate normals for a specific station.
     */
    public function normals(string $stationId): JsonResponse
    {
        try {
            $data = $this->aemet->getClimateNormals($stationId);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start an async batch weather query.
     * If all data is already cached, returns completed results immediately (synchronous).
     * If small batch, runs inline without queuing.
     * Otherwise dispatches to queue for background processing.
     */
    public function startBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:current-observations,daily-values,monthly-yearly-trends,extreme-values,climatological-normals,forecast',
            'stationIds' => 'required_unless:type,forecast|array|max:5',
            'stationIds.*' => 'required_with:stationIds|string',
            'municipalityIds' => 'required_if:type,forecast|array|max:5',
            'municipalityIds.*' => 'required_with:municipalityIds|string',
            'dateRange.startDate' => 'required_if:type,daily-values|date',
            'dateRange.endDate' => 'required_if:type,daily-values|date|after_or_equal:dateRange.startDate',
            'monthYearRange.month' => 'required_if:type,monthly-yearly-trends|integer|min:1|max:12',
            'monthYearRange.startYear' => 'required_if:type,monthly-yearly-trends|integer|min:1900',
            'monthYearRange.endYear' => 'required_if:type,monthly-yearly-trends|integer|min:1900',
        ]);

        try {
            $batchId = Str::ulid()->toString();

            // Check if all required data is already cached
            $allDataCached = $this->checkIfAllDataCached($validated);

            if ($allDataCached) {
                // Everything is cached; return results immediately (synchronous)
                Log::info('All data cached, returning results synchronously', [
                    'batchId' => $batchId,
                    'type' => $validated['type'],
                ]);

                // Initialize batch metadata so results endpoint can find it
                Cache::put(
                    "batch_metadata_{$batchId}",
                    [
                        'status' => 'completed',
                        'type' => $validated['type'],
                        'startedAt' => now(),
                        'completedAt' => now(),
                        'totalJobs' => 0,
                        'completedJobs' => 0,
                        'failedJobs' => 0,
                        'error' => null,
                        'options' => $validated,
                    ],
                    24 * 60 * 60
                );

                // Initialize results cache
                Cache::put("batch_results_{$batchId}", [], 24 * 60 * 60);

                // Create inline orchestrator to populate results
                $orchestrator = new StartWeatherDataQueryBatch($batchId, $validated);
                $orchestrator->handle();

                // Return completed status with results
                return response()->json([
                    'batchId' => $batchId,
                    'status' => 'completed',
                    'completedImmediately' => true,
                ]);
            }

            // Not all cached; proceed with queued orchestrator
            Cache::put(
                "batch_metadata_{$batchId}",
                [
                    'status' => 'queued',
                    'type' => $validated['type'],
                    'startedAt' => now(),
                    'totalJobs' => 0,
                    'completedJobs' => 0,
                    'failedJobs' => 0,
                    'error' => null,
                    'options' => $validated,
                ],
                24 * 60 * 60
            );

            // Initialize batch results cache
            Cache::put("batch_results_{$batchId}", [], 24 * 60 * 60);

            // Dispatch the orchestrator job
            StartWeatherDataQueryBatch::dispatch($batchId, $validated);

            Log::info('Started weather data batch query', [
                'batchId' => $batchId,
                'type' => $validated['type'],
            ]);

            return response()->json([
                'batchId' => $batchId,
                'status' => 'queued',
            ]);
        } catch (\Exception $e) {
            Log::error('Error starting batch query', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to start batch query',
            ], 500);
        }
    }

    /**
     * Get the progress of a batch query.
     */
    public function batchProgress(string $batchId): JsonResponse
    {
        $metadata = Cache::get("batch_metadata_{$batchId}");

        if (! $metadata) {
            return response()->json([
                'error' => 'Batch not found or expired',
            ], 404);
        }

        $status = $metadata['status'] ?? 'unknown';
        $totalJobs = $metadata['totalJobs'] ?? 0;
        $completedJobs = $metadata['completedJobs'] ?? 0;
        $failedJobs = $metadata['failedJobs'] ?? 0;

        $percent = $totalJobs > 0 ? (int) (($completedJobs / $totalJobs) * 100) : 0;

        return response()->json([
            'batchId' => $batchId,
            'status' => $status,
            'percent' => $percent,
            'totalJobs' => $totalJobs,
            'completedJobs' => $completedJobs,
            'failedJobs' => $failedJobs,
            'error' => $metadata['error'] ?? null,
            'aemetOutage' => $metadata['aemetOutage'] ?? false,
        ]);
    }

    /**
     * Get the results of a completed batch query.
     */
    public function batchResults(string $batchId): JsonResponse
    {
        $metadata = Cache::get("batch_metadata_{$batchId}");

        if (! $metadata) {
            return response()->json([
                'error' => 'Batch not found or expired',
            ], 404);
        }

        $status = $metadata['status'] ?? 'unknown';

        // Only return results if batch is completed
        if ($status !== 'completed' && $status !== 'failed') {
            return response()->json([
                'error' => 'Batch is still processing',
                'status' => $status,
            ], 202); // 202 Accepted - still processing
        }

        $results = Cache::get("batch_results_{$batchId}", []);

        $options = $metadata['options'] ?? [];
        $type = $metadata['type'] ?? null;

        Log::info('Formatting batch results', [
            'batchId' => $batchId,
            'type' => $type,
            'options' => $options,
            'resultsCount' => count($results),
        ]);

        $formatted = match ($type) {
            'current-observations' => $this->formatter->formatCurrentObservations($batchId, $options['stationIds'] ?? []),
            'daily-values' => $this->formatter->formatDailyValues($batchId, $options['stationIds'] ?? []),
            'monthly-yearly-trends' => $this->formatter->formatMonthlyYearlyTrends($batchId, $options['stationIds'] ?? []),
            'extreme-values' => $this->formatter->formatExtremeValues($batchId, $options['stationIds'] ?? []),
            'climatological-normals' => $this->formatter->formatClimatologicalNormals($batchId, $options['stationIds'] ?? []),
            'forecast' => $this->formatter->formatForecast($batchId, $options['municipalityIds'] ?? []),
            default => [
                'queryType' => $type,
                'observations' => [],
                'stations' => [],
                'selectedStationIds' => [],
            ],
        };

        Log::info('Batch results formatted', [
            'batchId' => $batchId,
            'formattedKeys' => array_keys($formatted),
        ]);

        return response()->json([
            'batchId' => $batchId,
            'status' => $status,
            'results' => $formatted,
            'failedJobs' => $metadata['failedJobs'] ?? 0,
        ]);
    }

    /**
     * Cancel a batch query.
     */
    public function batchCancel(string $batchId): JsonResponse
    {
        $metadata = Cache::get("batch_metadata_{$batchId}");

        if (! $metadata) {
            return response()->json([
                'error' => 'Batch not found',
            ], 404);
        }

        // Mark as cancelled
        $metadata['status'] = 'cancelled';
        $metadata['cancelledAt'] = now();
        Cache::put("batch_metadata_{$batchId}", $metadata, 24 * 60 * 60);

        Log::info('Batch query cancelled', [
            'batchId' => $batchId,
        ]);

        return response()->json([
            'batchId' => $batchId,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Check if all required data for a query is already cached.
     * Returns true if no API calls are needed.
     */
    protected function checkIfAllDataCached(array $validated): bool
    {
        $type = $validated['type'];

        switch ($type) {
            case 'current-observations':
                return Cache::has('aemet_recent_observations');

            case 'daily-values':
                $stationIds = $validated['stationIds'] ?? [];
                $startDate = $validated['dateRange']['startDate'] ?? null;
                $endDate = $validated['dateRange']['endDate'] ?? null;

                foreach ($stationIds as $stationId) {
                    $cacheKey = "aemet_daily_climate_{$stationId}_{$startDate}_{$endDate}";
                    if (! Cache::has($cacheKey)) {
                        return false;
                    }
                }

                return true;

            case 'monthly-yearly-trends':
                $stationIds = $validated['stationIds'] ?? [];
                $startYear = $validated['monthYearRange']['startYear'] ?? null;
                $endYear = $validated['monthYearRange']['endYear'] ?? null;

                for ($year = $startYear; $year <= $endYear; $year++) {
                    foreach ($stationIds as $stationId) {
                        $cacheKey = "aemet_monthly_annual_{$stationId}_{$year}";
                        if (! Cache::has($cacheKey)) {
                            return false;
                        }
                    }
                }

                return true;

            case 'extreme-values':
                $stationIds = $validated['stationIds'] ?? [];

                foreach ($stationIds as $stationId) {
                    $cacheKey = "aemet_extreme_values_{$stationId}";
                    if (! Cache::has($cacheKey)) {
                        return false;
                    }
                }

                return true;

            case 'climatological-normals':
                $stationIds = $validated['stationIds'] ?? [];

                foreach ($stationIds as $stationId) {
                    $cacheKey = "aemet_climate_normals_{$stationId}";
                    if (! Cache::has($cacheKey)) {
                        return false;
                    }
                }

                return true;

            case 'forecast':
                $municipalityIds = $validated['municipalityIds'] ?? [];

                foreach ($municipalityIds as $municipalityId) {
                    $cacheKey = "aemet_forecast_municipality_{$municipalityId}";
                    if (! Cache::has($cacheKey)) {
                        return false;
                    }
                }

                return true;

            default:
                return false;
        }
    }
}
