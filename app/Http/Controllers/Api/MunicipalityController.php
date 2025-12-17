<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AemetService;
use Illuminate\Http\JsonResponse;

class MunicipalityController extends Controller
{
    public function __construct(
        protected AemetService $aemet
    ) {}

    /**
     * Get all municipalities from AEMET with province information.
     */
    public function index(): JsonResponse
    {
        try {
            $municipalities = $this->aemet->getMunicipalitiesByProvince();

            return response()->json([
                'success' => true,
                'data' => $municipalities,
                'count' => count($municipalities),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
