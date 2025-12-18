<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

it('may welcome the user', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    $page = visit('/');

    $page->assertSee('¡Hola Meteo!');
});

it('does not show authentication links', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    $page = visit('/');

    $page->assertDontSee('Log in')
        ->assertDontSee('Register')
        ->assertDontSee('Dashboard');
});
