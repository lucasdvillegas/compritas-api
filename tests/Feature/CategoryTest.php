<?php

use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('index returns only the current workspace categories ordered by name', function () {
    [, $workspace] = actingAsWorkspaceUser();

    Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Lácteos']);
    Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bebidas']);
    Category::factory()->create(['name' => 'Otra categoría']);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Categorías obtenidas correctamente.',
        ])
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Bebidas')
        ->assertJsonPath('data.1.name', 'Lácteos');
});

test('index paginates categories with ten items per page', function () {
    [, $workspace] = actingAsWorkspaceUser();

    foreach (range(1, 12) as $index) {
        Category::factory()->create([
            'workspace_id' => $workspace->id,
            'name' => sprintf('Categoría %02d', $index),
        ]);
    }

    $this->getJson('/api/categories?per_page=10')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 12)
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.has_more', true);

    $this->getJson('/api/categories?per_page=10&page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.has_more', false);
});

test('index searches categories by name', function () {
    [, $workspace] = actingAsWorkspaceUser();

    Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bebidas']);
    Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Lácteos']);

    $this->getJson('/api/categories?search=beb')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Bebidas');
});

test('index rejects an invalid per page value', function () {
    actingAsWorkspaceUser();

    $this->getJson('/api/categories?per_page=51')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

test('store creates a category in the current workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $this->postJson('/api/categories', ['name' => 'Bebidas'])
        ->assertCreated()
        ->assertJson([
            'status' => true,
            'message' => 'Categoría creada correctamente.',
            'data' => [
                'name' => 'Bebidas',
                'workspace_id' => $workspace->id,
            ],
        ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Bebidas',
        'workspace_id' => $workspace->id,
    ]);
});

test('store requires a name', function () {
    actingAsWorkspaceUser();

    $this->postJson('/api/categories', ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('store requires a unique name within the workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bebidas']);

    $this->postJson('/api/categories', ['name' => 'Bebidas'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    expect(Category::where('workspace_id', $workspace->id)->where('name', 'Bebidas')->count())->toBe(1);
});

test('store allows the same category name in another workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    Category::factory()->create([
        'workspace_id' => Workspace::factory()->create()->id,
        'name' => 'Bebidas',
    ]);

    $this->postJson('/api/categories', ['name' => 'Bebidas'])
        ->assertCreated();

    expect(Category::where('workspace_id', $workspace->id)->where('name', 'Bebidas')->count())->toBe(1);
});

test('show returns a category', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $category = Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bebidas']);

    $this->getJson("/api/categories/{$category->id}")
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Categoría obtenida correctamente.',
            'data' => ['id' => $category->id, 'name' => 'Bebidas'],
        ]);
});

test('show returns not found for a missing category', function () {
    actingAsWorkspaceUser();

    $this->getJson('/api/categories/999')->assertNotFound();
});

test('show returns not found for a category from another workspace', function () {
    actingAsWorkspaceUser();

    $category = Category::factory()->create();

    $this->getJson("/api/categories/{$category->id}")->assertNotFound();
});

test('update changes the category name', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $category = Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bebidas']);

    $this->putJson("/api/categories/{$category->id}", ['name' => 'Bebidas sin alcohol'])
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Categoría actualizada correctamente.',
            'data' => ['name' => 'Bebidas sin alcohol'],
        ]);

    $this->assertDatabaseHas('categories', ['name' => 'Bebidas sin alcohol']);
});

test('update allows keeping the same name', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $category = Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bebidas']);

    $this->putJson("/api/categories/{$category->id}", ['name' => 'Bebidas'])->assertOk();
});

test('update requires a unique name within the workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Bebidas']);
    $category = Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Lácteos']);

    $this->putJson("/api/categories/{$category->id}", ['name' => 'Bebidas'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('update returns not found for a category from another workspace', function () {
    actingAsWorkspaceUser();

    $category = Category::factory()->create(['name' => 'Bebidas']);

    $this->putJson("/api/categories/{$category->id}", ['name' => 'Lácteos'])
        ->assertNotFound();
});

test('destroy deletes a category', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $category = Category::factory()->create(['workspace_id' => $workspace->id]);

    $this->deleteJson("/api/categories/{$category->id}")
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Categoría eliminada correctamente.',
            'data' => null,
        ]);

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('destroy returns not found for a missing category', function () {
    actingAsWorkspaceUser();

    $this->deleteJson('/api/categories/999')->assertNotFound();
});

test('destroy returns not found for a category from another workspace', function () {
    actingAsWorkspaceUser();

    $category = Category::factory()->create();

    $this->deleteJson("/api/categories/{$category->id}")->assertNotFound();
});
