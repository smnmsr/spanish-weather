<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\AemetFixtures;

it('zeigt die Stationen als Cluster auf der Karte', function () {
    // Clear cache and set up HTTP mocks
    Cache::flush();
    Http::fake(AemetFixtures::httpFakeConfig());

    $page = visit('/?step=map');

    $page->assertNoJavascriptErrors()
        ->assertSee('OpenStreetMap')
        ->screenshot(true);
});
