<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AemetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherDataController extends Controller
{
    public function __construct(
        protected AemetService $aemet
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
}
