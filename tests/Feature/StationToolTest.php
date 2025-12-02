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

it('saves selected stations to session', function () {
    $selectedStations = ['station1', 'station2', 'station3'];

    $response = $this->post(route('save.selection'), [
        'stations' => $selectedStations,
    ]);

    $response->assertRedirect(route('home'));

    expect(session('selected_stations'))->toBe($selectedStations);
})->skipOnCi();

it('persists selected stations across requests', function () {
    $selectedStations = ['station1', 'station2'];

    // Save stations
    $this->post(route('save.selection'), [
        'stations' => $selectedStations,
    ]);

    // Load the tool page again
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stations/Tool')
        ->where('selectedStations', $selectedStations)
    );
})->skipOnCi();

it('handles empty station selection', function () {
    $response = $this->post(route('save.selection'), [
        'stations' => [],
    ]);

    $response->assertRedirect(route('home'));

    expect(session('selected_stations'))->toBe([]);
})->skipOnCi();
