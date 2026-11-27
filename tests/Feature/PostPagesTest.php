<?php

use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->admin()->create());
});

it('shows a paginated list of posts', function () {
    foreach (range(1, 6) as $number) {
        Post::create([
            'title' => "Article $number",
            'slug' => "article-$number",
            'body' => "Contenu assez long pour l'article numéro $number.",
        ]);
    }

    $this->get(route('posts.index'))
        ->assertOk()
        ->assertSee('Article 6')
        ->assertSee('Article 3')
        ->assertDontSee('Article 2')
        ->assertSee('posts?page=2');

    $this->get(route('posts.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Article 2')
        ->assertSee('Article 1')
        ->assertDontSee('Article 3');
});

it('shows an empty state when no posts exist', function () {
    $this->get(route('posts.index'))
        ->assertOk()
        ->assertSee('Aucun article');
});

it('shows a post resolved by route model binding', function () {
    $post = Post::create([
        'title' => 'Article complet',
        'slug' => 'article-complet',
        'body' => 'Texte complet de cet article de test.',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $this->get(route('posts.show', $post))
        ->assertOk()
        ->assertSee('Article complet')
        ->assertSee('Texte complet de cet article de test.');
});

it('returns not found for an unknown post', function () {
    $this->get('/posts/999')->assertNotFound();
});

it('filters the archive by publication year', function () {
    Post::create([
        'title' => 'Article actuel',
        'slug' => 'article-actuel',
        'body' => 'Article publié pendant l’année demandée.',
        'is_published' => true,
        'published_at' => '2026-03-01 10:00:00',
    ]);

    Post::create([
        'title' => 'Article ancien',
        'slug' => 'article-ancien',
        'body' => 'Article publié pendant une autre année.',
        'is_published' => true,
        'published_at' => '2025-03-01 10:00:00',
    ]);

    Post::create([
        'title' => 'Brouillon',
        'slug' => 'brouillon',
        'body' => 'Un brouillon sans date de publication.',
    ]);

    $this->get(route('posts.archive', 2026))
        ->assertOk()
        ->assertSee('Article actuel')
        ->assertDontSee('Article ancien')
        ->assertDontSee('Brouillon');
});

it('shows the create form', function () {
    $this->get(route('posts.create'))
        ->assertOk()
        ->assertSee('Nouvel article');
});

it('returns validation errors and preserves input for an empty submission', function () {
    $this->from(route('posts.create'))
        ->post(route('posts.store'), [
            'title' => '',
            'slug' => '',
            'body' => '',
        ])
        ->assertRedirect(route('posts.create'))
        ->assertSessionHasErrors(['title', 'slug', 'body'])
        ->assertSessionHas('_old_input.title', '');

    $this->assertDatabaseCount('posts', 0);
});

it('rejects a slug that is not lowercase alphanumeric with hyphens', function () {
    $this->from(route('posts.create'))
        ->post(route('posts.store'), [
            'title' => 'Un article avec un titre',
            'slug' => 'Slug invalide',
            'body' => 'Un contenu suffisamment long pour cet article.',
        ])
        ->assertRedirect(route('posts.create'))
        ->assertSessionHasErrors('slug');

    $this->assertDatabaseCount('posts', 0);
});

it('rejects article content shorter than twenty characters', function () {
    $this->from(route('posts.create'))
        ->post(route('posts.store'), [
            'title' => 'Article trop court',
            'slug' => 'article-trop-court',
            'body' => 'Trop court.',
        ])
        ->assertRedirect(route('posts.create'))
        ->assertSessionHasErrors('body');

    $this->assertDatabaseCount('posts', 0);
});

it('rejects a slug that is already in use', function () {
    Post::create([
        'title' => 'Article existant',
        'slug' => 'article-existant',
        'body' => 'Un contenu suffisamment long pour cet article.',
    ]);

    $this->from(route('posts.create'))
        ->post(route('posts.store'), [
            'title' => 'Autre article',
            'slug' => 'article-existant',
            'body' => 'Un contenu suffisamment long pour cet article.',
        ])
        ->assertRedirect(route('posts.create'))
        ->assertSessionHasErrors('slug');

    $this->assertDatabaseCount('posts', 1);
});

it('creates a draft and redirects to its detail page', function () {
    $this->post(route('posts.store'), [
        'title' => 'Premier article créé',
        'slug' => 'premier-article-cree',
        'body' => 'Un contenu suffisamment long pour cet article.',
    ]);

    $post = Post::where('slug', 'premier-article-cree')->firstOrFail();

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Premier article créé',
        'is_published' => false,
        'published_at' => null,
    ]);

    $this->get(route('posts.show', $post))
        ->assertOk()
        ->assertSee('Premier article créé')
        ->assertSee('Brouillon');
});

it('publishes an article immediately when requested', function () {
    $this->freezeTime();

    $this->post(route('posts.store'), [
        'title' => 'Article publié',
        'slug' => 'article-publie',
        'body' => 'Un contenu suffisamment long pour cet article.',
        'is_published' => '1',
    ]);

    $post = Post::where('slug', 'article-publie')->firstOrFail();

    $this->assertTrue($post->is_published);
    $this->assertSame(now()->toDateTimeString(), $post->published_at->toDateTimeString());
});
