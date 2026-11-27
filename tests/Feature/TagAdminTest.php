<?php

use App\Models\Post;
use App\Models\Tag;

beforeEach(function () {
    $this->withoutVite();
});

function tagAdminData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Laravel',
        'slug' => 'laravel',
    ], $overrides);
}

it('creates a tag and flashes a message', function () {
    $this->post(route('admin.tags.store'), tagAdminData())
        ->assertRedirect(route('admin.tags.index'))
        ->assertSessionHas('success', 'Tag créé.');

    $this->assertDatabaseHas('tags', ['slug' => 'laravel']);
});

it('rejects an empty tag with French messages', function () {
    $this->post(route('admin.tags.store'), ['name' => '', 'slug' => ''])
        ->assertSessionHasErrors([
            'name' => 'Le champ nom est obligatoire.',
            'slug' => 'Le champ slug est obligatoire.',
        ]);

    $this->assertDatabaseCount('tags', 0);
});

it('rejects a slug already used by another tag', function () {
    Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);

    $this->post(route('admin.tags.store'), tagAdminData(['slug' => 'php']))
        ->assertSessionHasErrors(['slug' => 'Ce slug est déjà utilisé par un autre tag.']);
});

it('blocks tag writes when the site is read-only', function () {
    config(['site.read_only' => true]);

    $this->post(route('admin.tags.store'), tagAdminData())
        ->assertStatus(503)
        ->assertSee('Le site est en lecture seule.');

    $this->assertDatabaseCount('tags', 0);
});

it('still shows the tag list when the site is read-only', function () {
    config(['site.read_only' => true]);

    $this->get(route('admin.tags.index'))->assertOk();
});

it('updates a tag and keeps its existing slug', function () {
    $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

    $this->put(route('admin.tags.update', $tag), tagAdminData(['name' => 'Laravel 13']))
        ->assertRedirect(route('admin.tags.index'))
        ->assertSessionHas('success', 'Tag mis à jour.');

    $this->assertDatabaseHas('tags', [
        'id' => $tag->id,
        'name' => 'Laravel 13',
        'slug' => 'laravel',
    ]);
});

it('rejects another tag slug on update with the French message', function () {
    $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
    Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);

    $this->put(route('admin.tags.update', $tag), tagAdminData(['slug' => 'php']))
        ->assertSessionHasErrors(['slug' => 'Ce slug est déjà utilisé par un autre tag.']);
});

it('deletes a tag and keeps its posts', function () {
    $tag = Tag::factory()->create();
    $post = Post::factory()->create();
    $post->tags()->attach($tag);

    $this->delete(route('admin.tags.destroy', $tag))
        ->assertRedirect(route('admin.tags.index'))
        ->assertSessionHas('success', 'Tag supprimé.');

    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    $this->assertDatabaseHas('posts', ['id' => $post->id]);
    $this->assertDatabaseMissing('post_tag', [
        'post_id' => $post->id,
        'tag_id' => $tag->id,
    ]);
});
