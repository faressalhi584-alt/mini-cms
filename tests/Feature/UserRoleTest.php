<?php

use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

it('allows an admin to change another user role in either direction', function () {
    $author = User::factory()->create(['name' => 'Fares Auteur', 'email' => 'auteur@example.com']);
    $otherAdmin = User::factory()->admin()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.role', $author), ['role' => 'admin'])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', 'Rôle mis à jour.');

    $this->patch(route('admin.users.role', $otherAdmin), ['role' => 'author'])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', 'Rôle mis à jour.');

    $this->assertDatabaseHas('users', ['id' => $author->id, 'role' => 'admin']);
    $this->assertDatabaseHas('users', ['id' => $otherAdmin->id, 'role' => 'author']);
});

it('lists each user name, email and role for an admin', function () {
    User::factory()->create([
        'name' => 'Auteur de test',
        'email' => 'auteur@example.com',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Auteur de test')
        ->assertSee('auteur@example.com')
        ->assertSee('author')
        ->assertSee('admin');
});

it('forbids an admin from changing their own role', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.role', $admin), ['role' => 'author'])
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
});

it('forbids an author from opening the role management page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('rejects a role outside the allowed values', function () {
    $author = User::factory()->create();

    $this->from(route('admin.users.index'))
        ->actingAs(User::factory()->admin()->create())
        ->followingRedirects()
        ->patch(route('admin.users.role', $author), ['role' => 'superadmin'])
        ->assertOk()
        ->assertSee('La valeur sélectionnée pour le champ rôle est invalide.');

    $this->assertDatabaseHas('users', ['id' => $author->id, 'role' => 'author']);
});
