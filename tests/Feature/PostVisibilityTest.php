<?php

use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

it('shows a published post to a guest', function () {
    $post = Post::factory()->published()->create();

    $this->get(route('posts.show', $post))
        ->assertOk();
});

it('forbids a guest from viewing a draft', function () {
    $post = Post::factory()->create(['is_published' => false]);

    $this->get(route('posts.show', $post))
        ->assertForbidden();
});

it('shows a draft only to its author', function () {
    $author = User::factory()->create();
    $post = Post::factory()->for($author)->create(['is_published' => false]);

    $this->actingAs($author)
        ->get(route('posts.show', $post))
        ->assertOk();

    $this->actingAs(User::factory()->create())
        ->get(route('posts.show', $post))
        ->assertForbidden();
});

it('shows any draft to an admin', function () {
    $post = Post::factory()->for(User::factory()->create())->create(['is_published' => false]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('posts.show', $post))
        ->assertOk();
});

it('lists published posts for guests and only the author\'s drafts for authors', function () {
    $author = User::factory()->create();
    Post::factory()->for($author)->create(['title' => 'Brouillon personnel', 'is_published' => false]);
    Post::factory()->for(User::factory()->create())->create(['title' => 'Brouillon externe', 'is_published' => false]);
    Post::factory()->published()->create(['title' => 'Article public']);

    $this->get(route('posts.index'))
        ->assertSee('Article public')
        ->assertDontSee('Brouillon personnel')
        ->assertDontSee('Brouillon externe');

    $this->actingAs($author)
        ->get(route('posts.index'))
        ->assertSee('Article public')
        ->assertSee('Brouillon personnel')
        ->assertDontSee('Brouillon externe');
});
