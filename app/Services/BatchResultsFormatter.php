<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Helper to transform batch results into controller response format.
 */
class BatchResultsFormatter
{
    public function __construct(protected AemetService $aemet) {}

    /**
     * Format batch results for a monthly-yearly-trends query.
     */
    public function formatMonthlyYearlyTrends(string $batchId, array $stationIds): array
    {
        $results = Cache::get("batch_results_{$batchId}", []);
        $stationDetails = [];
        $allObservations = [];

        // Get station details
        $stations = $this->aemet->getAllStations();
        foreach ($stations as $station) {
            $stationId = $station['idema'] ?? $station['indicativo'] ?? null;

            if ($stationId && in_array($stationId, $stationIds)) {
                $stationDetails[$stationId] = [
                    'id' => $stationId,
                    'name' => $station['nombre'] ?? $station['ub'] ?? 'Unknown',
                    'provincia' => $station['provincia'] ?? null,
                ];
            }
        }

        // Flatten monthly-yearly trends from the nested structure
        $monthlyTrendsByStation = $results['monthly_yearly_trends_by_station'] ?? [];
        foreach ($monthlyTrendsByStation as $data) {
            $allObservations = array_merge($allObservations, $data);
        }

        // Retrieve monthYearRange from batch metadata (options)
        $metadata = Cache::get("batch_metadata_{$batchId}");
        $monthYearRange = null;
        if ($metadata && isset($metadata['options']['monthYearRange'])) {
            $monthYearRange = $metadata['options']['monthYearRange'];
        }

        return [
            'queryType' => 'monthly-yearly-trends',
            'observations' => $allObservations,
            'stations' => $stationDetails,
            'selectedStationIds' => $stationIds,
            'monthYearRange' => $monthYearRange,
        ];
    }

    /**
     * Format batch results for a daily-values query.
     */
    public function formatDailyValues(string $batchId, array $stationIds): array
    {
        $results = Cache::get("batch_results_{$batchId}", []);
        $stationDetails = [];
        $allObservations = [];

        // Get station details
        $stations = $this->aemet->getAllStations();
        foreach ($stations as $station) {
            $stationId = $station['idema'] ?? $station['indicativo'] ?? null;

            if ($stationId && in_array($stationId, $stationIds)) {
                $stationDetails[$stationId] = [
                    'id' => $stationId,
                    'name' => $station['nombre'] ?? $station['ub'] ?? 'Unknown',
                    'provincia' => $station['provincia'] ?? null,
                ];
            }
        }

        // Flatten daily values from the nested structure
        $dailyValuesByStation = $results['daily_values_by_station'] ?? [];
        foreach ($dailyValuesByStation as $data) {
            $allObservations = array_merge($allObservations, $data);
        }

        return [
            'queryType' => 'daily-values',
            'observations' => $allObservations,
            'stations' => $stationDetails,
            'selectedStationIds' => $stationIds,
        ];
    }

    /**
     * Format batch results for a current-observations query.
     */
    public function formatCurrentObservations(string $batchId, array $stationIds): array
    {
        $results = Cache::get("batch_results_{$batchId}", []);
        $stationDetails = [];

        $stations = $this->aemet->getAllStations();
        foreach ($stations as $station) {
            $stationId = $station['idema'] ?? $station['indicativo'] ?? null;

            if ($stationId && in_array($stationId, $stationIds)) {
                $stationDetails[$stationId] = [
                    'id' => $stationId,
                    'name' => $station['nombre'] ?? $station['ub'] ?? 'Unknown',
                    'provincia' => $station['provincia'] ?? null,
                ];
            }
        }

        $observations = $results['current_observations'] ?? [];

        return [
            'queryType' => 'current-observations',
            'observations' => $observations,
            'stations' => $stationDetails,
            'selectedStationIds' => $stationIds,
        ];
    }

    /**
     * Format batch results for an extreme-values query.
     */
    public function formatExtremeValues(string $batchId, array $stationIds): array
    {
        $results = Cache::get("batch_results_{$batchId}", []);
        $stationDetails = [];

        $stations = $this->aemet->getAllStations();
        foreach ($stations as $station) {
            $stationId = $station['idema'] ?? $station['indicativo'] ?? null;

            if ($stationId && in_array($stationId, $stationIds)) {
                $stationDetails[$stationId] = [
                    'id' => $stationId,
                    'name' => $station['nombre'] ?? $station['ub'] ?? 'Unknown',
                    'provincia' => $station['provincia'] ?? null,
                ];
            }
        }

        $extremeValuesByStation = $results['extreme_values_by_station'] ?? [];
        $flattened = [];

        foreach ($extremeValuesByStation as $stationId => $recordsByDimension) {
            foreach ($recordsByDimension as $record) {
                $flattened[] = array_merge($record, [
                    'idema' => $record['indicativo'] ?? $stationId,
                    'dimension' => $record['dimension'] ?? $record['dimensionKey'] ?? null,
                ]);
            }
        }

        return [
            'queryType' => 'extreme-values',
            'observations' => $flattened,
            'stations' => $stationDetails,
            'selectedStationIds' => $stationIds,
        ];
    }

    /**
     * Format batch results for a climatological-normals query.
     */
    public function formatClimatologicalNormals(string $batchId, array $stationIds): array
    {
        $results = Cache::get("batch_results_{$batchId}", []);
        $stationDetails = [];

        $stations = $this->aemet->getAllStations();
        foreach ($stations as $station) {
            $stationId = $station['idema'] ?? $station['indicativo'] ?? null;

            if ($stationId && in_array($stationId, $stationIds)) {
                $stationDetails[$stationId] = [
                    'id' => $stationId,
                    'name' => $station['nombre'] ?? $station['ub'] ?? 'Unknown',
                    'provincia' => $station['provincia'] ?? null,
                ];
            }
        }

        $normalsByStation = $results['climatological_normals_by_station'] ?? [];
        $flattened = [];

        foreach ($normalsByStation as $stationId => $entries) {
            foreach ($entries as $entry) {
                $flattened[] = array_merge($entry, [
                    'idema' => $entry['idema'] ?? $entry['indicativo'] ?? $stationId,
                ]);
            }
        }

        return [
            'queryType' => 'climatological-normals',
            'observations' => $flattened,
            'stations' => $stationDetails,
            'selectedStationIds' => $stationIds,
        ];
    }

    /**
     * Format batch results for a forecast query.
     */
    public function formatForecast(string $batchId, array $municipalityIds): array
    {
        $results = Cache::get("batch_results_{$batchId}", []);
        $municipalities = $this->aemet->getAllMunicipalities();
        $stationsInfo = [];
        $allForecasts = [];

        $forecastByMunicipality = $results['forecast_by_municipality'] ?? [];

        foreach ($forecastByMunicipality as $municipalityId => $forecastData) {
            // Forecast response is typically an array with a single element
            $forecastRecord = is_array($forecastData) && array_is_list($forecastData)
                ? ($forecastData[0] ?? [])
                : $forecastData;

            $days = data_get($forecastRecord, 'prediccion.dia', []);
            $locationName = $forecastRecord['nombre'] ?? $forecastRecord['municipio'] ?? 'Unknown';
            $locationProvince = $forecastRecord['provincia'] ?? null;

            $averageFromEntries = function (array $entries, string $key): ?float {
                $values = array_filter(array_map(function ($entry) use ($key) {
                    if (! isset($entry[$key])) {
                        return null;
                    }

                    return is_numeric($entry[$key]) ? (float) $entry[$key] : null;
                }, $entries), fn ($value) => $value !== null);

                if (count($values) === 0) {
                    return null;
                }

                return array_sum($values) / count($values);
            };

            foreach ($days as $day) {
                $date = $day['fecha'] ?? null;
                if (! $date) {
                    continue;
                }

                $temperature = $day['temperatura'] ?? [];
                $humidityEntries = $day['humedadRelativa'] ?? [];
                $windEntries = $day['viento'] ?? [];
                $precipEntries = $day['probPrecipitacion'] ?? [];

                $tempMax = isset($temperature['maxima']) && is_numeric($temperature['maxima'])
                    ? (float) $temperature['maxima']
                    : null;
                $tempMin = isset($temperature['minima']) && is_numeric($temperature['minima'])
                    ? (float) $temperature['minima']
                    : null;
                $tempAvg = ($tempMax !== null && $tempMin !== null)
                    ? ($tempMax + $tempMin) / 2
                    : null;

                $humidityAvg = $averageFromEntries($humidityEntries, 'value');
                $windAvg = $averageFromEntries($windEntries, 'velocidad');
                $precipAvg = $averageFromEntries($precipEntries, 'value');

                $allForecasts[] = [
                    'idema' => $municipalityId,
                    'fecha' => $date,
                    'tempMax' => $tempMax,
                    'tempMin' => $tempMin,
                    'tempAvg' => $tempAvg,
                    'humidityAvg' => $humidityAvg,
                    'windSpeedAvg' => $windAvg,
                    'precipitationProb' => $precipAvg,
                ];
            }

            $stationsInfo[$municipalityId] = [
                'id' => $municipalityId,
                'name' => $locationName,
                'provincia' => $locationProvince,
            ];
        }

        return [
            'queryType' => 'forecast',
            'observations' => $allForecasts,
            'stations' => $stationsInfo,
            'selectedStationIds' => $municipalityIds,
        ];
    }
}
