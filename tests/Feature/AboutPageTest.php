<?php

beforeEach(function () {
    $this->withoutVite();
});

it('shows Fares Salhi as the project author', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Fares Salhi');
});
