<?php

use Illuminate\Support\Facades\Http;

it('zeigt den lustigen Siesta-Drawer wenn AEMET nicht erreichbar ist', function () {
    // Mock AEMET API to return fake station data
    Http::fake([
        '*/api/valores/climatologicos/inventarioestaciones/todasestaciones' => Http::response([
            'datos' => 'https://mock.aemet.es/stations-data',
        ]),
        '*/stations-data' => Http::response([
            [
                'idema' => '1234',
                'nombre' => 'Madrid Test',
                'latitud' => '40.4167',
                'longitud' => '-3.7038',
                'provincia' => 'Madrid',
            ],
        ]),
    ]);

    // Visit the home page
    $page = visit('/');

    // Navigate to data-options step
    $page->navigate('/?step=data-options');
    $page->wait(0.5);

    // Trigger the outage event
    $page->script("window.dispatchEvent(new CustomEvent('aemet:outage', {
        detail: {
            message: 'Hoppla! 🌩️ Die spanischen Wettergötter machen gerade Siesta. AEMET antwortet nicht.'
        }
    }))");

    $page->wait(0.5);

    // Assert the drawer is visible with the content
    $page->assertSee('Der Spanische Wetterdienst macht gerade Siesta')
        ->assertSee('Die API des Spanischen Wetterdiensts (AEMET) ist derzeit nicht erreichbar. Das passiert einigermassen oft, leider. Bite versuche es in ca. 10 Minuten erneut.')
        ->assertSee('Alles klar');

    // Take a screenshot of the outage drawer
    $page->screenshot(fullPage: true);

    // Test the close functionality
    $page->click('Alles klar')
        ->wait(0.5);

    // Drawer should be closed now
    $page->assertDontSee('Der Spanische Wetterdienst macht gerade Siesta');
});
