<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

it('returns climatological normals for selected stations', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();

    // Fake AEMET two-step for normals endpoint
    Http::fake(array_merge(
        AemetFixtures::httpFakeConfig(), // Get base config which includes getAllStations mock
        [
            // Climate normals - Step 1: Metadata with datos URL for both stations
            '*/api/valores/climatologicos/normales/estacion/3195' => \Illuminate\Support\Facades\Http::response([
                'estado' => 200,
                'descripcion' => 'OK',
                'datos' => 'http://test.local/aemet/normals/3195',
            ], 200),
            '*/api/valores/climatologicos/normales/estacion/0201D' => \Illuminate\Support\Facades\Http::response([
                'estado' => 200,
                'descripcion' => 'OK',
                'datos' => 'http://test.local/aemet/normals/0201D',
            ], 200),
            // Climate normals - Step 2: Actual data per station
            'http://test.local/aemet/normals/3195' => Http::response([
                // Include 12 months + 1 summary; controller will filter by mes 01..12
                ...collect(range(1, 12))->map(function ($m) {
                    return [
                        'indicativo' => '3195',
                        'mes' => str_pad((string) $m, 2, '0', STR_PAD_LEFT),
                        'tm_mes_md' => '10.0',
                        'tm_max_md' => '15.0',
                        'tm_min_md' => '5.0',
                        'p_mes_md' => '20.0',
                        'hr_md' => '70.0',
                    ];
                })->all(),
                ['indicativo' => '3195'],
            ], 200),
            'http://test.local/aemet/normals/0201D' => Http::response([
                ...collect(range(1, 12))->map(function ($m) {
                    return [
                        'indicativo' => '0201D',
                        'mes' => str_pad((string) $m, 2, '0', STR_PAD_LEFT),
                        'tm_mes_md' => '12.0',
                        'tm_max_md' => '18.0',
                        'tm_min_md' => '7.0',
                        'p_mes_md' => '25.0',
                        'hr_md' => '65.0',
                    ];
                })->all(),
                ['indicativo' => '0201D'],
            ], 200),
        ]
    ));

    $response = $this->postJson('/query-data', [
        'type' => 'climatological-normals',
        'stationIds' => ['3195', '0201D'],
    ]);

    $response->assertSuccessful();
    $json = $response->json();

    expect($json['queryType'])->toBe('climatological-normals');
    expect($json['selectedStationIds'])->toBe(['3195', '0201D']);

    // Should have 24 monthly records total (12 per station)
    expect(count($json['observations']))->toBe(24);

    // Each observation must include fecha and idema
    foreach ($json['observations'] as $obs) {
        expect($obs)->toHaveKey('fecha');
        expect($obs)->toHaveKey('idema');
    }
});
