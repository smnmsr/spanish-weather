<?php

use function Pest\Laravel\get;

it('renders the Stations Tool page', function () {
    $response = get('/');
    $response->assertSuccessful();
});
