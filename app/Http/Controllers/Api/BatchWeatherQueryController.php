<?php

namespace App\Http\Controllers\Api;

use App\Jobs\StartWeatherDataQueryBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles async batch weather data queries.
 */
class BatchWeatherQueryController
{
    /**
     * Start a new weather data query batch.
     * Returns a batchId for polling progress and retrieving results.
     */
    public function start(Request $request): JsonResponse
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
    public function progress(string $batchId): JsonResponse
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
        ]);
    }

    /**
     * Get the results of a completed batch query.
     */
    public function results(string $batchId): JsonResponse
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

        return response()->json([
            'batchId' => $batchId,
            'status' => $status,
            'results' => $results,
            'failedJobs' => $metadata['failedJobs'] ?? 0,
        ]);
    }

    /**
     * Cancel a batch query.
     */
    public function cancel(string $batchId): JsonResponse
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
}
