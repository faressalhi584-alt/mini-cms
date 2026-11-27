<?php

use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->admin()->create());
});

function feedbackPostData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Article avec message',
        'slug' => 'article-avec-message',
        'body' => 'Contenu assez long pour passer la validation.',
        'is_published' => '1',
    ], $overrides);
}

it('shows a one-time success message after creating a post', function () {
    $response = $this->followingRedirects()
        ->post(route('posts.store'), feedbackPostData())
        ->assertOk()
        ->assertSee('Article créé.')
        ->assertSee('role="status"', false);

    $post = Post::where('slug', 'article-avec-message')->firstOrFail();
    $this->assertModelExists($post);

    $this->get(route('posts.show', $post))
        ->assertOk()
        ->assertDontSee('Article créé.');
});

it('shows a success message after updating a post', function () {
    $post = Post::factory()->create(['slug' => 'article-avec-message']);

    $this->followingRedirects()
        ->put(route('posts.update', $post), feedbackPostData(['title' => 'Article modifie']))
        ->assertOk()
        ->assertSee('Article mis à jour.');

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Article modifie',
    ]);
});

it('shows a success message after publishing a post', function () {
    $post = Post::factory()->create();

    $this->followingRedirects()
        ->patch(route('posts.publication', $post))
        ->assertOk()
        ->assertSee('Article publié.');

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'is_published' => true,
    ]);
});

it('shows a success message after unpublishing a post', function () {
    $post = Post::factory()->published()->create();

    $this->followingRedirects()
        ->patch(route('posts.publication', $post))
        ->assertOk()
        ->assertSee('Article dépublié.');

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'is_published' => false,
        'published_at' => null,
    ]);
});

it('shows a success message after deleting a post', function () {
    $post = Post::factory()->create();

    $this->followingRedirects()
        ->delete(route('posts.destroy', $post))
        ->assertOk()
        ->assertSee('Article supprimé.');

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

it('blocks all post writes while allowing read requests in read-only mode', function () {
    config(['site.read_only' => true]);
    $post = Post::factory()->create([
        'title' => 'Article original',
        'is_published' => false,
        'published_at' => null,
    ]);

    $this->get(route('posts.index'))->assertOk();
    $this->post(route('posts.store'), feedbackPostData())->assertStatus(503);
    $this->put(route('posts.update', $post), feedbackPostData())->assertStatus(503);
    $this->patch(route('posts.publication', $post))->assertStatus(503);
    $this->delete(route('posts.destroy', $post))->assertStatus(503);

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Article original',
        'is_published' => false,
        'published_at' => null,
    ]);
    $this->assertDatabaseMissing('posts', ['slug' => 'article-avec-message']);
});

it('shows the custom page for an unknown post', function () {
    $this->get('/posts/999')
        ->assertNotFound()
        ->assertSee('Article introuvable');
});

it('keeps the standard 404 page for an unknown category', function () {
    $this->get('/posts/categorie/inconnue')
        ->assertNotFound()
        ->assertDontSee('Article introuvable');
});

it('shows the read-only banner on the home page when the site is read-only', function () {
    config(['site.read_only' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Site en lecture seule');
});

it('hides the read-only banner when the site is writable', function () {
    config(['site.read_only' => false]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Site en lecture seule');
});

it('shows up to three recent published posts on the missing-post page', function () {
    collect([
        ['title' => 'Article recent premier', 'published_at' => '2026-01-04 10:00:00'],
        ['title' => 'Article recent deuxieme', 'published_at' => '2026-01-03 10:00:00'],
        ['title' => 'Article recent troisieme', 'published_at' => '2026-01-02 10:00:00'],
        ['title' => 'Article recent exclu', 'published_at' => '2026-01-01 10:00:00'],
    ])->map(fn (array $attributes) => Post::factory()->published()->create($attributes));
    Post::factory()->create(['title' => 'Brouillon exclu']);

    $this->get('/posts/999')
        ->assertNotFound()
        ->assertSeeInOrder([
            'Article recent premier',
            'Article recent deuxieme',
            'Article recent troisieme',
        ])
        ->assertDontSee('Article recent exclu')
        ->assertDontSee('Brouillon exclu');
});

it('hides recent post suggestions when no published posts exist', function () {
    Post::factory()->create(['title' => 'Brouillon seulement']);

    $this->get('/posts/999')
        ->assertNotFound()
        ->assertDontSee('Articles récents')
        ->assertDontSee('Brouillon seulement');
});

it('keeps missing-post errors as JSON when requested', function () {
    $this->getJson('/posts/999')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});
