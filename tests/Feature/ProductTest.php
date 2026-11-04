<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('index returns only the current workspace products ordered by name', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $lacteos = Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Lácteos']);
    $bebidas = Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bebidas']);

    Product::factory()->create([
        'workspace_id' => $workspace->id,
        'category_id' => $lacteos->id,
        'name' => 'Yogur natural',
    ]);

    Product::factory()->create([
        'workspace_id' => $workspace->id,
        'category_id' => $bebidas->id,
        'name' => 'Agua mineral',
    ]);

    Product::factory()->create(['name' => 'Producto de otro workspace']);

    $this->getJson('/api/products')
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Productos obtenidos correctamente.',
        ])
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Agua mineral')
        ->assertJsonPath('data.0.category.name', 'Bebidas')
        ->assertJsonPath('data.1.name', 'Yogur natural')
        ->assertJsonPath('data.1.category.name', 'Lácteos');
});

test('store creates a product in the current workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $category = Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bebidas']);

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
                'workspace_id' => $workspace->id,
                'category_id' => $category->id,
                'category' => ['id' => $category->id, 'name' => 'Bebidas'],
            ],
        ]);

    $this->assertDatabaseHas('products', [
        'name' => 'Agua mineral',
        'workspace_id' => $workspace->id,
        'category_id' => $category->id,
    ]);
});

test('store allows a product without a category', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $this->postJson('/api/products', ['name' => 'Agua mineral'])
        ->assertCreated()
        ->assertJsonPath('data.category_id', null)
        ->assertJsonPath('data.category', null);

    $this->assertDatabaseHas('products', [
        'name' => 'Agua mineral',
        'workspace_id' => $workspace->id,
        'category_id' => null,
    ]);
});

test('store defaults the unit to unit', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $this->postJson('/api/products', ['name' => 'Agua mineral'])
        ->assertCreated()
        ->assertJsonPath('data.unit', 'unit');

    $this->assertDatabaseHas('products', [
        'name' => 'Agua mineral',
        'workspace_id' => $workspace->id,
        'unit' => 'unit',
    ]);
});

test('store accepts a product sold by weight', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $this->postJson('/api/products', [
        'name' => 'Manzana',
        'unit' => 'kg',
    ])
        ->assertCreated()
        ->assertJsonPath('data.unit', 'kg');

    $this->assertDatabaseHas('products', [
        'name' => 'Manzana',
        'workspace_id' => $workspace->id,
        'unit' => 'kg',
    ]);
});

test('store rejects an invalid unit', function () {
    actingAsWorkspaceUser();

    $this->postJson('/api/products', [
        'name' => 'Manzana',
        'unit' => 'litro',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('unit');
});

test('store requires a name', function () {
    actingAsWorkspaceUser();

    $this->postJson('/api/products', ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('store rejects a category from another workspace', function () {
    actingAsWorkspaceUser();

    $category = Category::factory()->create();

    $this->postJson('/api/products', [
        'name' => 'Agua mineral',
        'category_id' => $category->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');
});

test('store requires a unique name within the workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    Product::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Agua mineral',
    ]);

    $this->postJson('/api/products', ['name' => 'Agua mineral'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('store allows the same product name in another workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    Product::factory()->create([
        'workspace_id' => Workspace::factory()->create()->id,
        'name' => 'Agua mineral',
    ]);

    $this->postJson('/api/products', ['name' => 'Agua mineral'])
        ->assertCreated();

    expect(Product::where('workspace_id', $workspace->id)->where('name', 'Agua mineral')->count())->toBe(1);
});

test('show returns a product with its category', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $category = Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bebidas']);
    $product = Product::factory()->create([
        'workspace_id' => $workspace->id,
        'category_id' => $category->id,
        'name' => 'Agua mineral',
    ]);

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
    actingAsWorkspaceUser();

    $this->getJson('/api/products/999')->assertNotFound();
});

test('show returns not found for a product from another workspace', function () {
    actingAsWorkspaceUser();

    $product = Product::factory()->create();

    $this->getJson("/api/products/{$product->id}")->assertNotFound();
});

test('update changes the product', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $category = Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bebidas']);
    $newCategory = Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Lácteos']);
    $product = Product::factory()->create([
        'workspace_id' => $workspace->id,
        'category_id' => $category->id,
        'name' => 'Agua mineral',
    ]);

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
    [, $workspace] = actingAsWorkspaceUser();

    $product = Product::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Agua mineral',
    ]);

    $this->putJson("/api/products/{$product->id}", [
        'name' => 'Agua mineral',
        'category_id' => null,
    ])->assertOk();
});

test('update changes the unit', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $product = Product::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Manzana',
        'unit' => 'unit',
    ]);

    $this->putJson("/api/products/{$product->id}", [
        'name' => 'Manzana',
        'unit' => 'kg',
    ])
        ->assertOk()
        ->assertJsonPath('data.unit', 'kg');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'unit' => 'kg',
    ]);
});

test('update requires a unique name within the workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    Product::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Agua mineral']);
    $product = Product::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Yogur natural']);

    $this->putJson("/api/products/{$product->id}", [
        'name' => 'Agua mineral',
        'category_id' => null,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('update rejects a category from another workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $product = Product::factory()->create(['workspace_id' => $workspace->id]);
    $category = Category::factory()->create();

    $this->putJson("/api/products/{$product->id}", [
        'name' => $product->name,
        'category_id' => $category->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');
});

test('update returns not found for a product from another workspace', function () {
    actingAsWorkspaceUser();

    $product = Product::factory()->create();

    $this->putJson("/api/products/{$product->id}", ['name' => 'Otro nombre'])
        ->assertNotFound();
});

test('destroy deletes a product', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $product = Product::factory()->create(['workspace_id' => $workspace->id]);

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
    actingAsWorkspaceUser();

    $this->deleteJson('/api/products/999')->assertNotFound();
});

test('destroy returns not found for a product from another workspace', function () {
    actingAsWorkspaceUser();

    $product = Product::factory()->create();

    $this->deleteJson("/api/products/{$product->id}")->assertNotFound();
});

test('deleting a category keeps its products with a null category', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $category = Category::factory()->create(['workspace_id' => $workspace->id]);
    $product = Product::factory()->create([
        'workspace_id' => $workspace->id,
        'category_id' => $category->id,
    ]);

    $this->deleteJson("/api/categories/{$category->id}")->assertOk();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'category_id' => null,
    ]);
});
