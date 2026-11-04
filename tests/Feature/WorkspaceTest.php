<?php

use App\Models\User;
use App\Models\Workspace;
use App\Services\DefaultCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration creates a personal workspace with an owner membership', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Lucas',
        'email' => 'lucas@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $user = User::where('email', 'lucas@example.com')->firstOrFail();
    $workspace = $user->currentWorkspace();

    expect($workspace->name)->toBe('Espacio personal')
        ->and($workspace->owner_id)->toBe($user->id);

    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
});

test('factory users receive a personal workspace', function () {
    $user = User::factory()->create();

    expect($user->workspaces)->toHaveCount(1)
        ->and($user->currentWorkspace()->owner_id)->toBe($user->id);
});

test('registration seeds the default catalog in the personal workspace', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Lucas',
        'email' => 'lucas@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $workspace = User::where('email', 'lucas@example.com')->firstOrFail()->currentWorkspace();
    $catalog = app(DefaultCatalogService::class);

    expect($workspace->categories()->count())->toBe(count(DefaultCatalogService::CATEGORIES))
        ->and($workspace->products()->count())->toBe($catalog->productCount());

    $this->assertDatabaseHas('products', [
        'workspace_id' => $workspace->id,
        'name' => 'Manzana Roja',
        'unit' => 'kg',
    ]);
});

test('factory users do not receive the default catalog', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace();

    expect($workspace->categories()->count())->toBe(0)
        ->and($workspace->products()->count())->toBe(0);
});

test('a workspace exposes its owner, members and catalog', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace();

    expect($workspace->owner->id)->toBe($user->id)
        ->and($workspace->members)->toHaveCount(1)
        ->and($workspace->members->first()->pivot->role)->toBe('owner');
});

test('workspaces from different users are isolated', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();

    expect($first->currentWorkspace()->id)
        ->not->toBe($second->currentWorkspace()->id);
});

test('a workspace can be created with an owner membership through its factory', function () {
    $workspace = Workspace::factory()->create();

    expect($workspace->members()->where('users.id', $workspace->owner_id)->exists())->toBeTrue();
});
