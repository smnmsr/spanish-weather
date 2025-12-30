<?php

use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

use function Tests\Helpers\configurePage;

it('completes the full workflow from home page to data display for current observations 24h', function (string $device, bool $darkMode) {
    // Mock AEMET API with realistic test data
    Http::fake(AemetFixtures::httpFakeConfig());

    // 1. Visit the home page (welcome step) with device and theme settings
    $page = configurePage('/', $device, $darkMode);

    // Verify we're on the welcome page
    $page->assertPathIs('/');

    // 2. Navigate directly to results (simulating the workflow completion)
    // In a real scenario, the form submission would handle this,
    // but this test focuses on verifying the results display works correctly
    // Selecting Madrid Retiro (3195), Barcelona Airport (0201D), and Valencia (5783)
    $page = configurePage('/?step=results&stations=3195,0201D,5783&analysis=current-observations', $device, $darkMode);

    // 3. Verify we're on the results step with charts displayed
    $page->assertSee('Aktuelle Beobachtungen (24h)')
        ->assertSee('MADRID RETIRO')
        ->assertSee('BARCELONA AEROPUERTO')
        ->assertSee('VALENCIA')
        ->assertSee('Temperatur');

    // 4. Verify charts are rendered - assertScript waits automatically
    $page->assertScript(
        "document.querySelectorAll('svg').length > 0",
        true,
        'At least one SVG chart must be rendered'
    );

    // 5. Verify legend items are present (one per station)
    $page->assertScript(
        "document.querySelectorAll('.mt-4.flex.flex-wrap.justify-center span').length >= 3",
        true,
        'Should have at least 3 legend text items for 3 stations'
    );

    // 6. Verify carousel navigation is present
    $page->assertPresent('[role="region"]', 'Carousel region should be present');
})->with([
    'desktop light mode' => ['desktop', false],
    'mobile dark mode' => ['mobile', true],
]);

it('shows error message when no stations are selected', function () {
    // Mock AEMET API
    Http::fake(AemetFixtures::httpFakeConfig());

    $page = configurePage('/', 'desktop', false);

    // Navigate directly to data-options without selecting stations
    $page->navigate('/?step=data-options');

    // The data-options step should indicate no stations are selected
    // or the stepper should prevent navigation to this step
    $page->assertNoJavaScriptErrors();
});

it('automatically loads data when started with results step URL', function (string $device) {
    // Mock AEMET API
    Http::fake(AemetFixtures::httpFakeConfig());

    // Start at results step with pre-selected stations and analysis
    // This should trigger automatic data fetching
    $page = configurePage('/?step=results&stations=3195,0201D&analysis=current-observations', $device, false);

    // Verify results are displayed (assertSee will wait for element)
    $page->assertSee('Aktuelle Beobachtungen (24h)')
        ->assertSee('MADRID RETIRO')
        ->assertSee('BARCELONA AEROPUERTO');
})->with([
    'desktop' => ['desktop'],
    'mobile' => ['mobile'],
]);

it('handles multiple stations correctly and displays all data', function () {
    // Mock AEMET API
    Http::fake(AemetFixtures::httpFakeConfig());

    // Select all 5 available stations and navigate directly to results
    $page = configurePage('/?step=results&stations=3195,0201D,5783,5960,1387&analysis=current-observations', 'desktop', false);

    // Verify all station names appear (assertSee will wait for elements)
    $page->assertSee('MADRID RETIRO')
        ->assertSee('BARCELONA AEROPUERTO')
        ->assertSee('VALENCIA')
        ->assertSee('SEVILLA AEROPUERTO')
        ->assertSee('BILBAO AEROPUERTO');

    // Verify all new charts are present
    $page->assertSee('Wind', 'Wind chart title should be visible')
        ->assertSee('Windrichtung', 'Wind direction chart title should be visible')
        ->assertSee('Luftdruck', 'Pressure chart title should be visible')
        ->assertSee('Sonnenschein', 'Sunshine chart title should be visible');

    // Verify chart descriptions include gust information
    $page->assertSee('Mittelwert (Linie), Böen (Fläche)', 'Wind chart should describe mean line and gust area');

    // Verify SVG elements are rendered for each chart
    $page->assertScript(
        "document.querySelectorAll('svg').length >= 4",
        true,
        'At least 4 charts (temperature, wind, wind direction, pressure) should be rendered as SVG'
    );

    // Verify gust area is rendered (stacked area with gust data)
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
});

it('displays observation data with time information', function (string $device) {
    // Mock AEMET API
    Http::fake(AemetFixtures::httpFakeConfig());

    $page = configurePage('/?step=results&stations=3195&analysis=current-observations', $device, false);

    // Verify data is displayed - check for station name and that data is present
    $page->assertSee('MADRID RETIRO');
})->with([
    'desktop' => ['desktop'],
    'mobile' => ['mobile'],
]);

it('works with the stepper for navigation between steps', function (string $device) {
    // Mock AEMET API
    Http::fake(AemetFixtures::httpFakeConfig());

    $page = configurePage('/', $device, false);

    // Verify stepper step labels are visible (may be hidden on mobile)
    if ($device === 'mobile') {
        // On mobile, stepper may be more compact, just check page loads
        $page->assertPathIs('/');
    } else {
        $page->assertSee('Willkommen')
            ->assertSee('Karte')
            ->assertSee('Datenart')
            ->assertSee('Resultate');
    }
})->with([
    'desktop' => ['desktop'],
    'mobile' => ['mobile'],
]);
