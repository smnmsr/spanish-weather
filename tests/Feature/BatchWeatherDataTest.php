<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    config(['queue.default' => 'sync']);
});

it('processes an uncached daily batch and fetches data from AEMET', function () {
    // Station metadata is cached to avoid extra HTTP calls during result formatting
    Cache::put('aemet_stations_all', [
        [
            'idema' => '1234',
            'nombre' => 'Test Station',
            'provincia' => 'Madrid',
            'latitud' => '40.4167',
            'longitud' => '-3.7033',
        ],
    ], 3600);

    // Two-step AEMET pattern: first call returns data URL, second returns payload
    Http::fake([
        'https://opendata.aemet.es/*' => Http::response([
            'datos' => 'https://fake-aemet.test/daily-values.json',
        ], 200),
        'https://fake-aemet.test/daily-values.json' => Http::response([
            [
                'idema' => '1234',
                'fecha' => '2024-01-01',
                'tmed' => 20.5,
            ],
        ], 200),
    ]);

    $payload = [
        'type' => 'daily-values',
        'stationIds' => ['1234'],
        'dateRange' => [
            'startDate' => '2024-01-01',
            'endDate' => '2024-01-02',
        ],
    ];

    $startResponse = $this->postJson('/api/batch/start', $payload);
    $startResponse->assertOk()->assertJsonStructure(['batchId', 'status']);

    $batchId = $startResponse->json('batchId');

    $resultsResponse = $this->getJson("/api/batch/{$batchId}/results");
    $resultsResponse->assertOk();
    $resultsResponse->assertJsonPath('status', 'completed');
    $resultsResponse->assertJsonPath('results.queryType', 'daily-values');

    // One job → two HTTP calls (metadata URL + data URL)
    Http::assertSentCount(2);

    $metadata = Cache::get("batch_metadata_{$batchId}");
    expect($metadata['status'])->toBe('completed');
    expect($metadata['completedJobs'])->toBe(1);
    expect(Cache::has('aemet_daily_climate_1234_2024-01-01_2024-01-02'))->toBeTrue();
});

it('short-circuits a cached daily batch without hitting AEMET', function () {
    // Seed caches so the controller detects a fully cached request
    Cache::put('aemet_stations_all', [
        [
            'idema' => '1234',
            'nombre' => 'Test Station',
            'provincia' => 'Madrid',
            'latitud' => '40.4167',
            'longitud' => '-3.7033',
        ],
    ], 3600);

    Cache::put(
        'aemet_daily_climate_1234_2024-01-01_2024-01-02',
        [
            [
                'idema' => '1234',
                'fecha' => '2024-01-01',
                'tmed' => 19.0,
            ],
        ],
        3600
    );

    Http::fake();

    $payload = [
        'type' => 'daily-values',
        'stationIds' => ['1234'],
        'dateRange' => [
            'startDate' => '2024-01-01',
            'endDate' => '2024-01-02',
        ],
    ];

    $startResponse = $this->postJson('/api/batch/start', $payload);
    $startResponse->assertOk();
    $startResponse->assertJsonPath('status', 'completed');
    $startResponse->assertJsonPath('completedImmediately', true);

    $batchId = $startResponse->json('batchId');

    $resultsResponse = $this->getJson("/api/batch/{$batchId}/results");
    $resultsResponse->assertOk();
    $resultsResponse->assertJsonPath('status', 'completed');
    $resultsResponse->assertJsonPath('results.queryType', 'daily-values');

    // All data was cached; no external HTTP calls expected
    Http::assertSentCount(0);

    $metadata = Cache::get("batch_metadata_{$batchId}");
    expect($metadata['status'])->toBe('completed');
    expect($metadata['completedJobs'])->toBe(1);
});
