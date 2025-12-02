<?php

namespace App\Http\Controllers;

use App\Services\AemetService;
use Illuminate\Http\Request;
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
            ];
        }

        return Inertia::render('Stations/Map', [
            'stations' => $mapped,
        ]);
    }
}
