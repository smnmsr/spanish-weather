<?php

namespace Tests\Helpers;

/**
 * Static test data fixtures for mocking AEMET API responses.
 * Based on real AEMET API response structures.
 */
class AemetFixtures
{
    /**
     * Get mock station inventory data.
     * Represents the response from /api/valores/climatologicos/inventarioestaciones/todasestaciones
     */
    public static function stations(): array
    {
        return [
            [
                'idema' => '3195',
                'nombre' => 'MADRID RETIRO',
                'latitud' => '40°24\'48"N',
                'longitud' => '03°40\'50"W',
                'provincia' => 'Madrid',
                'altitud' => '667',
                'indicativo' => '3195',
            ],
            [
                'idema' => '0201D',
                'nombre' => 'BARCELONA AEROPUERTO',
                'latitud' => '41°17\'45"N',
                'longitud' => '02°04\'36"E',
                'provincia' => 'Barcelona',
                'altitud' => '4',
                'indicativo' => '0201D',
            ],
            [
                'idema' => '5783',
                'nombre' => 'VALENCIA',
                'latitud' => '39°28\'53"N',
                'longitud' => '00°22\'08"W',
                'provincia' => 'Valencia',
                'altitud' => '11',
                'indicativo' => '5783',
            ],
            [
                'idema' => '5960',
                'nombre' => 'SEVILLA AEROPUERTO',
                'latitud' => '37°25\'02"N',
                'longitud' => '05°53\'48"W',
                'provincia' => 'Sevilla',
                'altitud' => '34',
                'indicativo' => '5960',
            ],
            [
                'idema' => '1387',
                'nombre' => 'BILBAO AEROPUERTO',
                'latitud' => '43°18\'04"N',
                'longitud' => '02°54\'27"W',
                'provincia' => 'Vizcaya',
                'altitud' => '42',
                'indicativo' => '1387',
            ],
        ];
    }

    /**
     * Get mock recent observations data.
     * Represents the response from /api/observacion/convencional/todas
     * Includes multiple timestamped observations for each station.
     */
    public static function observations(): array
    {
        return [
            // Madrid Retiro observations (3195)
            [
                'idema' => '3195',
                'fint' => '2025-12-12T08:00:00',
                'ta' => 8.5,
                'hr' => 72,
                'prec' => 0.0,
                'vv' => 5.2,
                'pres' => 1013.2,
            ],
            [
                'idema' => '3195',
                'fint' => '2025-12-12T09:00:00',
                'ta' => 10.2,
                'hr' => 68,
                'prec' => 0.0,
                'vv' => 6.8,
                'pres' => 1013.0,
            ],
            [
                'idema' => '3195',
                'fint' => '2025-12-12T10:00:00',
                'ta' => 12.8,
                'hr' => 62,
                'prec' => 0.0,
                'vv' => 8.1,
                'pres' => 1012.8,
            ],
            [
                'idema' => '3195',
                'fint' => '2025-12-12T11:00:00',
                'ta' => 14.5,
                'hr' => 58,
                'prec' => 0.0,
                'vv' => 9.3,
                'pres' => 1012.5,
            ],
            [
                'idema' => '3195',
                'fint' => '2025-12-12T12:00:00',
                'ta' => 15.8,
                'hr' => 55,
                'prec' => 0.0,
                'vv' => 10.5,
                'pres' => 1012.2,
            ],

            // Barcelona Airport observations (0201D)
            [
                'idema' => '0201D',
                'fint' => '2025-12-12T08:00:00',
                'ta' => 12.3,
                'hr' => 78,
                'prec' => 0.2,
                'vv' => 12.5,
                'pres' => 1015.8,
            ],
            [
                'idema' => '0201D',
                'fint' => '2025-12-12T09:00:00',
                'ta' => 13.7,
                'hr' => 75,
                'prec' => 0.1,
                'vv' => 13.2,
                'pres' => 1015.5,
            ],
            [
                'idema' => '0201D',
                'fint' => '2025-12-12T10:00:00',
                'ta' => 15.2,
                'hr' => 71,
                'prec' => 0.0,
                'vv' => 14.8,
                'pres' => 1015.2,
            ],
            [
                'idema' => '0201D',
                'fint' => '2025-12-12T11:00:00',
                'ta' => 16.8,
                'hr' => 68,
                'prec' => 0.0,
                'vv' => 15.5,
                'pres' => 1015.0,
            ],
            [
                'idema' => '0201D',
                'fint' => '2025-12-12T12:00:00',
                'ta' => 18.1,
                'hr' => 65,
                'prec' => 0.0,
                'vv' => 16.2,
                'pres' => 1014.8,
            ],

            // Valencia observations (5783)
            [
                'idema' => '5783',
                'fint' => '2025-12-12T08:00:00',
                'ta' => 11.5,
                'hr' => 80,
                'prec' => 1.5,
                'vv' => 8.3,
                'pres' => 1014.5,
            ],
            [
                'idema' => '5783',
                'fint' => '2025-12-12T09:00:00',
                'ta' => 13.2,
                'hr' => 76,
                'prec' => 0.8,
                'vv' => 9.1,
                'pres' => 1014.2,
            ],
            [
                'idema' => '5783',
                'fint' => '2025-12-12T10:00:00',
                'ta' => 15.6,
                'hr' => 72,
                'prec' => 0.3,
                'vv' => 10.2,
                'pres' => 1014.0,
            ],
            [
                'idema' => '5783',
                'fint' => '2025-12-12T11:00:00',
                'ta' => 17.3,
                'hr' => 68,
                'prec' => 0.0,
                'vv' => 11.5,
                'pres' => 1013.8,
            ],
            [
                'idema' => '5783',
                'fint' => '2025-12-12T12:00:00',
                'ta' => 18.9,
                'hr' => 64,
                'prec' => 0.0,
                'vv' => 12.8,
                'pres' => 1013.5,
            ],

            // Sevilla Airport observations (5960)
            [
                'idema' => '5960',
                'fint' => '2025-12-12T08:00:00',
                'ta' => 9.2,
                'hr' => 85,
                'prec' => 0.0,
                'vv' => 4.5,
                'pres' => 1016.2,
            ],
            [
                'idema' => '5960',
                'fint' => '2025-12-12T09:00:00',
                'ta' => 11.8,
                'hr' => 80,
                'prec' => 0.0,
                'vv' => 5.2,
                'pres' => 1016.0,
            ],
            [
                'idema' => '5960',
                'fint' => '2025-12-12T10:00:00',
                'ta' => 14.5,
                'hr' => 74,
                'prec' => 0.0,
                'vv' => 6.8,
                'pres' => 1015.8,
            ],
            [
                'idema' => '5960',
                'fint' => '2025-12-12T11:00:00',
                'ta' => 16.9,
                'hr' => 68,
                'prec' => 0.0,
                'vv' => 7.5,
                'pres' => 1015.5,
            ],
            [
                'idema' => '5960',
                'fint' => '2025-12-12T12:00:00',
                'ta' => 19.2,
                'hr' => 62,
                'prec' => 0.0,
                'vv' => 8.2,
                'pres' => 1015.2,
            ],

            // Bilbao Airport observations (1387)
            [
                'idema' => '1387',
                'fint' => '2025-12-12T08:00:00',
                'ta' => 10.5,
                'hr' => 88,
                'prec' => 2.3,
                'vv' => 15.2,
                'pres' => 1012.5,
            ],
            [
                'idema' => '1387',
                'fint' => '2025-12-12T09:00:00',
                'ta' => 11.2,
                'hr' => 85,
                'prec' => 1.8,
                'vv' => 16.5,
                'pres' => 1012.3,
            ],
            [
                'idema' => '1387',
                'fint' => '2025-12-12T10:00:00',
                'ta' => 12.8,
                'hr' => 82,
                'prec' => 0.5,
                'vv' => 17.8,
                'pres' => 1012.0,
            ],
            [
                'idema' => '1387',
                'fint' => '2025-12-12T11:00:00',
                'ta' => 13.5,
                'hr' => 78,
                'prec' => 0.0,
                'vv' => 18.5,
                'pres' => 1011.8,
            ],
            [
                'idema' => '1387',
                'fint' => '2025-12-12T12:00:00',
                'ta' => 14.8,
                'hr' => 75,
                'prec' => 0.0,
                'vv' => 19.2,
                'pres' => 1011.5,
            ],
        ];
    }

    /**
     * Get mock daily climate data.
     * Represents the response from /api/valores/climatologicos/diarios/datos endpoint
     */
    public static function dailyClimateData(): array
    {
        return [
            [
                'idema' => '3195',
                'fecha' => '2024-12-10',
                'tmed' => '14,5',
                'tmax' => '18,2',
                'tmin' => '10,8',
                'hrMedia' => '65',
                'hrMax' => '82',
                'hrMin' => '48',
                'prec' => '0,0',
                'velmedia' => '8,5',
                'sol' => '6,2',
            ],
            [
                'idema' => '3195',
                'fecha' => '2024-12-11',
                'tmed' => '15,2',
                'tmax' => '19,1',
                'tmin' => '11,5',
                'hrMedia' => '62',
                'hrMax' => '78',
                'hrMin' => '45',
                'prec' => '0,0',
                'velmedia' => '9,2',
                'sol' => '6,8',
            ],
            [
                'idema' => '3195',
                'fecha' => '2024-12-12',
                'tmed' => '16,1',
                'tmax' => '20,3',
                'tmin' => '12,2',
                'hrMedia' => '58',
                'hrMax' => '75',
                'hrMin' => '42',
                'prec' => '0,0',
                'velmedia' => '7,8',
                'sol' => '7,5',
            ],
            [
                'idema' => '0201D',
                'fecha' => '2024-12-10',
                'tmed' => '12,8',
                'tmax' => '15,4',
                'tmin' => '10,2',
                'hrMedia' => '72',
                'hrMax' => '85',
                'hrMin' => '58',
                'prec' => '2,3',
                'velmedia' => '12,5',
                'sol' => '4,1',
            ],
            [
                'idema' => '0201D',
                'fecha' => '2024-12-11',
                'tmed' => '13,5',
                'tmax' => '16,2',
                'tmin' => '11,1',
                'hrMedia' => '68',
                'hrMax' => '82',
                'hrMin' => '54',
                'prec' => '0,5',
                'velmedia' => '11,8',
                'sol' => '5,2',
            ],
            [
                'idema' => '0201D',
                'fecha' => '2024-12-12',
                'tmed' => '14,2',
                'tmax' => '17,1',
                'tmin' => '11,8',
                'hrMedia' => '65',
                'hrMax' => '80',
                'hrMin' => '51',
                'prec' => '0,0',
                'velmedia' => '10,5',
                'sol' => '6,5',
            ],
        ];
    }

    /**
     * Generate mock monthly/annual climatological data dynamically for any month/year.
     * Uses seeded random values based on station ID, month, and year for consistent test results.
     *
     * @param  string  $stationId  Station identifier (e.g., '3195', '0201D')
     * @param  int  $startYear  Start year (e.g., 2020)
     * @param  int  $endYear  End year (e.g., 2024)
     * @return array Array of monthly records
     */
    public static function generateMonthlyAnnualDataForStation(string $stationId, int $startYear, int $endYear): array
    {
        // Base temperatures by station (annual average) and month variations
        $stationBaseTemps = [
            '3195' => 15.0,  // Madrid
            '0201D' => 16.5, // Barcelona
            '5783' => 17.5,  // Valencia
            '5960' => 19.0,  // Sevilla
            '1387' => 14.0,  // Bilbao
        ];

        // Monthly temperature offsets from annual average (in °C)
        $monthlyOffsets = [
            1 => -6.0,  // January (coldest)
            2 => -4.5,  // February
            3 => -2.0,  // March
            4 => 1.5,   // April
            5 => 5.0,   // May
            6 => 9.0,   // June
            7 => 12.0,  // July (hottest)
            8 => 11.5,  // August
            9 => 7.5,   // September
            10 => 3.0,  // October
            11 => -1.0, // November
            12 => -5.0, // December
        ];

        $baseTemp = $stationBaseTemps[$stationId] ?? 15.0;
        $data = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                // Use a deterministic seed for consistent test data
                $seed = crc32("{$stationId}-{$year}-{$month}");
                mt_srand($seed);

                // Calculate temperatures with some year-to-year variation
                $yearVariation = (mt_rand(-15, 25) / 10.0); // ±1.5 to ±2.5°C year variation
                $monthOffset = $monthlyOffsets[$month];
                $avgTemp = $baseTemp + $monthOffset + $yearVariation;
                $maxTemp = $avgTemp + mt_rand(80, 140) / 10.0; // 8-14°C above average
                $minTemp = $avgTemp - mt_rand(60, 100) / 10.0; // 6-10°C below average

                // Precipitation varies by season (winter more in Spain generally)
                $precipBase = in_array($month, [11, 12, 1, 2, 3]) ? 40 : 20; // Winter vs Summer
                $precipitation = mt_rand(5, $precipBase + 30);

                // Humidity inversely related to temperature
                $humidity = (int) (80 - ($avgTemp / 2) + mt_rand(-10, 10));
                $humidity = max(25, min(95, $humidity)); // Clamp between 25-95%

                $data[] = [
                    'idema' => $stationId,
                    'indicativo' => $stationId,
                    'fecha' => sprintf('%d-%02d', $year, $month),
                    'tm_mes' => number_format($avgTemp, 1, ',', ''),
                    'tm_max' => number_format($maxTemp, 1, ',', ''),
                    'tm_min' => number_format($minTemp, 1, ',', ''),
                    'p_mes' => (string) $precipitation,
                    'hr' => (string) $humidity,
                ];
            }
        }

        return $data;
    }

    /**
     * Get mock monthly/annual climatological data.
     * Now dynamically generates data for all stations across all months and years.
     * Supports years 1950-2025 and all 12 months.
     */
    public static function monthlyAnnualData(): array
    {
        // Generate data for common test scenarios
        $allData = [];
        $stations = ['3195', '0201D', '5783', '5960', '1387'];
        $startYear = 1990;
        $endYear = 2025;

        foreach ($stations as $stationId) {
            $stationData = self::generateMonthlyAnnualDataForStation($stationId, $startYear, $endYear);
            $allData = array_merge($allData, $stationData);
        }

        return $allData;
    }

    /**
     * Get HTTP fake configuration for AEMET API.
     * This includes both the metadata and data URL responses following AEMET's two-step pattern.
     */
    public static function httpFakeConfig(): array
    {
        return [
            // Station inventory - Step 1: Metadata with datos URL
            '*/api/valores/climatologicos/inventarioestaciones/todasestaciones' => \Illuminate\Support\Facades\Http::response([
                'datos' => 'http://test.local/mock-stations-data',
                'estado' => 200,
                'descripcion' => 'exito',
            ]),
            // Station inventory - Step 2: Actual data
            '*/mock-stations-data' => \Illuminate\Support\Facades\Http::response(self::stations()),

            // Recent observations - Step 1: Metadata with datos URL
            '*/api/observacion/convencional/todas' => \Illuminate\Support\Facades\Http::response([
                'datos' => 'http://test.local/mock-observations-data',
                'estado' => 200,
                'descripcion' => 'exito',
            ]),
            // Recent observations - Step 2: Actual data
            '*/mock-observations-data' => \Illuminate\Support\Facades\Http::response(self::observations()),

            // Monthly/annual climatological data - Step 1: Metadata with datos URL
            '*/api/valores/climatologicos/mensualesanuales/datos/*' => \Illuminate\Support\Facades\Http::response([
                'datos' => 'http://test.local/mock-monthly-data',
                'estado' => 200,
                'descripcion' => 'exito',
            ]),
            // Monthly/annual climatological data - Step 2: Actual data
            '*/mock-monthly-data' => \Illuminate\Support\Facades\Http::response(self::monthlyAnnualData()),
        ];
    }
}
