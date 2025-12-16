<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the station tool page', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stations/Tool')
        ->has('stations')
        ->has('selectedStations')
    );
})->skipOnCi();

it('reads selected stations from query parameters', function () {
    $selectedStations = ['station1', 'station2', 'station3'];
    $stationsParam = implode(',', $selectedStations);

    $response = $this->get(route('home', ['stations' => $stationsParam]));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stations/Tool')
        ->where('selectedStations', $selectedStations)
    );
})->skipOnCi();

it('persists selected stations across requests via URL', function () {
    $selectedStations = ['station1', 'station2'];
    $stationsParam = implode(',', $selectedStations);

    $response = $this->get(route('home', ['stations' => $stationsParam]));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stations/Tool')
        ->where('selectedStations', $selectedStations)
    );
})->skipOnCi();

it('handles empty station selection from query parameters', function () {
    $response = $this->get(route('home', ['stations' => '']));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stations/Tool')
        ->where('selectedStations', [])
    );
})->skipOnCi();
