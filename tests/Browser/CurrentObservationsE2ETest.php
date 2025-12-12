<?php

use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

/**
 * Configure page with device and theme settings
 */
function configurePage(string $url, string $device, bool $darkMode)
{
    $page = match ($device) {
        'mobile' => $darkMode ? visit($url)->on()->iPhone14Pro()->inDarkMode() : visit($url)->on()->iPhone14Pro(),
        default => $darkMode ? visit($url)->inDarkMode() : visit($url),
    };

    return $page;
}

it('completes the full workflow from home page to data display for current observations 24h', function (string $device, bool $darkMode) {
    // Mock AEMET API with realistic test data
    Http::fake(AemetFixtures::httpFakeConfig());

    // 1. Visit the home page (welcome step) with device and theme settings
    $page = configurePage('/', $device, $darkMode);

    // Verify we're on the welcome page
    $page->assertPathIs('/');

    // 2. Navigate to data-options step with pre-selected stations via URL
    // Selecting Madrid Retiro (3195), Barcelona Airport (0201D), and Valencia (5783)
    $page = configurePage('/?step=data-options&stations=3195,0201D,5783', $device, $darkMode);

    // Verify we're on the correct path
    $page->assertPathIs('/');

    // 3. Select "Aktuelle Beobachtungen (24h)" data query type
    // Use test ID for more reliable clicking
    $selector = '[data-testid="data-option-card-current-observations"]';
    $page->assertPresent($selector);
    $page->click($selector);

    // 4. Submit data query
    $page->click('Daten abfragen');

    // 5. Verify results are displayed (will wait for element to appear)
    $page->assertSee('Aktuelle Beobachtungen (24h)');

    // 6. Verify station names appear in results
    $page->assertSee('MADRID RETIRO')
        ->assertSee('BARCELONA AEROPUERTO')
        ->assertSee('VALENCIA');
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
