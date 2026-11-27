<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->withoutVite();
});

function authRegisterData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Amira Ben Salah',
        'email' => 'amira@example.com',
        'password' => 'motdepasse123',
        'password_confirmation' => 'motdepasse123',
    ], $overrides);
}

it('registers an author with a hashed password and logs them in', function () {
    $this->post(route('register.store'), authRegisterData())
        ->assertRedirect(route('posts.index'))
        ->assertSessionHas('success', 'Compte créé : bienvenue, Amira Ben Salah.');

    $user = User::where('email', 'amira@example.com')->firstOrFail();

    expect($user->password)->not->toBe('motdepasse123')
        ->and(Hash::check('motdepasse123', $user->password))->toBeTrue()
        ->and($user->role)->toBe('author');

    $this->assertAuthenticatedAs($user);
});

it('does not let a registration request assign the admin role', function () {
    $this->post(route('register.store'), authRegisterData(['role' => 'admin']))
        ->assertRedirect(route('posts.index'));

    $user = User::where('email', 'amira@example.com')->firstOrFail();

    expect($user->role)->toBe('author');
});

it('shows French validation errors for an invalid registration', function () {
    $this->post(route('register.store'), authRegisterData([
        'name' => '',
        'email' => 'invalid',
        'password' => 'short',
        'password_confirmation' => 'different',
    ]))
        ->assertSessionHasErrors([
            'name' => 'Le champ nom est obligatoire.',
            'email' => 'Le champ adresse e-mail doit être une adresse e-mail valide.',
            'password' => 'Le champ mot de passe doit contenir au moins 8 caractères.',
        ]);

    $this->assertGuest();
});

it('requires a name, email and password when registering', function () {
    $this->post(route('register.store'), [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
    ])->assertSessionHasErrors([
        'name' => 'Le champ nom est obligatoire.',
        'email' => 'Le champ adresse e-mail est obligatoire.',
        'password' => 'Le champ mot de passe est obligatoire.',
    ]);
});

it('refuses a password that does not match its confirmation', function () {
    $this->post(route('register.store'), authRegisterData(['password_confirmation' => 'autrechose']))
        ->assertSessionHasErrors(['password' => 'La confirmation du champ mot de passe ne correspond pas.']);

    $this->assertGuest();
});

it('refuses an already registered email address', function () {
    User::factory()->create(['email' => 'amira@example.com']);

    $this->post(route('register.store'), authRegisterData())
        ->assertSessionHasErrors(['email' => 'La valeur du champ adresse e-mail est déjà utilisée.']);
});

it('logs in with the right password', function () {
    $user = User::factory()->create(['email' => 'auteur@example.com']);

    $this->post(route('login.store'), ['email' => 'auteur@example.com', 'password' => 'password'])
        ->assertRedirect(route('posts.index'))
        ->assertSessionHas('success', 'Vous êtes connecté.');

    $this->assertAuthenticatedAs($user);
});

it('returns to the page that required authentication after login', function () {
    $user = User::factory()->create(['email' => 'auteur@example.com']);

    $this->get(route('posts.create'))
        ->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => 'auteur@example.com', 'password' => 'password'])
        ->assertRedirect(route('posts.create'));

    $this->assertAuthenticatedAs($user);
});

it('requires email and password when logging in', function () {
    $this->post(route('login.store'), [])
        ->assertSessionHasErrors([
            'email' => 'Le champ adresse e-mail est obligatoire.',
            'password' => 'Le champ mot de passe est obligatoire.',
        ]);
});

it('refuses a wrong password with a neutral message and keeps only the email', function () {
    User::factory()->create(['email' => 'auteur@example.com']);

    $this->post(route('login.store'), ['email' => 'auteur@example.com', 'password' => 'mauvais'])
        ->assertSessionHasErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.'])
        ->assertSessionHas('_old_input.email', 'auteur@example.com')
        ->assertSessionMissing('_old_input.password');

    $this->assertGuest();
});

it('uses the same neutral message for an unknown email', function () {
    $this->post(route('login.store'), [
        'email' => 'absent@example.com',
        'password' => 'mauvais',
    ])->assertSessionHasErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.']);

    $this->assertGuest();
});

it('creates a remember token when the remember option is selected', function () {
    $user = User::factory()->create([
        'email' => 'auteur@example.com',
        'remember_token' => null,
    ]);

    $this->post(route('login.store'), [
        'email' => 'auteur@example.com',
        'password' => 'password',
        'remember' => '1',
    ])->assertRedirect(route('posts.index'));

    expect($user->fresh()->remember_token)->not->toBeNull();
});

it('limits repeated login attempts for the same email and address', function () {
    User::factory()->create(['email' => 'auteur@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'email' => 'auteur@example.com',
            'password' => 'mauvais',
        ])->assertRedirect();
    }

    $this->post(route('login.store'), [
        'email' => 'auteur@example.com',
        'password' => 'mauvais',
    ])->assertStatus(429);
});

it('logs out and shows a message', function () {
    $this->actingAs(User::factory()->create())
        ->followingRedirects()
        ->post(route('logout'))
        ->assertOk()
        ->assertSee('Vous êtes déconnecté.');

    $this->assertGuest();
});
