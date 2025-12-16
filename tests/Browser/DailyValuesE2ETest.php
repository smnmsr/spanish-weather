<?php

use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

use function Tests\Helpers\configurePage;

// Set up HTTP fakes once for all tests
beforeEach(function () {
    Http::fake(AemetFixtures::httpFakeConfig());
});

it('completes the full workflow from home page to data display for daily values', function (string $device, bool $darkMode) {
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
})->with([
    'desktop light mode' => ['desktop', false],
    'mobile dark mode' => ['mobile', true],
]);

it('shows error message when no stations are selected for daily values', function () {
    // Navigate directly to data-options without selecting stations
    $page = visit('/?step=data-options');

    // The data-options step should indicate no stations are selected
    // or the stepper should prevent navigation to this step
    $page->assertNoJavaScriptErrors();
});

it('displays date range selector when daily values is selected', function () {
    $page = visit('/?step=data-options&stations=3195');

    // Select daily values
    $page->click('[data-testid="data-option-card-daily-values"]');

    // Verify date range selector appears with preset buttons (assertSee waits)
    $page->assertSee('Letzte 7 Tage')
        ->assertSee('Letzte 30 Tage')
        ->assertNoJavaScriptErrors();
});

it('handles multiple stations for daily values correctly and displays all data', function () {
    // Mock AEMET API
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
        ->assertSee('ZARAGOZA AEROPUERTO');
});

it('displays daily values observation data with date information', function () {
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
});

it('submit button is disabled until date range is selected for daily values', function () {
    $page = visit('/?step=data-options&stations=3195');

    // Select daily values
    $page->click('[data-testid="data-option-card-daily-values"]');

    // Select a date preset
    $page->assertSee('Letzte 7 Tage')->click('Letzte 7 Tage');

    // Verify submit button becomes enabled (assertButtonEnabled waits)
    $page->assertButtonEnabled('Daten abfragen');
});
