<?php

namespace App\Http\Controllers;

use App\Services\AemetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class StationController
{
    public function __construct(public AemetService $aemet) {}

    public function index(Request $request): Response
    {
        $stations = $this->aemet->getAllStations();

        $mapped = [];
        foreach ($stations as $station) {
            if (! isset($station['latitud'], $station['longitud'])) {
                continue;
            }

            $lat = $this->aemet->parseCoordinate((string) $station['latitud']);
            $lon = $this->aemet->parseCoordinate((string) $station['longitud']);

            // Skip invalid coordinates (0.0 indicates parsing failed)
            if ($lat === 0.0 || $lon === 0.0) {
                continue;
            }

            $mapped[] = [
                'id' => $station['idema'] ?? $station['indicativo'] ?? null,
                'name' => $station['nombre'] ?? $station['ub'] ?? 'Station',
                'lat' => $lat,
                'lon' => $lon,
                'provincia' => $station['provincia'] ?? null,
                'altitude' => isset($station['altitud']) ? (int) $station['altitud'] : null,
            ];
        }

        $selectedStations = session('selected_stations', []);

        return Inertia::render('Stations/Tool', [
            'stations' => $mapped,
            'selectedStations' => $selectedStations,
        ]);
    }

    public function saveSelection(Request $request)
    {
        $stations = $request->input('stations', []);
        session(['selected_stations' => $stations]);

        return redirect()->route('home')->with('success', 'Auswahl gespeichert!');
    }

    public function queryData(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:current-observations,daily-values,monthly-yearly-trends,extreme-values,climatological-normals',
            'stationIds' => 'required|array|max:5',
            'stationIds.*' => 'required|string',
            'dateRange.startDate' => 'required_if:type,daily-values|date',
            'dateRange.endDate' => 'required_if:type,daily-values|date|after_or_equal:dateRange.startDate',
        ]);

        $type = $validated['type'];
        $stationIds = $validated['stationIds'];

        if ($type === 'current-observations') {
            $observations = $this->aemet->getRecentObservations();

            // Filter to only selected stations
            $filteredObservations = array_filter($observations, function ($observation) use ($stationIds) {
                $stationId = $observation['idema'] ?? null;

                return $stationId && in_array($stationId, $stationIds);
            });

            // Get station details for the selected stations
            $allStations = $this->aemet->getAllStations();
            $stationDetails = [];

            foreach ($allStations as $station) {
                $stationId = $station['idema'] ?? $station['indicativo'] ?? null;
                if ($stationId && in_array($stationId, $stationIds)) {
                    $stationDetails[$stationId] = [
                        'id' => $stationId,
                        'name' => $station['nombre'] ?? $station['ub'] ?? 'Unknown',
                        'provincia' => $station['provincia'] ?? null,
                    ];
                }
            }

            return response()->json([
                'queryType' => $type,
                'observations' => array_values($filteredObservations),
                'stations' => $stationDetails,
                'selectedStationIds' => $stationIds,
            ]);
        }

        if ($type === 'daily-values') {
            $startDate = data_get($validated, 'dateRange.startDate');
            $endDate = data_get($validated, 'dateRange.endDate');

            $allStations = $this->aemet->getAllStations();
            $stationDetails = [];

            foreach ($allStations as $station) {
                $stationId = $station['idema'] ?? $station['indicativo'] ?? null;

                if ($stationId && in_array($stationId, $stationIds)) {
                    $stationDetails[$stationId] = [
                        'id' => $stationId,
                        'name' => $station['nombre'] ?? $station['ub'] ?? 'Unknown',
                        'provincia' => $station['provincia'] ?? null,
                    ];
                }
            }

            $dailyData = [];

            try {
                foreach ($stationIds as $stationId) {
                    $data = $this->aemet->getDailyClimateData($stationId, (string) $startDate, (string) $endDate);

                    $normalized = array_map(function (array $entry) use ($stationId) {
                        return array_merge($entry, [
                            'idema' => $entry['idema'] ?? $entry['indicativo'] ?? $stationId,
                        ]);
                    }, $data);

                    $dailyData = array_merge($dailyData, $normalized);
                }
            } catch (\RuntimeException $e) {
                Log::error('Error fetching daily climate data', [
                    'message' => $e->getMessage(),
                    'stationIds' => $stationIds,
                    'dateRange' => [$startDate, $endDate],
                ]);

                return response()->json([
                    'error' => 'AEMET API is currently unavailable. Please try again in a few minutes.',
                    'type' => 'api_outage',
                ], 503);
            }

            return response()->json([
                'queryType' => $type,
                'observations' => $dailyData,
                'stations' => $stationDetails,
                'selectedStationIds' => $stationIds,
                'dateRange' => [
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ],
            ]);
        }

        // TODO: Handle other query types
        return response()->json([
            'error' => 'Dieser Datentyp wird noch nicht unterstützt.',
        ], 400);
    }
}
