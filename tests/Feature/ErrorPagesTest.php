<?php

use App\Models\Category;

beforeEach(function () {
    $this->withoutVite();
});

it('shows the styled 404 page for an unknown URL', function () {
    $this->get('/page-inexistante')
        ->assertNotFound()
        ->assertSee('Page introuvable')
        ->assertSee("Retour à l'accueil", false);
});

it('shows the styled 503 page when the site is read-only', function () {
    config(['site.read_only' => true]);

    $this->post(route('posts.store'), [])
        ->assertStatus(503)
        ->assertSee('Service momentanément indisponible')
        ->assertSee("Retour à l'accueil", false);
});

it('shows category suggestions when a public category does not exist', function () {
    Category::factory()->create(['name' => 'Cuisine', 'slug' => 'cuisine']);

    $this->get(route('posts.category', ['category' => 'inconnue']))
        ->assertNotFound()
        ->assertSee('Catégorie introuvable')
        ->assertSee('Cuisine')
        ->assertSee(route('posts.category', ['category' => 'cuisine']), false);
});

it('keeps unknown category errors as JSON when requested', function () {
    $this->getJson(route('posts.category', ['category' => 'inconnue']))
        ->assertNotFound()
        ->assertJsonStructure(['message'])
        ->assertDontSee('Catégorie introuvable');
});
