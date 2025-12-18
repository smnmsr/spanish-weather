<?php

use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

beforeEach(function () {
    // Mock AEMET API for all tests
    Http::fake(AemetFixtures::httpFakeConfig());
});

it('preserves selected data query type when navigating between steps via url', function () {
    // Visit with analysis already set
    $page = visit('/?step=data-options&analysis=daily-values');

    // Verify we're on data-options and daily-values is available
    $page->assertSee('Welche Daten möchten Sie abfragen?')
        ->assertPresent('[data-testid="data-option-daily-values"]');

    expect($page->url())->toContain('daily-values');

    // Navigate to a different option
    $page->click('[data-testid="data-option-card-extreme-values"]');

    expect($page->url())->toContain('extreme-values');

    $page->assertNoJavascriptErrors();
});

it('updates url when clicking different data option cards', function () {
    $page = visit('/?step=data-options');

    $page->assertSee('Welche Daten möchten Sie abfragen?');

    // Click monthly-yearly-trends
    $page->click('[data-testid="data-option-card-monthly-yearly-trends"]');

    expect($page->url())->toContain('monthly-yearly-trends');

    // Switch to daily-values
    $page->click('[data-testid="data-option-card-daily-values"]');

    expect($page->url())->toContain('daily-values');

    $page->assertNoJavascriptErrors();
});

it('displays correct data options based on url parameter', function () {
    // Visit with extreme-values preset
    $page = visit('/?step=data-options&analysis=extreme-values');

    $page->assertPresent('[data-testid="data-option-extreme-values"]');

    expect($page->url())->toContain('extreme-values');

    $page->assertNoJavascriptErrors();
});

it('can navigate back to map and preserves analysis in url', function () {
    $page = visit('/?step=data-options&analysis=current-observations');

    $page->assertSee('Welche Daten möchten Sie abfragen?');

    expect($page->url())->toContain('current-observations');

    // Click back button and verify navigation
    $page->click('Zurück zur Auswahl')
        ->assertSee('Klicken Sie auf die Karte');

    expect($page->url())->toContain('current-observations');

    $page->assertNoJavascriptErrors();
});
