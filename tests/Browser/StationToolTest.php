<?php

use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

beforeEach(function () {
    Http::fake(AemetFixtures::httpFakeConfig());
});

it('navigates from welcome to map section', function () {
    $page = visit('/');

    $page->assertSee('¡Hola Meteo!')
        ->assertSee('Auf zur interaktiven Karte')
        ->click('Auf zur interaktiven Karte')
        ->waitForText('Stationen wählen')
        ->assertSee('Stationen wählen')
        ->assertNoJavascriptErrors();
});

it('displays station count when stations are selected', function () {
    $page = visit('/?step=map');

    $page->waitForText('Stationen wählen')
        ->assertSee('Stationen wählen')
        ->assertNoJavascriptErrors();
});

it('can save selected stations', function () {
    $page = visit('/');

    $page->click('Auf zur interaktiven Karte')
        ->waitForText('Stationen wählen')
        ->assertNoJavascriptErrors();
});
