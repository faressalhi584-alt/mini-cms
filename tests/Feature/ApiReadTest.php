<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

it('lists only published posts with paginated public fields', function () {
    $publishedPosts = Post::factory()->count(2)->published()->create();
    Post::factory()->create(['title' => 'Brouillon privé']);

    $this->getJson(route('api.posts.index', ['per_page' => 1]))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [[
                'id',
                'title',
                'slug',
                'excerpt',
                'published_at',
                'author',
                'category',
                'tags',
                'url',
            ]],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'per_page', 'total'],
        ])
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('links.next', fn (?string $url): bool => str_contains($url ?? '', 'per_page=1'))
        ->assertJsonMissingPath('data.0.body')
        ->assertJsonMissingPath('data.0.email')
        ->assertJsonMissingPath('data.0.user_id');
});

it('returns a published post with its body and loaded relationships', function () {
    $author = User::factory()->create(['name' => 'Auteur API']);
    $category = Category::factory()->create(['name' => 'Catégorie API']);
    $tag = Tag::factory()->create(['name' => 'Tag API']);
    $post = Post::factory()
        ->published()
        ->for($author)
        ->for($category)
        ->create(['body' => 'Le contenu complet de cet article publié.']);
    $post->tags()->attach($tag);

    $this->getJson(route('api.posts.show', $post))
        ->assertOk()
        ->assertJsonPath('data.id', $post->id)
        ->assertJsonPath('data.body', 'Le contenu complet de cet article publié.')
        ->assertJsonPath('data.author', 'Auteur API')
        ->assertJsonPath('data.category.name', 'Catégorie API')
        ->assertJsonPath('data.tags', ['Tag API']);
});

it('returns a JSON 403 for a draft and a JSON 404 for an unknown post', function () {
    $draft = Post::factory()->create(['is_published' => false]);

    $this->getJson(route('api.posts.show', $draft))
        ->assertForbidden()
        ->assertJsonPath('message', 'This action is unauthorized.');

    $this->getJson('/api/posts/999999')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('rejects a page size above fifty with a French JSON validation error', function () {
    $this->getJson(route('api.posts.index', ['per_page' => 51]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.per_page.0', 'Le champ nombre par page ne doit pas dépasser 50.');
});
