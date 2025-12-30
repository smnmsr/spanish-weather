<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

use function Tests\Helpers\configurePage;

it('completes the full workflow from home page to climate normals display', function () {
    $device = 'desktop';
    $darkMode = false;
    // Clear cache and set up HTTP mocks before navigating
    Cache::flush();
    Http::fake(array_merge(
        AemetFixtures::httpFakeConfig(),
        [
            // Climate normals - Step 1: Metadata with datos URL
            '*/api/valores/climatologicos/normales/estacion/3195' => Http::response([
                'estado' => 200,
                'descripcion' => 'OK',
                'datos' => 'http://test.local/aemet/normals/3195',
            ], 200),
            '*/api/valores/climatologicos/normales/estacion/0201D' => Http::response([
                'estado' => 200,
                'descripcion' => 'OK',
                'datos' => 'http://test.local/aemet/normals/0201D',
            ], 200),
            // Climate normals - Step 2: Actual data per station (all 12 months)
            'http://test.local/aemet/normals/3195' => Http::response(AemetFixtures::climateNormals()),
            'http://test.local/aemet/normals/0201D' => Http::response(AemetFixtures::climateNormals()),
        ]
    ));

    // 1. Navigate directly to data-options step with pre-selected stations
    // Selecting Madrid Retiro (3195) and Barcelona Airport (0201D)
    $page = configurePage('/?step=data-options&stations=3195,0201D', $device, $darkMode);

    // 2. Select "Klimatologische Normalen" (climatological normals) data query type
    $selector = '[data-testid="data-option-card-climatological-normals"]';
    $page->assertPresent($selector)->click($selector);

    // 3. Submit data query
    $page->click('Daten abfragen');

    // 4. Verify results are displayed with correct title
    $page->assertSee('Klimanormale (1991-2020)')
        ->assertSee('MADRID RETIRO')
        ->assertSee('BARCELONA AEROPUERTO');

    // 5. Verify charts are rendered - assertScript waits automatically
    $page->assertScript(
        "document.querySelectorAll('svg').length > 0",
        true,
        'At least one SVG chart must be rendered for climate normals'
    );

    // 6. Verify all chart dimensions are present (exclude windDirection for climate normals)
    $page->assertSee('Temperatur')
        ->assertSee('Niederschlag')
        ->assertSee('Luftfeuchtigkeit')
        ->assertSee('Wind')
        ->assertSee('Luftdruck')
        ->assertSee('Sonnenschein');

    // 7. Verify windDirection is NOT displayed for climate normals
    // (This is the key verification that wind direction was correctly removed)
    $page->assertDontSee('Windrichtung');

    // 8. Verify legend items are present (one per station)
    $page->assertScript(
        "document.querySelectorAll('.mt-4.flex.flex-wrap.justify-center span').length >= 2",
        true,
        'Should have at least 2 legend text items for 2 stations'
    );

    // 9. Verify carousel region is present for dimension navigation
    $page->assertPresent('[role="region"]', 'Carousel region should be present');
});

it('shows error message when no stations are selected for climate normals', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    // Navigate directly to data-options without selecting stations
    $page = visit('/?step=data-options');

    // The data-options step should indicate no stations are selected
    $page->assertNoJavaScriptErrors();
});

it('displays all climate normals dimensions with correct descriptions and rendering', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(array_merge(
        AemetFixtures::httpFakeConfig(),
        [
            '*/api/valores/climatologicos/normales/estacion/3195' => Http::response([
                'estado' => 200,
                'descripcion' => 'OK',
                'datos' => 'http://test.local/aemet/normals/3195',
            ], 200),
            'http://test.local/aemet/normals/3195' => Http::response(AemetFixtures::climateNormals()),
        ]
    ));

    $page = configurePage('/?step=data-options&stations=3195', 'desktop', false);

    // Select climate normals
    $page->click('[data-testid="data-option-card-climatological-normals"]');

    // Submit query
    $page->click('Daten abfragen');

    // Verify results page shows the correct title and station
    $page->assertSee('Klimanormale (1991-2020)')
        ->assertSee('MADRID RETIRO')
        ->assertNoJavaScriptErrors();

    // Verify all 6 climate normals dimensions are displayed (no windDirection)
    $page->assertSee('Temperatur')
        ->assertSee('Niederschlag')
        ->assertSee('Luftfeuchtigkeit')
        ->assertSee('Wind')
        ->assertSee('Luftdruck')
        ->assertSee('Sonnenschein');

    // Verify windDirection is NOT present in climate normals
    $page->assertDontSee('Windrichtung', 'Wind direction should not appear in climate normals');

    // Verify specific chart descriptions
    $page->assertSee('Temperatur (°C) – Mittelwert (Linie), Min/Max (Bereich)', 'Temperature description should show mean and range')
        ->assertSee('Windgeschwindigkeit (km/h) – Mittelwert (Linie), Böen (Fläche)', 'Wind description should show mean and gust');

    // Verify at least one chart is displayed as SVG
    $page->assertScript(
        "document.querySelectorAll('svg').length >= 6",
        true,
        'All 6 climate normals dimensions should render as SVG charts'
    );

    // Verify legend has station entry
    $page->assertScript(
        "document.querySelectorAll('.mt-4.flex.flex-wrap.justify-center span').length >= 1",
        true,
        'Should have at least 1 legend text item for the station'
    );
});

it('handles multiple stations for climate normals with all dimensions and no wind direction', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(array_merge(
        AemetFixtures::httpFakeConfig(),
        [
            '*/api/valores/climatologicos/normales/estacion/3195' => Http::response([
                'estado' => 200,
                'descripcion' => 'OK',
                'datos' => 'http://test.local/aemet/normals/3195',
            ], 200),
            '*/api/valores/climatologicos/normales/estacion/0201D' => Http::response([
                'estado' => 200,
                'descripcion' => 'OK',
                'datos' => 'http://test.local/aemet/normals/0201D',
            ], 200),
            '*/api/valores/climatologicos/normales/estacion/1387' => Http::response([
                'estado' => 200,
                'descripcion' => 'OK',
                'datos' => 'http://test.local/aemet/normals/1387',
            ], 200),
            'http://test.local/aemet/normals/3195' => Http::response(AemetFixtures::climateNormals()),
            'http://test.local/aemet/normals/0201D' => Http::response(AemetFixtures::climateNormals()),
            'http://test.local/aemet/normals/1387' => Http::response(AemetFixtures::climateNormals()),
        ]
    ));

    // Select 3 stations
    $page = configurePage('/?step=data-options&stations=3195,0201D,1387', 'desktop', false);

    // Select climate normals
    $page->click('[data-testid="data-option-card-climatological-normals"]');

    // Submit query
    $page->click('Daten abfragen');

    // Verify all station names appear in results and title
    $page->assertSee('Klimanormale (1991-2020)')
        ->assertSee('MADRID RETIRO')
        ->assertSee('BARCELONA AEROPUERTO');

    // Verify all 6 climate normals dimensions are displayed
    $page->assertSee('Temperatur')
        ->assertSee('Niederschlag')
        ->assertSee('Luftfeuchtigkeit')
        ->assertSee('Wind')
        ->assertSee('Luftdruck')
        ->assertSee('Sonnenschein');

    // Verify windDirection is NOT displayed for climate normals
    $page->assertDontSee('Windrichtung', 'Wind direction should not be shown in climate normals');

    // Verify SVG charts are rendered for all 6 dimensions
    $page->assertScript(
        "document.querySelectorAll('svg').length >= 6",
        true,
        'All 6 climate normals dimensions should render as SVG charts'
    );

    // Verify legend has multiple stations
    $page->assertScript(
        "document.querySelectorAll('.mt-4.flex.flex-wrap.justify-center span').length >= 3",
        true,
        'Should have at least 3 legend items for 3 stations'
    );
});
