<?php

use Illuminate\Support\Facades\Cache;

it('queries current observations for selected stations', function () {
    // Mock the AEMET service to return test data
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            [
                'idema' => '1234',
                'fint' => '2025-12-02T12:00:00',
                'ta' => 15.5,
                'hr' => 65,
                'prec' => 0,
                'vv' => 10,
                'pres' => 1013,
            ],
            [
                'idema' => '5678',
                'fint' => '2025-12-02T12:00:00',
                'ta' => 18.2,
                'hr' => 70,
                'prec' => 2.5,
                'vv' => 15,
                'pres' => 1015,
            ],
        ]);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            [
                'idema' => '1234',
                'nombre' => 'Test Station 1',
                'provincia' => 'Madrid',
                'latitud' => '40.4167',
                'longitud' => '-3.7033',
            ],
            [
                'idema' => '5678',
                'nombre' => 'Test Station 2',
                'provincia' => 'Barcelona',
                'latitud' => '41.3879',
                'longitud' => '2.1699',
            ],
        ]);

    $response = $this->post('/query-data', [
        'type' => 'current-observations',
        'stationIds' => ['1234', '5678'],
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'queryType' => 'current-observations',
    ]);
    $response->assertJsonCount(2, 'observations');
    $response->assertJsonStructure([
        'queryType',
        'observations',
        'stations',
        'selectedStationIds',
    ]);
})->skipOnCi();

it('validates query data request', function () {
    $response = $this->post('/query-data', [
        'type' => 'invalid-type',
        'stationIds' => [],
    ]);

    $response->assertSessionHasErrors('type');
})->skipOnCi();

it('requires station ids', function () {
    $response = $this->post('/query-data', [
        'type' => 'current-observations',
    ]);

    $response->assertSessionHasErrors('stationIds');
})->skipOnCi();
