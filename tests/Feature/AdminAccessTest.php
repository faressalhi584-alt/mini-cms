<?php

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->withoutVite();
});

it('redirects a guest from the back office to the login page', function () {
    $this->get(route('admin.tags.index'))
        ->assertRedirect(route('login'));
});

it('forbids an author from the category and tag back office', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.categories.index'))
        ->assertForbidden()
        ->assertSee('Réservé aux administrateurs.');

    $this->get(route('admin.tags.index'))
        ->assertForbidden();
});

it('forbids an author to create a tag', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.tags.store'), ['name' => 'Interdit', 'slug' => 'interdit'])
        ->assertForbidden();

    $this->assertDatabaseMissing('tags', ['slug' => 'interdit']);
});

it('forbids an author to delete a tag', function () {
    $tag = Tag::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.tags.destroy', $tag))
        ->assertForbidden();

    $this->assertDatabaseHas('tags', ['id' => $tag->id]);
});

it('opens the back office to an admin', function () {
    $admin = User::factory()->admin()->create();
    Category::factory()->create();
    Tag::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.categories.index'))
        ->assertOk();

    $this->get(route('admin.tags.index'))
        ->assertOk();

    $this->get(route('home'))
        ->assertSee(route('admin.categories.index'))
        ->assertSee(route('admin.tags.index'))
        ->assertSee(route('admin.users.index'));
});

it('hides the back office links from an author', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee(route('admin.categories.index'))
        ->assertDontSee(route('admin.tags.index'))
        ->assertDontSee(route('admin.users.index'));
});

it('grants the manage-tags gate only to admins', function () {
    expect(Gate::forUser(User::factory()->create())->allows('manage-tags'))->toBeFalse()
        ->and(Gate::forUser(User::factory()->admin()->create())->allows('manage-tags'))->toBeTrue();
});
