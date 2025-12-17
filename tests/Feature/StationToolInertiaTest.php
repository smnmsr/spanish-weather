<?php

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Helpers\AemetFixtures;

// Set up HTTP mocks for all tests
beforeEach(function () {
    Http::fake(AemetFixtures::httpFakeConfig());
});

// ===========================
// Inertia Page Rendering Tests
// ===========================

it('renders the station tool page with mocked data', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stations/Tool')
        ->has('stations')
        ->has('selectedStations')
    );
});

it('reads selected stations from query parameters', function () {
    $selectedStations = ['station1', 'station2', 'station3'];
    $stationsParam = implode(',', $selectedStations);

    $response = $this->get(route('home', ['stations' => $stationsParam]));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stations/Tool')
        ->where('selectedStations', $selectedStations)
    );
});

it('persists selected stations across requests via URL', function () {
    $selectedStations = ['station1', 'station2'];
    $stationsParam = implode(',', $selectedStations);

    $response = $this->get(route('home', ['stations' => $stationsParam]));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stations/Tool')
        ->where('selectedStations', $selectedStations)
    );
});

it('handles empty station selection from query parameters', function () {
    $response = $this->get(route('home', ['stations' => '']));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stations/Tool')
        ->where('selectedStations', [])
    );
});

// ==========================
// Map Page Rendering Tests
// ==========================

it('renders the stations map on the home page', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stations/Tool')
        ->has('stations')
        ->has('selectedStations')
    );
});
