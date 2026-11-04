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
