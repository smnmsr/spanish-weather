<?php

it('may welcome the user', function () {
    $page = visit('/');

    $page->assertSee("Let's get started");
});

it('does not show authentication links', function () {
    $page = visit('/');

    $page->assertDontSee('Log in')
        ->assertDontSee('Register')
        ->assertDontSee('Dashboard');
});
