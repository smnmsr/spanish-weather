<?php

use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

use function Tests\Helpers\configurePage;

it('completes the full workflow from home page to forecast data display', function (string $device, bool $darkMode) {
    // Mock AEMET API with realistic test data
    Http::fake(AemetFixtures::httpFakeConfig());

    // 1. Navigate directly to data-options step with pre-selected stations
    // Selecting Madrid Retiro (3195), Barcelona Airport (0201D), and Valencia (5783)
    $page = configurePage('/?step=data-options&stations=3195,0201D,5783', $device, $darkMode);

    // 2. Select "Vorhersage (7 Tage)" forecast data query type
    $selector = '[data-testid="data-option-card-forecast"]';
    $page->assertPresent($selector)->click($selector);

    // 3. Wait for municipality auto-selection to complete (assertSee waits)
    $page->assertSee('Daten abfragen');

    // 4. Submit data query
    $page->click('Daten abfragen');

    // 5. Verify forecast results are displayed (assertSee implicitly waits)
    $page->assertSee('Vorhersage (7 Tage)')
        ->assertSee('Madrid')
        ->assertSee('Barcelona')
        ->assertSee('Valencia');

    // 6. Verify temperature dimension is available
    $page->assertSee('Temperatur');
})->with([
    'desktop light mode' => ['desktop', false],
    'mobile dark mode' => ['mobile', true],
]);

it('displays 7-day forecast with min/max temperature ranges', function () {
    // Mock AEMET API
    Http::fake(AemetFixtures::httpFakeConfig());

    // Start at results step with pre-selected municipalities
    $page = configurePage('/?step=results&stations=3195&analysis=forecast&municipalities=28079', 'desktop', false);

    // Verify forecast data is displayed (assertSee waits)
    $page->assertSee('Vorhersage (7 Tage)')
        ->assertSee('Madrid');

    // Should see temperature dimension option
    $page->assertSee('Temperatur');

    // Should not see any "no data" messages
    $page->assertDontSee('Keine Daten verfügbar');

    $page->assertNoJavaScriptErrors();
});

it('handles multiple municipalities for forecast correctly', function () {
    // Mock AEMET API
    Http::fake(AemetFixtures::httpFakeConfig());

    // Select multiple stations that map to different municipalities
    $page = configurePage('/?step=data-options&stations=3195,0201D,5783', 'desktop', false);

    // Select forecast
    $page->click('[data-testid="data-option-card-forecast"]');

    // Wait for municipalities to be auto-selected
    $page->assertSee('Daten abfragen');

    // Submit query
    $page->click('Daten abfragen');

    // Verify all municipality names appear in results (assertSee waits for elements)
    $page->assertSee('Madrid')
        ->assertSee('Barcelona')
        ->assertSee('Valencia');
});

it('automatically loads forecast when started with results step URL', function (string $device) {
    // Mock AEMET API
    Http::fake(AemetFixtures::httpFakeConfig());

    // Start at results step with pre-selected stations and forecast analysis
    // This should trigger automatic data fetching
    $page = configurePage('/?step=results&stations=3195,0201D&analysis=forecast&municipalities=28079,08019', $device, false);

    // Verify forecast results are displayed (assertSee will wait for element)
    $page->assertSee('Vorhersage (7 Tage)')
        ->assertSee('Madrid')
        ->assertSee('Barcelona');
})->with([
    'desktop' => ['desktop'],
    'mobile' => ['mobile'],
]);
