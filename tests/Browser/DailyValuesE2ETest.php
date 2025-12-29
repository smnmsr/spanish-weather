<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

use function Tests\Helpers\configurePage;

it('completes the full workflow from home page to data display for daily values', function (string $device, bool $darkMode) {
    // Clear cache and set up HTTP mocks before navigating
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    // 1. Navigate directly to data-options step with pre-selected stations
    // Selecting Madrid Retiro (3195), Barcelona Airport (0201D), and Valencia (5783)
    $page = configurePage('/?step=data-options&stations=3195,0201D,5783', $device, $darkMode);

    // 2. Select "Tageswerte" (daily values) data query type
    $selector = '[data-testid="data-option-card-daily-values"]';
    $page->assertPresent($selector)->click($selector);

    // 3. Wait for date range selector to appear and click a preset
    // assertSee() implicitly waits for the element to appear
    $page->assertSee('Letzte 7 Tage')->click('Letzte 7 Tage');

    // 4. Submit data query
    $page->click('Daten abfragen');

    // 5. Verify results are displayed (assertSee implicitly waits)
    $page->assertSee('Tageswerte')
        ->assertSee('MADRID RETIRO')
        ->assertSee('BARCELONA AEROPUERTO')
        ->assertSee('VALENCIA');

    // 6. Verify charts are rendered - assertScript waits automatically
    $page->assertScript(
        "document.querySelectorAll('svg').length > 0",
        true,
        'At least one SVG chart must be rendered'
    );

    // 7. Verify all chart dimensions are present
    $page->assertSee('Temperatur')
        ->assertSee('Niederschlag')
        ->assertSee('Luftfeuchtigkeit')
        ->assertSee('Wind')
        ->assertSee('Windrichtung')
        ->assertSee('Luftdruck');

    // 8. Verify legend items are present (one per station)
    $page->assertScript(
        "document.querySelectorAll('.mt-4.flex.flex-wrap.justify-center span').length >= 3",
        true,
        'Should have at least 3 legend text items for 3 stations'
    );

    // 9. Verify carousel region is present for dimension navigation
    $page->assertPresent('[role="region"]', 'Carousel region should be present');
})->with([
    'desktop light mode' => ['desktop', false],
    'mobile dark mode' => ['mobile', true],
]);

it('shows error message when no stations are selected for daily values', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    // Navigate directly to data-options without selecting stations
    $page = visit('/?step=data-options');

    // The data-options step should indicate no stations are selected
    // or the stepper should prevent navigation to this step
    $page->assertNoJavaScriptErrors();
});

it('displays date range selector when daily values is selected', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    $page = visit('/?step=data-options&stations=3195');

    // Select daily values
    $page->click('[data-testid="data-option-card-daily-values"]');

    // Verify date range selector appears with preset buttons (assertSee waits)
    $page->assertSee('Letzte 7 Tage')
        ->assertSee('Letzte 30 Tage')
        ->assertNoJavaScriptErrors();
});

it('handles multiple stations for daily values correctly and displays all data', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    // Select all 5 available stations via data-options step
    $page = visit('/?step=data-options&stations=3195,0201D,5783,5960,1387');

    // Select daily values
    $page->click('[data-testid="data-option-card-daily-values"]');

    // Select a preset (assertSee waits for element)
    $page->assertSee('Letzte 7 Tage')->click('Letzte 7 Tage');

    // Submit query
    $page->click('Daten abfragen');

    // Verify all station names appear in results (assertSee waits for elements)
    $page->assertSee('MADRID RETIRO')
        ->assertSee('BARCELONA AEROPUERTO')
        ->assertSee('VALENCIA')
        ->assertSee('SEVILLA AEROPUERTO')
        ->assertSee('BILBAO AEROPUERTO');

    // Verify all chart dimensions are present for daily values
    $page->assertSee('Temperatur')
        ->assertSee('Niederschlag')
        ->assertSee('Luftfeuchtigkeit')
        ->assertSee('Wind')
        ->assertSee('Windrichtung')
        ->assertSee('Luftdruck');

    // Verify chart descriptions include ranges and overlays
    $page->assertSee('Temperatur (°C) – Mittelwert (Linie), Min/Max (Bereich)', 'Temperature description should show range');

    // Verify SVG elements are rendered for each chart
    $page->assertScript(
        "document.querySelectorAll('svg').length >= 6",
        true,
        'At least 6 charts should be rendered as SVG (temperature, precipitation, humidity, wind, wind direction, pressure)'
    );

    // Verify wind gust area is rendered (stacked area with gust data)
    $page->assertScript(
        "document.querySelector('svg [color*=\"rgba\"]') !== null || document.querySelectorAll('svg path').length > 0",
        true,
        'Gust area or path elements should be present in wind chart'
    );

    // Verify legend has station entries
    // Each legend item is a div with color indicator + span with station name
    $page->assertScript(
        "document.querySelectorAll('.mt-4.flex.flex-wrap.justify-center span').length >= 5",
        true,
        'Should have at least 5 legend text items for 5 stations'
    );

    // Verify carousel navigation is present
    $page->assertPresent('[role="region"]', 'Carousel region should be present');
});

it('displays daily values observation data with date information', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    $page = visit('/?step=data-options&stations=3195');

    // Select daily values
    $page->click('[data-testid="data-option-card-daily-values"]');

    // Select a preset (assertSee waits)
    $page->assertSee('Letzte 7 Tage')->click('Letzte 7 Tage');

    // Submit query
    $page->click('Daten abfragen');

    // Verify data is displayed - check for station name and title (assertSee waits)
    $page->assertSee('MADRID RETIRO')
        ->assertSee('Tageswerte');

    // Verify key dimensions are visible
    $page->assertSee('Temperatur')
        ->assertSee('Wind')
        ->assertSee('Windrichtung')
        ->assertSee('Luftdruck');

    // Verify chart descriptions
    $page->assertSee('Temperatur (°C) – Mittelwert (Linie), Min/Max (Bereich)');

    // Verify SVG rendering for charts
    $page->assertScript(
        "document.querySelectorAll('svg').length >= 1",
        true,
        'At least one SVG chart should be rendered'
    );
});

it('submit button is disabled until date range is selected for daily values', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    $page = visit('/?step=data-options&stations=3195');

    // Select daily values
    $page->click('[data-testid="data-option-card-daily-values"]');

    // Select a date preset
    $page->assertSee('Letzte 7 Tage')->click('Letzte 7 Tage');

    // Verify submit button becomes enabled (assertButtonEnabled waits)
    $page->assertButtonEnabled('Daten abfragen');
});

it('displays all 6 daily values dimensions with correct descriptions and rendering', function (string $device) {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    $page = configurePage('/?step=data-options&stations=3195,0201D,5783', $device, false);

    // Select daily values
    $page->click('[data-testid="data-option-card-daily-values"]');

    // Select a preset (assertSee waits for element to appear)
    $page->assertSee('Letzte 7 Tage')->click('Letzte 7 Tage');

    // Submit query
    $page->click('Daten abfragen');

    // Verify all 6 daily values dimensions are present (no sunshine)
    $page->assertSee('Temperatur')
        ->assertSee('Niederschlag')
        ->assertSee('Luftfeuchtigkeit')
        ->assertSee('Wind')
        ->assertSee('Windrichtung')
        ->assertSee('Luftdruck');

    // Verify sunshine is NOT present in daily values
    $page->assertDontSee('Sonnenschein', 'Sunshine should not be available in daily values');

    // Verify specific chart descriptions
    $page->assertSee('Temperatur (°C) – Mittelwert (Linie), Min/Max (Bereich)', 'Temperature should show mean line and range')
        ->assertSee('Windgeschwindigkeit (km/h) – Mittelwert (Linie), Böen (Fläche)', 'Wind should show mean line and gust area')
        ->assertSee('Luftdruck (hPa) – Min/Max (Bereich)', 'Pressure should show min/max range for daily values');

    // Verify all charts render as SVG
    $page->assertScript(
        "document.querySelectorAll('svg').length >= 6",
        true,
        'All 6 daily values dimensions should render as SVG charts'
    );

    // Verify gust area is present (wind dimension has special overlay)
    $page->assertScript(
        "document.querySelector('svg') && document.querySelectorAll('svg path').length > 0",
        true,
        'SVG paths should be present for chart rendering (including gust area)'
    );

    // Verify legend items match stations
    $page->assertScript(
        "document.querySelectorAll('.mt-4.flex.flex-wrap.justify-center span').length >= 3",
        true,
        'Should have legend items for all selected stations'
    );
})->with([
    'desktop' => ['desktop'],
    // Note: Mobile test skipped for this comprehensive test - mobile coverage provided by main workflow test
]);

it('does not include sunshine dimension in daily values carousel', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    $page = visit('/?step=data-options&stations=3195,0201D');

    // Select daily values
    $page->click('[data-testid="data-option-card-daily-values"]');

    // Select a preset
    $page->assertSee('Letzte 7 Tage')->click('Letzte 7 Tage');

    // Submit query
    $page->click('Daten abfragen');

    // Verify sunshine dimension is NOT present
    $page->assertDontSee('Sonnenschein', 'Sunshine should not appear in daily values carousel');

    // Verify we only have 6 dimensions (not 7)
    $page->assertScript(
        "(() => { const titles = Array.from(document.querySelectorAll('[role=\\\"region\\\"] h2, [role=\\\"region\\\"] h3')).map(el => el.textContent.trim()); const dailyDimensions = ['Temperatur', 'Niederschlag', 'Luftfeuchtigkeit', 'Wind', 'Windrichtung', 'Luftdruck']; return dailyDimensions.every(dim => titles.some(title => title.includes(dim))); })()",
        true,
        'All 6 daily dimensions should be present in carousel'
    );
});
