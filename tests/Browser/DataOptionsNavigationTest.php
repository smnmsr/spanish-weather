<?php

it('preserves selected data query type when navigating back from results', function () {
    // Start with some selected stations and the data-options step
    $page = visit('/?step=data-options&stations=3195,0201D');

    // Wait for the carousel to load
    $page->waitFor('[data-testid="data-option-daily-values"]')
        ->assertSee('Aktuelle Beobachtungen (24h)')
        ->assertSee('Tageswerte');

    // Click on daily-values card
    $page->click('[data-testid="data-option-card-daily-values"]')
        ->pause(500); // Wait for carousel animation

    // Verify the URL contains the analysis parameter
    expect($page->page->url())->toContain('analysis=daily-values');

    // Click the "Zurück zur Auswahl" button to go back to map
    $page->click('Zurück zur Auswahl')
        ->waitForText('Stationen auswählen')
        ->assertSee('Stationen auswählen');

    // Click the "Datenart" step in the stepper to go back to data options
    $page->click('[data-step="3"]')
        ->waitFor('[data-testid="data-option-daily-values"]')
        ->pause(500); // Wait for carousel to initialize

    // Verify the URL still contains analysis=daily-values
    expect($page->page->url())->toContain('analysis=daily-values');

    // The carousel should have scrolled to daily-values automatically
    // We can verify this by checking if the daily-values card is centered/selected
    $page->assertNoJavascriptErrors();
});

it('preserves selected data query type when clicking stepper steps', function () {
    // Start directly at data-options with daily-values already selected
    $page = visit('/?step=data-options&stations=3195,0201D&analysis=daily-values');

    // Wait for the carousel to load
    $page->waitFor('[data-testid="data-option-daily-values"]')
        ->pause(500); // Wait for carousel initialization

    // Verify the URL contains the analysis parameter
    expect($page->page->url())->toContain('analysis=daily-values');

    // Click the "Karte" step in the stepper to navigate to map
    $page->click('[data-step="2"]')
        ->waitForText('Stationen auswählen')
        ->assertSee('Stationen auswählen');

    // Click back to "Datenart" step
    $page->click('[data-step="3"]')
        ->waitFor('[data-testid="data-option-daily-values"]')
        ->pause(500); // Wait for carousel to reinitialize

    // Verify the URL still contains analysis=daily-values
    expect($page->page->url())->toContain('analysis=daily-values');

    $page->assertNoJavascriptErrors();
});

it('defaults to current-observations only when no analysis is selected', function () {
    // Visit data-options without any analysis parameter
    $page = visit('/?step=data-options&stations=3195,0201D');

    // Wait for the carousel to load
    $page->waitFor('[data-testid="data-option-current-observations"]')
        ->pause(500); // Wait for carousel initialization

    // After carousel initializes, it should default to current-observations
    // and add it to the URL
    $page->pause(500);
    expect($page->page->url())->toContain('analysis=current-observations');

    $page->assertNoJavascriptErrors();
});

it('preserves extreme-values selection when navigating', function () {
    // Test with a different option to ensure it's not hardcoded
    $page = visit('/?step=data-options&stations=3195,0201D&analysis=extreme-values');

    $page->waitFor('[data-testid="data-option-extreme-values"]')
        ->pause(500);

    expect($page->page->url())->toContain('analysis=extreme-values');

    // Navigate to map
    $page->click('[data-step="2"]')
        ->waitForText('Stationen auswählen');

    // Navigate back to data options
    $page->click('[data-step="3"]')
        ->waitFor('[data-testid="data-option-extreme-values"]')
        ->pause(500);

    // Should still have extreme-values
    expect($page->page->url())->toContain('analysis=extreme-values');

    $page->assertNoJavascriptErrors();
});
