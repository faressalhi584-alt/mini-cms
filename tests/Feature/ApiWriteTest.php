<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function apiPostData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Article envoyé par API',
        'slug' => 'article-envoye-par-l-api',
        'excerpt' => 'Un article créé par l’API.',
        'body' => 'Un contenu assez long pour valider la création par API.',
        'is_published' => true,
    ], $overrides);
}

it('returns 401 for unauthenticated API post writes', function () {
    $post = Post::factory()->create();

    $this->postJson('/api/posts', apiPostData())
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');

    $this->putJson('/api/posts/'.$post->id, apiPostData())
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');

    $this->deleteJson('/api/posts/'.$post->id)
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');

    $this->assertDatabaseCount('posts', 1);
});

it('creates an API post for the authenticated author', function () {
    $author = User::factory()->create();
    $category = Category::factory()->create();
    $tag = Tag::factory()->create(['name' => 'API']);
    Sanctum::actingAs($author);

    $response = $this->postJson('/api/posts', apiPostData([
        'category_id' => $category->id,
        'tags' => [$tag->id],
    ]))
        ->assertCreated()
        ->assertJsonPath('data.slug', 'article-envoye-par-l-api')
        ->assertJsonPath('data.author', $author->name)
        ->assertJsonPath('data.category.id', $category->id)
        ->assertJsonPath('data.tags.0', 'API');

    $postId = $response->json('data.id');

    $this->assertDatabaseHas('posts', [
        'id' => $postId,
        'slug' => 'article-envoye-par-l-api',
        'user_id' => $author->id,
        'category_id' => $category->id,
        'is_published' => true,
    ]);

    $this->assertDatabaseHas('post_tag', [
        'post_id' => $postId,
        'tag_id' => $tag->id,
    ]);
});

it('rejects invalid API post data with French validation messages', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/posts', apiPostData(['title' => '']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title'])
        ->assertJsonPath('errors.title.0', 'Le champ titre est obligatoire.');

    $this->assertDatabaseMissing('posts', ['slug' => 'article-envoye-par-l-api']);
});

it('updates an API post and synchronizes its tags for the owner', function () {
    $author = User::factory()->create();
    $post = Post::factory()->for($author)->create(['slug' => 'article-initial']);
    $oldTag = Tag::factory()->create();
    $newTag = Tag::factory()->create(['name' => 'Nouveau']);
    $post->tags()->attach($oldTag);
    Sanctum::actingAs($author);

    $this->putJson('/api/posts/'.$post->id, apiPostData([
        'slug' => 'article-modifie-par-api',
        'tags' => [$newTag->id],
    ]))
        ->assertOk()
        ->assertJsonPath('data.slug', 'article-modifie-par-api')
        ->assertJsonPath('data.tags.0', 'Nouveau');

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'slug' => 'article-modifie-par-api',
        'user_id' => $author->id,
    ]);

    $this->assertDatabaseHas('post_tag', [
        'post_id' => $post->id,
        'tag_id' => $newTag->id,
    ]);

    $this->assertDatabaseMissing('post_tag', [
        'post_id' => $post->id,
        'tag_id' => $oldTag->id,
    ]);
});

it('forbids updating another authors API post before validation', function () {
    $post = Post::factory()->create([
        'slug' => 'article-d-un-autre-auteur',
        'user_id' => User::factory()->create()->id,
    ]);
    Sanctum::actingAs(User::factory()->create());

    $this->putJson('/api/posts/'.$post->id, apiPostData(['title' => '']))
        ->assertForbidden()
        ->assertJsonPath('message', 'This action is unauthorized.');

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'slug' => 'article-d-un-autre-auteur',
    ]);
});

it('forbids deleting another authors API post', function () {
    $post = Post::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);
    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson('/api/posts/'.$post->id)
        ->assertForbidden()
        ->assertJsonPath('message', 'This action is unauthorized.');

    $this->assertModelExists($post);
});

it('lets the author delete their own API post', function () {
    $author = User::factory()->create();
    $post = Post::factory()->for($author)->create();
    Sanctum::actingAs($author);

    $this->deleteJson('/api/posts/'.$post->id)
        ->assertNoContent();

    $this->assertModelMissing($post);
});

it('issues a bearer token that opens the protected API user endpoint', function () {
    $user = User::factory()->create();

    $token = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertCreated()
        ->assertJsonStructure(['token', 'token_type'])
        ->assertJsonPath('token_type', 'Bearer')
        ->json('token');

    $this->assertDatabaseCount('personal_access_tokens', 1);

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('email', $user->email);
});

it('rejects an incorrect API password with a neutral message', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'incorrect',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Ces identifiants ne correspondent à aucun compte.');

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('revokes only the current API token on logout', function () {
    $user = User::factory()->create();
    $currentToken = $user->createToken('current')->plainTextToken;
    $otherToken = $user->createToken('other')->plainTextToken;

    $this->withToken($currentToken)
        ->postJson('/api/logout')
        ->assertNoContent();

    $this->assertDatabaseCount('personal_access_tokens', 1);

    $this->app['auth']->forgetGuards();

    $this->withToken($currentToken)
        ->getJson('/api/user')
        ->assertUnauthorized();

    $this->app['auth']->forgetGuards();

    $this->withToken($otherToken)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('email', $user->email);
});
