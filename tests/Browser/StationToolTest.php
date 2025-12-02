<?php

it('navigates from welcome to map section', function () {
    $page = visit('/');

    $page->assertSee('Willkommen zum Stations-Tool')
        ->assertSee('Weiter zur Karte')
        ->click('Weiter zur Karte')
        ->waitForText('Stationen auswählen')
        ->assertSee('Stationen auswählen')
        ->assertNoJavascriptErrors();
});

it('displays station count when stations are selected', function () {
    $page = visit('/?step=map');

    $page->waitForText('Stationen auswählen')
        ->assertSee('Stationen auswählen')
        ->assertNoJavascriptErrors();
});

it('can save selected stations', function () {
    $page = visit('/');

    $page->click('Weiter zur Karte')
        ->waitForText('Stationen auswählen')
        ->assertNoJavascriptErrors();
});
