<?php

use Illuminate\Support\Facades\Http;

it('returns 7-day municipal forecast without station selection', function () {
    Http::fake([
        'opendata.aemet.es/*/api/prediccion/especifica/municipio/diaria/28079' => Http::response([
            'estado' => 200,
            'descripcion' => 'OK',
            'datos' => 'https://mock.local/aemet/forecast/28079',
        ], 200),
        'mock.local/aemet/forecast/28079' => Http::response([
            [
                'id' => '28079',
                'nombre' => 'Madrid',
                'provincia' => 'Madrid',
                'prediccion' => [
                    'dia' => [
                        [
                            'fecha' => '2025-12-16',
                            'temperatura' => [
                                'maxima' => 22,
                                'minima' => 12,
                            ],
                            'probPrecipitacion' => [
                                ['value' => 10],
                                ['value' => 20],
                            ],
                            'humedadRelativa' => [
                                ['value' => 60],
                                ['value' => 70],
                            ],
                            'viento' => [
                                ['velocidad' => 15],
                                ['velocidad' => 20],
                            ],
                        ],
                        [
                            'fecha' => '2025-12-17',
                            'temperatura' => [
                                'maxima' => 21,
                                'minima' => 11,
                            ],
                            'probPrecipitacion' => [
                                ['value' => 0],
                            ],
                            'humedadRelativa' => [
                                ['value' => 55],
                            ],
                            'viento' => [
                                ['velocidad' => 10],
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->postJson('/query-data', [
        'type' => 'forecast',
        'municipalityIds' => ['28079'],
    ]);

    $response->assertSuccessful();
    $json = $response->json();

    expect($json['queryType'])->toBe('forecast');
    expect($json['selectedStationIds'])->toBe(['28079']);
    expect($json['stations']['28079']['name'])->toBe('Madrid');
    expect(count($json['observations']))->toBe(2);

    foreach ($json['observations'] as $obs) {
        expect($obs)->toHaveKey('fecha');
        expect($obs)->toHaveKey('idema');
    }
});
