<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AemetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherStationsController extends Controller
{
    public function __construct(
        protected AemetService $aemet
    ) {}

    /**
     * Get all weather stations.
     */
    public function index(): JsonResponse
    {
        try {
            $stations = $this->aemet->getAllStations();

            return response()->json([
                'success' => true,
                'data' => $stations,
                'count' => count($stations),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find the nearest station to given coordinates.
     */
    public function nearest(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $station = $this->aemet->findNearestStation(
                $request->input('latitude'),
                $request->input('longitude')
            );

            if (! $station) {
                return response()->json([
                    'success' => false,
                    'error' => 'No station found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $station,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
