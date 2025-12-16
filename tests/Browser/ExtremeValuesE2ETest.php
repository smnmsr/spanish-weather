<?php

use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

use function Tests\Helpers\configurePage;

it('completes the full workflow from home page to extreme values display', function (string $device, bool $darkMode) {
    // Mock AEMET API with realistic test data
    Http::fake(AemetFixtures::httpFakeConfig());

    // 1. Visit the home page (welcome step) with device and theme settings
    $page = configurePage('/', $device, $darkMode);

    // Verify we're on the welcome page
    $page->assertPathIs('/');

    // 2. Navigate to data-options step with pre-selected stations via URL
    // Selecting Madrid Retiro (3195), Barcelona Airport (0201D)
    $page = configurePage('/?step=data-options&stations=3195,0201D', $device, $darkMode);

    // Verify we're on the correct path
    $page->assertPathIs('/');

    // 3. Select "Extremwerte" data query type
    // Use test ID for more reliable clicking
    $selector = '[data-testid="data-option-card-extreme-values"]';
    $page->assertPresent($selector);
    $page->click($selector);

    // 4. Submit data query
    $page->click('Daten abfragen');

    // 5. Verify results are displayed (will wait for element to appear)
    $page->assertSee('Extremwerte');

    // 6. Verify the extreme metric slides (card titles) are visible
    // We expect per-metric slides instead of grouped tables now
    $page->assertSee('Höchste Temperatur')
        ->assertSee('Tiefste Temperatur')
        ->assertSee('Max. Tagesniederschlag')
        ->assertSee('Stärkste Böe');

    // 7. Verify station names appear in results
    $page->assertSee('MADRID RETIRO')
        ->assertSee('BARCELONA AEROPUERTO');

    // 8. Verify card content shows expected units/labels
    // Temperature values include °C
    $page->assertSee('°C')
        ->assertSee('Durchschnitt Hoch')
        ->assertSee('Durchschnitt Tief');

    // 9. Verify precipitation extreme values slide
    $page->assertSee('Max. Monatsniederschlag')
        ->assertSee('Min. Monatsniederschlag')
        ->assertSee('mm');

    // 10. Verify wind extreme values slide
    $page->assertSee('Stärkste Böe')
        ->assertSee('km/h')
        ->assertSee('Richtung:');
})->with([
    'desktop light mode' => ['desktop', false],
    'mobile dark mode' => ['mobile', true],
]);
