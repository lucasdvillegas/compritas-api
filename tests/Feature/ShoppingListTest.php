<?php

use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('index requires authentication', function () {
    $this->getJson('/api/lists')->assertUnauthorized();
});

test('store requires authentication', function () {
    $this->postJson('/api/lists', ['name' => 'Compra semanal'])->assertUnauthorized();
});

test('index returns only the current workspace lists with item counts', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra semanal',
    ]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'checked' => true,
        'checked_at' => now(),
    ]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'checked' => false,
    ]);

    ShoppingList::factory()->create(['name' => 'Lista de otro workspace']);

    $this->getJson('/api/lists')
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Listas obtenidas correctamente.',
        ])
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Compra semanal')
        ->assertJsonPath('data.0.status', 'open')
        ->assertJsonPath('data.0.items_count', 2)
        ->assertJsonPath('data.0.checked_items_count', 1);
});

test('index orders lists from newest to oldest', function () {
    [, $workspace] = actingAsWorkspaceUser();

    ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Lista vieja',
        'created_at' => now()->subDay(),
    ]);

    ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Lista nueva',
        'created_at' => now(),
    ]);

    $this->getJson('/api/lists')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Lista nueva')
        ->assertJsonPath('data.1.name', 'Lista vieja');
});

test('index filters lists by status', function () {
    [, $workspace] = actingAsWorkspaceUser();

    ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Lista abierta',
    ]);

    ShoppingList::factory()->completed()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Lista completada',
    ]);

    $this->getJson('/api/lists?status=completed')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Lista completada')
        ->assertJsonPath('data.0.status', 'completed');
});

test('index rejects an invalid status filter', function () {
    actingAsWorkspaceUser();

    $this->getJson('/api/lists?status=invalid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

test('store creates an open list in the current workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $this->postJson('/api/lists', ['name' => 'Compra semanal'])
        ->assertCreated()
        ->assertJson([
            'status' => true,
            'message' => 'Lista creada correctamente.',
            'data' => [
                'name' => 'Compra semanal',
                'status' => 'open',
                'items_count' => 0,
                'checked_items_count' => 0,
                'completed_at' => null,
            ],
        ]);

    $this->assertDatabaseHas('shopping_lists', [
        'name' => 'Compra semanal',
        'workspace_id' => $workspace->id,
        'status' => 'open',
    ]);
});

test('store requires a name', function () {
    actingAsWorkspaceUser();

    $this->postJson('/api/lists', ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('show returns the list with its items and snapshots', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'product_name' => 'Manzana',
        'category_name' => 'Frutas',
        'quantity' => 2,
        'checked' => false,
    ]);

    $this->getJson("/api/lists/{$list->id}")
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Lista obtenida correctamente.',
        ])
        ->assertJsonPath('data.id', $list->id)
        ->assertJsonPath('data.items.0.product_name', 'Manzana')
        ->assertJsonPath('data.items.0.category_name', 'Frutas')
        ->assertJsonPath('data.items.0.quantity', 2)
        ->assertJsonPath('data.items.0.checked', false)
        ->assertJsonPath('data.items.0.checked_at', null);
});

test('show returns not found for a list from another workspace', function () {
    actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create();

    $this->getJson("/api/lists/{$list->id}")->assertNotFound();
});

test('update renames the list', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra semanal',
    ]);

    $this->patchJson("/api/lists/{$list->id}", ['name' => 'Compra del sábado'])
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Lista actualizada correctamente.',
            'data' => ['name' => 'Compra del sábado'],
        ]);

    $this->assertDatabaseHas('shopping_lists', [
        'id' => $list->id,
        'name' => 'Compra del sábado',
    ]);
});

test('update requires a name', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);

    $this->patchJson("/api/lists/{$list->id}", ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('update sets a manual total', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);

    $this->patchJson("/api/lists/{$list->id}", ['manual_total' => 1234.5])
        ->assertOk()
        ->assertJsonPath('data.manual_total', 1234.5);

    $this->assertDatabaseHas('shopping_lists', [
        'id' => $list->id,
        'manual_total' => 1234.5,
    ]);
});

test('update clears a manual total', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'manual_total' => 1234.5,
    ]);

    $this->patchJson("/api/lists/{$list->id}", ['manual_total' => null])
        ->assertOk()
        ->assertJsonPath('data.manual_total', null);

    $this->assertDatabaseHas('shopping_lists', [
        'id' => $list->id,
        'manual_total' => null,
    ]);
});

test('update rejects a negative manual total', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);

    $this->patchJson("/api/lists/{$list->id}", ['manual_total' => -1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('manual_total');
});

test('update rejects a completed list', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->completed()->create(['workspace_id' => $workspace->id]);

    $this->patchJson("/api/lists/{$list->id}", ['name' => 'Otro nombre'])
        ->assertForbidden();
});

test('update returns not found for a list from another workspace', function () {
    actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create();

    $this->patchJson("/api/lists/{$list->id}", ['name' => 'Otro nombre'])
        ->assertNotFound();
});

test('destroy deletes the list and its items', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);
    $item = ShoppingListItem::factory()->create(['shopping_list_id' => $list->id]);

    $this->deleteJson("/api/lists/{$list->id}")
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Lista eliminada correctamente.',
            'data' => null,
        ]);

    $this->assertDatabaseMissing('shopping_lists', ['id' => $list->id]);
    $this->assertDatabaseMissing('shopping_list_items', ['id' => $item->id]);
});

test('destroy allows deleting a completed list', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->completed()->create(['workspace_id' => $workspace->id]);

    $this->deleteJson("/api/lists/{$list->id}")->assertOk();
});

test('complete marks the list as completed', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);

    $this->patchJson("/api/lists/{$list->id}/complete")
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Lista completada correctamente.',
            'data' => [
                'id' => $list->id,
                'status' => 'completed',
            ],
        ])
        ->assertJsonPath('data.completed_at', fn ($value) => $value !== null);

    $this->assertDatabaseHas('shopping_lists', [
        'id' => $list->id,
        'status' => 'completed',
    ]);
});

test('complete rejects an already completed list', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->completed()->create(['workspace_id' => $workspace->id]);

    $this->patchJson("/api/lists/{$list->id}/complete")->assertForbidden();
});

test('complete returns not found for a list from another workspace', function () {
    actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create();

    $this->patchJson("/api/lists/{$list->id}/complete")->assertNotFound();
});

test('show calculates the total amount from the item prices', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'quantity' => 2,
        'unit_price' => 1500.5,
    ]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'quantity' => 3,
        'unit_price' => 10.1,
    ]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'quantity' => 5,
        'unit_price' => null,
    ]);

    $this->getJson("/api/lists/{$list->id}")
        ->assertOk()
        ->assertJsonPath('data.total_amount', 3031.3)
        ->assertJsonPath('data.items.0.line_total', 3001)
        ->assertJsonPath('data.items.1.line_total', 30.3)
        ->assertJsonPath('data.items.2.line_total', null);
});

test('index returns the stored total amount for completed lists', function () {
    [, $workspace] = actingAsWorkspaceUser();

    ShoppingList::factory()->completed()->create([
        'workspace_id' => $workspace->id,
        'total_amount' => 4321.5,
    ]);

    $this->getJson('/api/lists?status=completed')
        ->assertOk()
        ->assertJsonPath('data.0.total_amount', 4321.5);
});

test('complete stores the total amount snapshot', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create(['workspace_id' => $workspace->id]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'quantity' => 3,
        'unit_price' => 100.25,
    ]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'quantity' => 1,
        'unit_price' => null,
    ]);

    $this->patchJson("/api/lists/{$list->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.total_amount', 300.75);

    $this->assertDatabaseHas('shopping_lists', [
        'id' => $list->id,
        'total_amount' => 300.75,
    ]);
});

test('show returns the manual total overriding the computed total', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'manual_total' => 5000,
    ]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'quantity' => 2,
        'unit_price' => 10,
    ]);

    $this->getJson("/api/lists/{$list->id}")
        ->assertOk()
        ->assertJsonPath('data.manual_total', 5000)
        ->assertJsonPath('data.total_amount', 5000);
});

test('complete stores the manual total as the snapshot', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'manual_total' => 7500,
    ]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'quantity' => 2,
        'unit_price' => 100,
    ]);

    $this->patchJson("/api/lists/{$list->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.total_amount', 7500);

    $this->assertDatabaseHas('shopping_lists', [
        'id' => $list->id,
        'manual_total' => 7500,
        'total_amount' => 7500,
    ]);
});
