<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('index requires authentication', function () {
    $this->getJson('/api/products')->assertUnauthorized();
});

test('store requires authentication', function () {
    $category = Category::factory()->create();

    $this->postJson('/api/products', [
        'name' => 'Leche entera',
        'category_id' => $category->id,
    ])->assertUnauthorized();
});

test('index returns products ordered by name with their category', function () {
    Sanctum::actingAs(User::factory()->create());

    $lacteos = Category::factory()->create(['name' => 'Lácteos']);
    $bebidas = Category::factory()->create(['name' => 'Bebidas']);

    Product::factory()->for($lacteos)->create(['name' => 'Yogur natural']);
    Product::factory()->for($bebidas)->create(['name' => 'Agua mineral']);

    $this->getJson('/api/products')
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Productos obtenidos correctamente.',
        ])
        ->assertJsonPath('data.0.name', 'Agua mineral')
        ->assertJsonPath('data.0.category.name', 'Bebidas')
        ->assertJsonPath('data.1.name', 'Yogur natural')
        ->assertJsonPath('data.1.category.name', 'Lácteos');
});

test('store creates a product', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = Category::factory()->create(['name' => 'Bebidas']);

    $this->postJson('/api/products', [
        'name' => 'Agua mineral',
        'category_id' => $category->id,
    ])
        ->assertCreated()
        ->assertJson([
            'status' => true,
            'message' => 'Producto creado correctamente.',
            'data' => [
                'name' => 'Agua mineral',
                'category_id' => $category->id,
                'category' => ['id' => $category->id, 'name' => 'Bebidas'],
            ],
        ]);

    $this->assertDatabaseHas('products', [
        'name' => 'Agua mineral',
        'category_id' => $category->id,
    ]);
});

test('store requires a name', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = Category::factory()->create();

    $this->postJson('/api/products', [
        'name' => '',
        'category_id' => $category->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('store requires a category', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/products', ['name' => 'Agua mineral'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');
});

test('store requires an existing category', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/products', [
        'name' => 'Agua mineral',
        'category_id' => 999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');
});

test('store requires a unique name', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = Category::factory()->create();
    Product::factory()->for($category)->create(['name' => 'Agua mineral']);

    $this->postJson('/api/products', [
        'name' => 'Agua mineral',
        'category_id' => $category->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('show returns a product with its category', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = Category::factory()->create(['name' => 'Bebidas']);
    $product = Product::factory()->for($category)->create(['name' => 'Agua mineral']);

    $this->getJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Producto obtenido correctamente.',
            'data' => [
                'id' => $product->id,
                'name' => 'Agua mineral',
                'category' => ['id' => $category->id, 'name' => 'Bebidas'],
            ],
        ]);
});

test('show returns not found for a missing product', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/products/999')->assertNotFound();
});

test('update changes the product', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = Category::factory()->create(['name' => 'Bebidas']);
    $newCategory = Category::factory()->create(['name' => 'Lácteos']);
    $product = Product::factory()->for($category)->create(['name' => 'Agua mineral']);

    $this->putJson("/api/products/{$product->id}", [
        'name' => 'Yogur natural',
        'category_id' => $newCategory->id,
    ])
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => [
                'name' => 'Yogur natural',
                'category_id' => $newCategory->id,
                'category' => ['id' => $newCategory->id, 'name' => 'Lácteos'],
            ],
        ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Yogur natural',
        'category_id' => $newCategory->id,
    ]);
});

test('update allows keeping the same name', function () {
    Sanctum::actingAs(User::factory()->create());

    $product = Product::factory()->create(['name' => 'Agua mineral']);

    $this->putJson("/api/products/{$product->id}", [
        'name' => 'Agua mineral',
        'category_id' => $product->category_id,
    ])->assertOk();
});

test('update requires a unique name', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = Category::factory()->create();
    Product::factory()->for($category)->create(['name' => 'Agua mineral']);
    $product = Product::factory()->for($category)->create(['name' => 'Yogur natural']);

    $this->putJson("/api/products/{$product->id}", [
        'name' => 'Agua mineral',
        'category_id' => $category->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('update requires a valid category', function () {
    Sanctum::actingAs(User::factory()->create());

    $product = Product::factory()->create();

    $this->putJson("/api/products/{$product->id}", [
        'name' => $product->name,
        'category_id' => 999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');
});

test('destroy deletes a product', function () {
    Sanctum::actingAs(User::factory()->create());

    $product = Product::factory()->create();

    $this->deleteJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Producto eliminado correctamente.',
            'data' => null,
        ]);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('destroy returns not found for a missing product', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson('/api/products/999')->assertNotFound();
});

test('deleting a category deletes its products', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    $this->deleteJson("/api/categories/{$category->id}")->assertOk();

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});
