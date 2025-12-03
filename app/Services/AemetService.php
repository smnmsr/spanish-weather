<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AemetService
{
    protected string $apiKey;

    protected string $baseUrl;

    /** Maximum retries for transient errors like 429/500 */
    protected int $maxRetries = 3;

    /** Base delay in milliseconds for exponential backoff */
    protected int $baseDelayMs = 500;

    public function __construct()
    {
        $this->apiKey = config('aemet.api_key');
        $this->baseUrl = config('aemet.base_url');

        if (empty($this->apiKey)) {
            throw new \RuntimeException('AEMET API key is not configured. Please set AEMET_API_KEY in your .env file.');
        }
    }

    /**
     * Get all weather stations from AEMET.
     */
    public function getAllStations(): array
    {
        $cacheKey = 'aemet_stations_all';
        $cacheTtl = config('aemet.cache_ttl.stations');

        return Cache::remember($cacheKey, $cacheTtl, function () {
            $endpoint = '/api/valores/climatologicos/inventarioestaciones/todasestaciones';

            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Get recent observational data for all stations (last 24h).
     */
    public function getRecentObservations(): array
    {
        $cacheKey = 'aemet_recent_observations';
        $cacheTtl = config('aemet.cache_ttl.recent_data');

        return Cache::remember($cacheKey, $cacheTtl, function () {
            $endpoint = '/api/observacion/convencional/todas';

            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Get recent observational data for a specific station.
     */
    public function getStationObservations(string $stationId): array
    {
        $cacheKey = "aemet_station_observations_{$stationId}";
        $cacheTtl = config('aemet.cache_ttl.recent_data');

        return Cache::remember($cacheKey, $cacheTtl, function () use ($stationId) {
            $endpoint = "/api/observacion/convencional/datos/estacion/{$stationId}";

            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Get daily climate data for a specific station and date range.
     */
    public function getDailyClimateData(string $stationId, string $startDate, string $endDate): array
    {
        $cacheKey = "aemet_daily_climate_{$stationId}_{$startDate}_{$endDate}";
        $cacheTtl = config('aemet.cache_ttl.historical_data');

        return Cache::remember($cacheKey, $cacheTtl, function () use ($stationId, $startDate, $endDate) {
            // Format: YYYY-MM-DDTHH:MM:SSUTC (e.g., 2023-01-01T00:00:00UTC)
            $start = $this->formatDateForApi($startDate);
            $end = $this->formatDateForApi($endDate);

            $endpoint = "/api/valores/climatologicos/diarios/datos/fechaini/{$start}/fechafin/{$end}/estacion/{$stationId}";

            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Get climate normals (1991-2020) for a specific station.
     */
    public function getClimateNormals(string $stationId): array
    {
        $cacheKey = "aemet_climate_normals_{$stationId}";
        $cacheTtl = config('aemet.cache_ttl.historical_data');

        return Cache::remember($cacheKey, $cacheTtl, function () use ($stationId) {
            $endpoint = "/api/valores/climatologicos/normales/estacion/{$stationId}";

            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Make a request to the AEMET API using the two-step process.
     *
     * Step 1: Request the data URL
     * Step 2: Fetch the actual data from the returned URL
     */
    protected function makeRequest(string $endpoint): array
    {
        $attempt = 0;
        $lastStatus = null;
        $response = null;

        while ($attempt <= $this->maxRetries) {
            // Step 1: Get the data URL
            $response = Http::withHeaders([
                'api_key' => $this->apiKey,
            ])->get($this->baseUrl.$endpoint);

            $lastStatus = $response->status();

            if ($response->successful()) {
                break;
            }

            // Handle rate limiting (429) and server errors (5xx) with backoff
            if ($lastStatus === 429 || ($lastStatus >= 500 && $lastStatus < 600)) {
                $delayMs = $this->backoffDelayMs($attempt);

                // Respect Retry-After header if present
                $retryAfter = (int) ($response->header('Retry-After') ?? 0);
                if ($retryAfter > 0) {
                    // Convert seconds to milliseconds
                    $delayMs = max($delayMs, $retryAfter * 1000);
                }

                // Log and sleep before retrying
                Log::warning('AEMET API transient error, backing off', [
                    'endpoint' => $endpoint,
                    'status' => $lastStatus,
                    'attempt' => $attempt + 1,
                    'delay_ms' => $delayMs,
                ]);

                usleep($delayMs * 1000);
                $attempt++;

                continue;
            }

            // Non-retryable error
            Log::error('AEMET API request failed', [
                'endpoint' => $endpoint,
                'status' => $lastStatus,
                'response' => $response->body(),
            ]);

            throw new \RuntimeException("AEMET API request failed: {$lastStatus}");
        }

        if (! $response || ! $response->successful()) {
            Log::error('AEMET API request failed after retries', [
                'endpoint' => $endpoint,
                'status' => $lastStatus,
                'attempts' => $attempt,
            ]);

            throw new \RuntimeException("AEMET API request failed after retries: {$lastStatus}");
        }

        $metadata = $response->json();

        if (! isset($metadata['datos'])) {
            Log::error('AEMET API response missing datos URL', [
                'endpoint' => $endpoint,
                'response' => $metadata,
            ]);

            throw new \RuntimeException('AEMET API response is missing the data URL');
        }

        // Step 2: Fetch the actual data
        $attemptData = 0;
        $dataResponse = null;
        $lastDataStatus = null;

        while ($attemptData <= $this->maxRetries) {
            $dataResponse = Http::get($metadata['datos']);
            $lastDataStatus = $dataResponse->status();

            if ($dataResponse->successful()) {
                break;
            }

            if ($lastDataStatus === 429 || ($lastDataStatus >= 500 && $lastDataStatus < 600)) {
                $delayMs = $this->backoffDelayMs($attemptData);
                $retryAfter = (int) ($dataResponse->header('Retry-After') ?? 0);
                if ($retryAfter > 0) {
                    $delayMs = max($delayMs, $retryAfter * 1000);
                }

                Log::warning('AEMET data fetch transient error, backing off', [
                    'url' => $metadata['datos'],
                    'status' => $lastDataStatus,
                    'attempt' => $attemptData + 1,
                    'delay_ms' => $delayMs,
                ]);
                usleep($delayMs * 1000);
                $attemptData++;

                continue;
            }

            Log::error('AEMET data fetch failed', [
                'url' => $metadata['datos'],
                'status' => $lastDataStatus,
            ]);
            throw new \RuntimeException("Failed to fetch data from AEMET: {$lastDataStatus}");
        }

        if (! $dataResponse || ! $dataResponse->successful()) {
            Log::error('AEMET data fetch failed after retries', [
                'url' => $metadata['datos'],
                'status' => $lastDataStatus,
                'attempts' => $attemptData,
            ]);
            throw new \RuntimeException("Failed to fetch data from AEMET after retries: {$lastDataStatus}");
        }

        // AEMET API may return data with encoding issues (Spanish characters)
        // Get raw body and fix encoding before JSON decode
        $rawBody = $dataResponse->body();

        // Convert to UTF-8 if needed
        if (! mb_check_encoding($rawBody, 'UTF-8')) {
            $rawBody = mb_convert_encoding($rawBody, 'UTF-8', 'ISO-8859-1');
        }

        $data = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('AEMET data JSON decode failed', [
                'url' => $metadata['datos'],
                'error' => json_last_error_msg(),
                'body_preview' => substr($rawBody, 0, 500),
            ]);

            throw new \RuntimeException('Failed to decode AEMET data: '.json_last_error_msg());
        }

        return $data ?? [];
    }

    /**
     * Format date for AEMET API (YYYY-MM-DDTHH:MM:SSUTC).
     */
    protected function formatDateForApi(string $date): string
    {
        // If already in correct format, return as is
        if (str_contains($date, 'T') && str_contains($date, 'UTC')) {
            return $date;
        }

        // Otherwise, assume YYYY-MM-DD and append time
        return "{$date}T00:00:00UTC";
    }

    /**
     * Find the nearest station to given coordinates.
     */
    public function findNearestStation(float $latitude, float $longitude): ?array
    {
        $stations = $this->getAllStations();

        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($stations as $station) {
            if (! isset($station['latitud'], $station['longitud'])) {
                continue;
            }

            $stationLat = $this->parseCoordinate($station['latitud']);
            $stationLon = $this->parseCoordinate($station['longitud']);

            $distance = $this->calculateDistance($latitude, $longitude, $stationLat, $stationLon);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $station;
                $nearest['distance_km'] = round($distance, 2);
            }
        }

        return $nearest;
    }

    /**
     * Parse coordinate string to decimal degrees.
     * AEMET uses formats like: "40°25'00\"N" or just decimal "40.4167"
     */
    public function parseCoordinate(string $coordinate): float
    {
        // If already decimal, return
        if (is_numeric($coordinate)) {
            return (float) $coordinate;
        }

        // Parse DMS format (Degrees Minutes Seconds)
        if (preg_match('/(\d+)°(\d+)\'(\d+)"([NSEW])/', $coordinate, $matches)) {
            $degrees = (float) $matches[1];
            $minutes = (float) $matches[2];
            $seconds = (float) $matches[3];
            $direction = $matches[4];

            $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

            // Apply direction (S and W are negative)
            if (in_array($direction, ['S', 'W'])) {
                $decimal *= -1;
            }

            return $decimal;
        }

        // Parse compact DMS format e.g., "394924N" or "025309E"
        if (preg_match('/^(\d{2,3})(\d{2})(\d{2})([NSEW])$/', $coordinate, $matches)) {
            $degrees = (float) $matches[1];
            $minutes = (float) $matches[2];
            $seconds = (float) $matches[3];
            $direction = $matches[4];

            $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

            if (in_array($direction, ['S', 'W'])) {
                $decimal *= -1;
            }

            return $decimal;
        }

        return 0.0;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula (in km).
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    protected function backoffDelayMs(int $attempt): int
    {
        // Jittered exponential backoff: base * 2^attempt +/- 20%
        $delay = $this->baseDelayMs * (2 ** $attempt);
        $jitterFactor = mt_rand(80, 120) / 100; // 0.8 - 1.2

        return (int) ($delay * $jitterFactor);
    }
}
