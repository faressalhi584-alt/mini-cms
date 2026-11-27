<?php

use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

function authorizationPostData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Article protege',
        'slug' => 'article-protege',
        'body' => 'Contenu assez long pour passer la validation.',
        'is_published' => '1',
    ], $overrides);
}

it('redirects a guest to the login page when opening the edit form', function () {
    $post = Post::factory()->create();

    $this->get(route('posts.edit', $post))
        ->assertRedirect(route('login'));
});

it('records the logged-in user as the author of a new post', function () {
    $author = User::factory()->create();

    $this->actingAs($author)
        ->post(route('posts.store'), authorizationPostData())
        ->assertRedirect();

    $this->assertDatabaseHas('posts', [
        'slug' => 'article-protege',
        'user_id' => $author->id,
    ]);
});

it('lets an author edit their own post', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)
        ->get(route('posts.edit', $post))
        ->assertOk();
});

it('lets an author update their own post', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)
        ->put(route('posts.update', $post), authorizationPostData())
        ->assertRedirect(route('posts.show', $post));

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'slug' => 'article-protege',
    ]);
});

it('forbids an author to edit the post of another user', function () {
    $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('posts.edit', $post))
        ->assertForbidden();
});

it('forbids an author to edit a post with no assigned author', function () {
    $post = Post::factory()->create(['user_id' => null]);

    $this->actingAs(User::factory()->create())
        ->get(route('posts.edit', $post))
        ->assertForbidden();
});

it('lets an admin edit a post with no assigned author', function () {
    $post = Post::factory()->create(['user_id' => null]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('posts.edit', $post))
        ->assertOk();
});

it('forbids an author to update the post of another user before validation', function () {
    $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs(User::factory()->create())
        ->put(route('posts.update', $post), authorizationPostData(['title' => '']))
        ->assertForbidden();

    $this->assertDatabaseMissing('posts', ['slug' => 'article-protege']);
});

it('forbids an author to delete the post of another user', function () {
    $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs(User::factory()->create())
        ->delete(route('posts.destroy', $post))
        ->assertForbidden();

    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});

it('lets an author delete their own post', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)
        ->delete(route('posts.destroy', $post))
        ->assertRedirect(route('posts.index'));

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

it('forbids an author to change publication of another user post', function () {
    $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs(User::factory()->create())
        ->patch(route('posts.publication', $post))
        ->assertForbidden();

    $this->assertDatabaseHas('posts', ['id' => $post->id, 'is_published' => false]);
});

it('lets an admin update any post', function () {
    $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('posts.update', $post), authorizationPostData())
        ->assertRedirect(route('posts.show', $post));

    $this->assertDatabaseHas('posts', ['id' => $post->id, 'slug' => 'article-protege']);
});

it('shows edit and delete controls only to an authorized user', function () {
    $post = Post::factory()->published()->create([
        'title' => 'Article protege',
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('posts.show', $post))
        ->assertOk()
        ->assertDontSee('Modifier')
        ->assertDontSee('Supprimer')
        ->assertDontSee('Dépublier');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('posts.show', $post))
        ->assertOk()
        ->assertSee('Modifier')
        ->assertSee('Supprimer')
        ->assertSee('Dépublier');
});

it('hides the post creation link from a guest', function () {
    $this->get(route('posts.index'))
        ->assertOk()
        ->assertDontSee('Nouvel article');

    $this->actingAs(User::factory()->create())
        ->get(route('posts.index'))
        ->assertOk()
        ->assertSee('Nouvel article');
});

it('keeps the category back office for admins', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.categories.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSee('Catégories');
});

it('denies category writes to a regular author', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.categories.store'), [
            'name' => 'Cuisine',
            'slug' => 'cuisine',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('categories', ['slug' => 'cuisine']);

    $this->get(route('posts.index'))
        ->assertOk()
        ->assertDontSee('Catégories');
});

it('lists only the authenticated user posts including drafts', function () {
    $author = User::factory()->create();
    Post::factory()->published()->create(['title' => 'Mon article publie', 'user_id' => $author->id]);
    Post::factory()->create(['title' => 'Mon brouillon', 'user_id' => $author->id]);
    Post::factory()->published()->create([
        'title' => 'Article d un autre auteur',
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs($author)
        ->get(route('my-posts'))
        ->assertOk()
        ->assertSee('Mon article publie')
        ->assertSee('Mon brouillon')
        ->assertDontSee('Article d un autre auteur');
});

it('redirects a guest from their posts page to login', function () {
    $this->get(route('my-posts'))
        ->assertRedirect(route('login'));
});
