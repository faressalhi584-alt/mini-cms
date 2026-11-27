<?php

it('requires a Sanctum token for the API user endpoint', function () {
    $this->getJson('/api/user')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
});
