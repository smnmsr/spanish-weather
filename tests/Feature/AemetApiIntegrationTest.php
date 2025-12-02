<?php

use App\Services\AemetService;
use Illuminate\Support\Facades\Http;

it('can fetch all weather stations from AEMET', function () {
    if (empty(config('aemet.api_key'))) {
        $this->markTestSkipped('AEMET API key not configured');
    }

    $service = app(AemetService::class);
    $stations = $service->getAllStations();

    expect($stations)->toBeArray()
        ->and($stations)->not->toBeEmpty();
})->skipOnCi();

it('returns stations via API endpoint', function () {
    if (empty(config('aemet.api_key'))) {
        $this->markTestSkipped('AEMET API key not configured');
    }

    $response = $this->getJson('/api/stations');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data',
            'count',
        ])
        ->assertJson([
            'success' => true,
        ]);
})->skipOnCi();

it('can find nearest station to coordinates', function () {
    if (empty(config('aemet.api_key'))) {
        $this->markTestSkipped('AEMET API key not configured');
    }

    // Madrid coordinates
    $response = $this->getJson('/api/stations/nearest?latitude=40.4168&longitude=-3.7038');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'distance_km',
            ],
        ])
        ->assertJson([
            'success' => true,
        ]);
})->skipOnCi();

it('validates coordinates when finding nearest station', function () {
    $response = $this->getJson('/api/stations/nearest?latitude=invalid&longitude=invalid');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

it('can fetch recent weather observations', function () {
    if (empty(config('aemet.api_key'))) {
        $this->markTestSkipped('AEMET API key not configured');
    }

    $response = $this->getJson('/api/weather/recent');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data',
            'count',
        ])
        ->assertJson([
            'success' => true,
        ]);
})->skipOnCi();

it('can fetch daily climate data for a station', function () {
    if (empty(config('aemet.api_key'))) {
        $this->markTestSkipped('AEMET API key not configured');
    }

    // Madrid-Retiro station
    $stationId = '3195';
    $response = $this->getJson('/api/weather/daily-climate?station_id='.$stationId.'&start_date=2024-01-01&end_date=2024-01-31');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data',
            'count',
            'period' => [
                'start',
                'end',
            ],
        ])
        ->assertJson([
            'success' => true,
        ]);
})->skipOnCi();

it('validates date range for daily climate data', function () {
    $response = $this->getJson('/api/weather/daily-climate?station_id=3195&start_date=2024-01-31&end_date=2024-01-01');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['end_date']);
});

it('handles AEMET API errors gracefully', function () {
    // Mock HTTP to simulate API failure
    Http::fake([
        'opendata.aemet.es/*' => Http::response(null, 500),
    ]);

    $response = $this->getJson('/api/stations');

    $response->assertStatus(500)
        ->assertJsonStructure([
            'success',
            'error',
        ])
        ->assertJson([
            'success' => false,
        ]);
});
