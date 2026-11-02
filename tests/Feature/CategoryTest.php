<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('index requires authentication', function () {
    $this->getJson('/api/categories')->assertUnauthorized();
});

test('index requires authentication without an accept header', function () {
    $this->get('/api/categories')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('store requires authentication', function () {
    $this->postJson('/api/categories', ['name' => 'Bebidas'])->assertUnauthorized();
});

test('index returns categories ordered by name', function () {
    Sanctum::actingAs(User::factory()->create());

    Category::factory()->create(['name' => 'Lácteos']);
    Category::factory()->create(['name' => 'Bebidas']);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Categories retrieved successfully',
        ])
        ->assertJsonPath('data.0.name', 'Bebidas')
        ->assertJsonPath('data.1.name', 'Lácteos');
});

test('store creates a category', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/categories', ['name' => 'Bebidas'])
        ->assertCreated()
        ->assertJson([
            'status' => true,
            'message' => 'Category created successfully',
            'data' => ['name' => 'Bebidas'],
        ]);

    $this->assertDatabaseHas('categories', ['name' => 'Bebidas']);
});

test('store requires a name', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/categories', ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('store requires a unique name', function () {
    Sanctum::actingAs(User::factory()->create());

    Category::factory()->create(['name' => 'Bebidas']);

    $this->postJson('/api/categories', ['name' => 'Bebidas'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    expect(Category::where('name', 'Bebidas')->count())->toBe(1);
});
