<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createProductInWorkspace($workspace, string $name, string $unit = 'unit'): Product
{
    $category = Category::firstOrCreate([
        'workspace_id' => $workspace->id,
        'name' => 'Frutas',
    ]);

    return Product::factory()->create([
        'workspace_id' => $workspace->id,
        'category_id' => $category->id,
        'name' => $name,
        'unit' => $unit,
    ]);
}

test('store requires authentication', function () {
    $list = ShoppingList::factory()->create();

    $this->postJson("/api/lists/{$list->id}/items", ['product_id' => 1])
        ->assertUnauthorized();
});

test('store adds a product to the list with a snapshot', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items", ['product_id' => $product->id])
        ->assertCreated()
        ->assertJson([
            'status' => true,
            'message' => 'Producto agregado a la lista.',
            'data' => [
                'product_id' => $product->id,
                'product_name' => 'Manzana',
                'category_name' => 'Frutas',
                'quantity' => 1,
                'checked' => false,
                'checked_at' => null,
            ],
        ]);

    $this->assertDatabaseHas('shopping_list_items', [
        'shopping_list_id' => $list->id,
        'product_id' => $product->id,
        'product_name' => 'Manzana',
        'category_name' => 'Frutas',
        'quantity' => 1,
    ]);
});

test('store accepts a quantity', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items", [
        'product_id' => $product->id,
        'quantity' => 3,
    ])
        ->assertCreated()
        ->assertJsonPath('data.quantity', 3);
});

test('store snapshots the product unit', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana', 'kg');

    $this->postJson("/api/lists/{$list->id}/items", ['product_id' => $product->id])
        ->assertCreated()
        ->assertJsonPath('data.unit', 'kg');

    $this->assertDatabaseHas('shopping_list_items', [
        'shopping_list_id' => $list->id,
        'product_id' => $product->id,
        'unit' => 'kg',
    ]);
});

test('store accepts a fractional quantity for a product sold by weight', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana', 'kg');

    $this->postJson("/api/lists/{$list->id}/items", [
        'product_id' => $product->id,
        'quantity' => 0.75,
        'unit_price' => 1200,
    ])
        ->assertCreated()
        ->assertJsonPath('data.unit', 'kg')
        ->assertJsonPath('data.quantity', 0.75)
        ->assertJsonPath('data.line_total', 900);
});

test('store rejects a quantity with more than three decimals', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana', 'kg');

    $this->postJson("/api/lists/{$list->id}/items", [
        'product_id' => $product->id,
        'quantity' => 0.1234,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');
});

test('store accepts a checked flag', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items", [
        'product_id' => $product->id,
        'checked' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.checked', true)
        ->assertJsonPath('data.checked_at', fn ($value) => $value !== null);

    $this->assertDatabaseHas('shopping_list_items', [
        'shopping_list_id' => $list->id,
        'product_id' => $product->id,
        'checked' => true,
    ]);
});

test('store is idempotent for an existing product', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items", ['product_id' => $product->id])
        ->assertCreated();

    $this->postJson("/api/lists/{$list->id}/items", [
        'product_id' => $product->id,
        'quantity' => 5,
    ])
        ->assertOk()
        ->assertJson([
            'message' => 'El producto ya estaba en la lista.',
            'data' => ['quantity' => 1],
        ]);

    expect(ShoppingListItem::where('shopping_list_id', $list->id)->count())->toBe(1);
});

test('store requires a quantity of at least one', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items", [
        'product_id' => $product->id,
        'quantity' => 0,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');
});

test('store rejects a product from another workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = Product::factory()->create();

    $this->postJson("/api/lists/{$list->id}/items", ['product_id' => $product->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_id');
});

test('store rejects a missing product', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);

    $this->postJson("/api/lists/{$list->id}/items", ['product_id' => 999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_id');
});

test('store rejects a completed list', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->completed()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items", ['product_id' => $product->id])
        ->assertForbidden();
});

test('store returns not found for a list from another workspace', function () {
    $user = actingAsWorkspaceUser()[0];
    $list = ShoppingList::factory()->create();
    $product = createProductInWorkspace($user->currentWorkspace(), 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items", ['product_id' => $product->id])
        ->assertNotFound();
});

test('bulk store adds several products in a single request', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $manzana = createProductInWorkspace($workspace, 'Manzana');
    $pera = createProductInWorkspace($workspace, 'Pera', 'kg');

    $this->postJson("/api/lists/{$list->id}/items/bulk", [
        'items' => [
            ['product_id' => $manzana->id, 'quantity' => 1],
            ['product_id' => $pera->id, 'quantity' => 0.5],
        ],
    ])
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Productos agregados correctamente.',
        ])
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.product_name', 'Manzana')
        ->assertJsonPath('data.0.unit', 'unit')
        ->assertJsonPath('data.1.product_name', 'Pera')
        ->assertJsonPath('data.1.unit', 'kg')
        ->assertJsonPath('data.1.quantity', 0.5);

    expect($list->items()->count())->toBe(2);
});

test('bulk store keeps existing items without duplicating them', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $manzana = createProductInWorkspace($workspace, 'Manzana');
    $pera = createProductInWorkspace($workspace, 'Pera');

    $this->postJson("/api/lists/{$list->id}/items", ['product_id' => $manzana->id])
        ->assertCreated();

    $this->postJson("/api/lists/{$list->id}/items/bulk", [
        'items' => [
            ['product_id' => $manzana->id],
            ['product_id' => $pera->id],
        ],
    ])
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect($list->items()->count())->toBe(2)
        ->and($list->items()->where('product_id', $manzana->id)->count())->toBe(1);
});

test('bulk store validates the items payload', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);

    $this->postJson("/api/lists/{$list->id}/items/bulk", ['items' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');
});

test('bulk store rejects duplicated products in the same payload', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items/bulk", [
        'items' => [
            ['product_id' => $product->id],
            ['product_id' => $product->id],
        ],
    ])->assertUnprocessable();

    expect($list->items()->count())->toBe(0);
});

test('bulk store rejects products from another workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = Product::factory()->create();

    $this->postJson("/api/lists/{$list->id}/items/bulk", [
        'items' => [['product_id' => $product->id]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items.0.product_id');
});

test('bulk store rejects a completed list', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->completed()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items/bulk", [
        'items' => [['product_id' => $product->id]],
    ])->assertForbidden();
});

test('update changes the quantity', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $item = ShoppingListItem::factory()->create(['shopping_list_id' => $list->id]);

    $this->patchJson("/api/lists/{$list->id}/items/{$item->id}", ['quantity' => 4])
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => ['quantity' => 4],
        ]);

    $this->assertDatabaseHas('shopping_list_items', [
        'id' => $item->id,
        'quantity' => 4,
    ]);
});

test('update accepts a fractional quantity and recalculates the line total', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $item = ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'unit' => 'kg',
        'quantity' => 1,
        'unit_price' => 800,
    ]);

    $this->patchJson("/api/lists/{$list->id}/items/{$item->id}", ['quantity' => 1.5])
        ->assertOk()
        ->assertJsonPath('data.unit', 'kg')
        ->assertJsonPath('data.quantity', 1.5)
        ->assertJsonPath('data.line_total', 1200);

    $this->assertDatabaseHas('shopping_list_items', [
        'id' => $item->id,
        'quantity' => 1.5,
    ]);
});

test('update checks and unchecks an item while keeping it in the list', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $item = ShoppingListItem::factory()->create(['shopping_list_id' => $list->id]);

    $this->patchJson("/api/lists/{$list->id}/items/{$item->id}", ['checked' => true])
        ->assertOk()
        ->assertJsonPath('data.checked', true)
        ->assertJsonPath('data.checked_at', fn ($value) => $value !== null);

    $this->assertDatabaseHas('shopping_list_items', [
        'id' => $item->id,
        'checked' => true,
    ]);

    $this->patchJson("/api/lists/{$list->id}/items/{$item->id}", ['checked' => false])
        ->assertOk()
        ->assertJsonPath('data.checked', false)
        ->assertJsonPath('data.checked_at', null);

    $this->assertDatabaseHas('shopping_list_items', [
        'id' => $item->id,
        'checked' => false,
        'checked_at' => null,
    ]);
});

test('update rejects a completed list', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->completed()->create(['workspace_id' => $workspace->id]);
    $item = ShoppingListItem::factory()->create(['shopping_list_id' => $list->id]);

    $this->patchJson("/api/lists/{$list->id}/items/{$item->id}", ['quantity' => 2])
        ->assertForbidden();
});

test('update returns not found for an item from another list', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $otherList = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $item = ShoppingListItem::factory()->create(['shopping_list_id' => $otherList->id]);

    $this->patchJson("/api/lists/{$list->id}/items/{$item->id}", ['quantity' => 2])
        ->assertNotFound();
});

test('destroy removes the item without deleting the product', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');
    $item = ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'product_id' => $product->id,
    ]);

    $this->deleteJson("/api/lists/{$list->id}/items/{$item->id}")
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Producto eliminado de la lista.',
            'data' => null,
        ]);

    $this->assertDatabaseMissing('shopping_list_items', ['id' => $item->id]);
    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

test('destroy returns not found for an item from another workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $otherList = ShoppingList::factory()->create();
    $item = ShoppingListItem::factory()->create(['shopping_list_id' => $otherList->id]);

    $this->deleteJson("/api/lists/{$list->id}/items/{$item->id}")
        ->assertNotFound();
});

test('store accepts a unit price', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items", [
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 1500.5,
    ])
        ->assertCreated()
        ->assertJsonPath('data.unit_price', 1500.5)
        ->assertJsonPath('data.line_total', 3001);

    $this->assertDatabaseHas('shopping_list_items', [
        'shopping_list_id' => $list->id,
        'product_id' => $product->id,
        'unit_price' => 1500.5,
    ]);
});

test('store rejects a negative unit price', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items", [
        'product_id' => $product->id,
        'unit_price' => -1,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('unit_price');
});

test('store rejects a unit price with more than two decimals', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $product = createProductInWorkspace($workspace, 'Manzana');

    $this->postJson("/api/lists/{$list->id}/items", [
        'product_id' => $product->id,
        'unit_price' => 10.999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('unit_price');
});

test('update changes the unit price and recalculates the line total', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $item = ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'quantity' => 3,
    ]);

    $this->patchJson("/api/lists/{$list->id}/items/{$item->id}", ['unit_price' => 100.25])
        ->assertOk()
        ->assertJsonPath('data.unit_price', 100.25)
        ->assertJsonPath('data.line_total', 300.75);

    $this->assertDatabaseHas('shopping_list_items', [
        'id' => $item->id,
        'unit_price' => 100.25,
    ]);
});

test('update clears the unit price', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $item = ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'unit_price' => 500,
    ]);

    $this->patchJson("/api/lists/{$list->id}/items/{$item->id}", ['unit_price' => null])
        ->assertOk()
        ->assertJsonPath('data.unit_price', null)
        ->assertJsonPath('data.line_total', null);

    $this->assertDatabaseHas('shopping_list_items', [
        'id' => $item->id,
        'unit_price' => null,
    ]);
});

test('bulk store accepts unit prices', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $manzana = createProductInWorkspace($workspace, 'Manzana');
    $pera = createProductInWorkspace($workspace, 'Pera');

    $this->postJson("/api/lists/{$list->id}/items/bulk", [
        'items' => [
            ['product_id' => $manzana->id, 'quantity' => 2, 'unit_price' => 10.5],
            ['product_id' => $pera->id],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.0.unit_price', 10.5)
        ->assertJsonPath('data.0.line_total', 21)
        ->assertJsonPath('data.1.unit_price', null)
        ->assertJsonPath('data.1.line_total', null);
});
