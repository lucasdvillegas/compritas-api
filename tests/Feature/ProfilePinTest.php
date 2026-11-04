<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration generates a unique eight digit pin', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Lucas',
        'email' => 'lucas@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $user = User::where('email', 'lucas@example.com')->firstOrFail();

    expect($user->pin)->toMatch('/^\d{8}$/');
});

test('profile returns the pin only to its owner', function () {
    [$user] = actingAsWorkspaceUser();

    $this->getJson('/api/profile')
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonPath('data.pin', $user->pin)
        ->assertJsonMissingPath('data.user.pin');
});

test('pin is hidden when the user model is serialized', function () {
    [$user] = actingAsWorkspaceUser();

    expect($user->toArray())->not->toHaveKey('pin');
});

test('regenerate pin replaces the previous one', function () {
    [$user] = actingAsWorkspaceUser();
    $previousPin = $user->pin;

    $this->postJson('/api/profile/pin/regenerate')
        ->assertOk()
        ->assertJsonPath('data.pin', fn ($pin) => $pin !== $previousPin);

    $user->refresh();

    expect($user->pin)->not->toBe($previousPin)
        ->and($user->pin)->toMatch('/^\d{8}$/');
});

test('profile name can be updated', function () {
    [$user] = actingAsWorkspaceUser();

    $this->patchJson('/api/profile', ['name' => 'Lucas Villegas'])
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.name', 'Lucas Villegas')
        ->assertJsonPath('data.user.email', $user->email);

    expect($user->fresh()->name)->toBe('Lucas Villegas');
});

test('profile name is required to update it', function () {
    actingAsWorkspaceUser();

    $this->patchJson('/api/profile', ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    $this->patchJson('/api/profile', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('profile name cannot exceed the maximum length', function () {
    actingAsWorkspaceUser();

    $this->patchJson('/api/profile', ['name' => str_repeat('a', 256)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('profile requires authentication', function () {
    $this->getJson('/api/profile')->assertUnauthorized();
    $this->patchJson('/api/profile', ['name' => 'Lucas'])->assertUnauthorized();
    $this->postJson('/api/profile/pin/regenerate')->assertUnauthorized();
});
