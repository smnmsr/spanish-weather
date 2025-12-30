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

    // 6. Verify ALL 8 extreme metric slides (card titles) are visible
    $page->assertSee('Höchste Temperatur')
        ->assertSee('Tiefste Temperatur')
        ->assertSee('Durchschnitt Hoch')
        ->assertSee('Durchschnitt Tief')
        ->assertSee('Max. Tagesniederschlag')
        ->assertSee('Max. Monatsniederschlag')
        ->assertSee('Min. Monatsniederschlag')
        ->assertSee('Stärkste Böe');

    // 7. Verify station names appear in results
    $page->assertSee('MADRID RETIRO')
        ->assertSee('BARCELONA AEROPUERTO');

    // 8. Verify temperature cards show units
    $page->assertSee('°C');

    // 9. Verify precipitation cards show units
    $page->assertSee('mm');

    // 10. Verify wind cards show units and direction label
    $page->assertSee('km/h')
        ->assertSee('Richtung:');

    // 11. Verify actual extreme values render from fixtures (text-only, no charts)
    $page->assertSee('42.0 °C') // absolute max
        ->assertSee('-14.0 °C') // absolute min
        ->assertSee('31.5 °C') // warmest month mean
        ->assertSee('-1.5 °C') // coldest month mean
        ->assertSee('112.0 mm') // max daily precipitation
        ->assertSee('420.0 mm') // max monthly precipitation
        ->assertSee('0.0 mm') // min monthly precipitation
        ->assertSee('120 km/h') // strongest gust
        ->assertSee('Richtung: 240°')
        ->assertSee('14. Februar 1989, 03:45');
})->with([
    'desktop light mode' => ['desktop', false],
    'mobile dark mode' => ['mobile', true],
]);
