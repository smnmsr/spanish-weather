<?php

it('zeigt die Stationen als Cluster auf der Karte', function () {
    $page = visit('/?step=map');

    $page->assertNoJavascriptErrors()
        ->assertSee('OpenStreetMap')
        ->screenshot(true);
});
