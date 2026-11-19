<?php

use App\Models\Post;

beforeEach(function () {
    $this->withoutVite();
});

it('shows only the three latest published posts on the home page', function () {
    foreach (range(1, 4) as $number) {
        Post::create([
            'title' => "Publié $number",
            'slug' => "publie-$number",
            'body' => "Contenu de l'article publié numéro $number.",
            'is_published' => true,
            'published_at' => now()->addDays($number),
        ]);
    }

    Post::create([
        'title' => 'Brouillon caché',
        'slug' => 'brouillon-cache',
        'body' => 'Ce brouillon ne doit pas apparaître sur la page accueil.',
        'is_published' => false,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Publié 4', 'Publié 3', 'Publié 2'])
        ->assertDontSee('Publié 1')
        ->assertDontSee('Brouillon caché');
});
