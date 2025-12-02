<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the stations map on the home page', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stations/Tool')
        ->has('stations')
        ->has('selectedStations')
    );
})->skipOnCi();
