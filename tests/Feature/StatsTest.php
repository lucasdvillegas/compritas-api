<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function completedListWithTotal(Workspace $workspace, float $total, string $completedAt, ?float $manualTotal = null): ShoppingList
{
    return ShoppingList::factory()->completed()->create([
        'workspace_id' => $workspace->id,
        'total_amount' => $total,
        'manual_total' => $manualTotal,
        'completed_at' => $completedAt,
    ]);
}

test('stats endpoints require authentication', function () {
    $this->getJson('/api/stats/summary')->assertUnauthorized();
    $this->getJson('/api/stats/monthly')->assertUnauthorized();
    $this->getJson('/api/stats/categories')->assertUnauthorized();
    $this->getJson('/api/stats/products')->assertUnauthorized();
});

test('summary only counts completed lists of the current workspace', function () {
    [, $workspace] = actingAsWorkspaceUser();

    completedListWithTotal($workspace, 1250.50, '2026-03-10 10:00:00');

    ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'total_amount' => 999,
        'completed_at' => '2026-03-11 10:00:00',
    ]);

    $otherWorkspace = Workspace::factory()->create();
    completedListWithTotal($otherWorkspace, 9999, '2026-03-12 10:00:00');

    $this->getJson('/api/stats/summary?from=2026-01-01&to=2026-12-31')
        ->assertOk()
        ->assertJsonPath('data.lists_count', 1)
        ->assertJsonPath('data.total_spent', 1250.5)
        ->assertJsonPath('data.average_per_list', 1250.5);
});

test('summary separates known breakdown from manual totals', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $detailed = completedListWithTotal($workspace, 200, '2026-03-01 10:00:00');

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $detailed->id,
        'product_name' => 'Manzana',
        'category_name' => 'Frutas',
        'unit_price' => 100,
        'quantity' => 2,
    ]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $detailed->id,
        'product_name' => 'Pan',
        'category_name' => 'Panadería',
        'unit_price' => null,
        'quantity' => 1,
    ]);

    $global = completedListWithTotal($workspace, 500, '2026-03-02 10:00:00', 500);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $global->id,
        'product_name' => 'Verduras surtidas',
        'category_name' => 'Verduras',
        'unit_price' => null,
        'quantity' => 1,
    ]);

    $this->getJson('/api/stats/summary?from=2026-03-01&to=2026-03-31')
        ->assertOk()
        ->assertJsonPath('data.lists_count', 2)
        ->assertJsonPath('data.total_spent', 700)
        ->assertJsonPath('data.breakdown_total', 200)
        ->assertJsonPath('data.lists_with_manual_total', 1)
        ->assertJsonPath('data.priced_items_count', 1)
        ->assertJsonPath('data.unpriced_items_count', 2)
        ->assertJsonPath('data.breakdown_coverage', 0.3333)
        ->assertJsonPath('data.average_per_list', 350);
});

test('summary respects the date range', function () {
    [, $workspace] = actingAsWorkspaceUser();

    completedListWithTotal($workspace, 100, '2026-01-15 10:00:00');
    completedListWithTotal($workspace, 200, '2026-02-15 10:00:00');

    $this->getJson('/api/stats/summary?from=2026-02-01&to=2026-02-28')
        ->assertOk()
        ->assertJsonPath('data.lists_count', 1)
        ->assertJsonPath('data.total_spent', 200)
        ->assertJsonPath('data.range.from', '2026-02-01')
        ->assertJsonPath('data.range.to', '2026-02-28');
});

test('categories only include priced items and use snapshot names', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = completedListWithTotal($workspace, 275, '2026-03-01 10:00:00');

    foreach ([
        ['product_name' => 'Manzana', 'category_name' => 'Frutas', 'unit_price' => 100, 'quantity' => 2],
        ['product_name' => 'Pera', 'category_name' => 'Frutas', 'unit_price' => 50, 'quantity' => 1],
        ['product_name' => 'Lechuga', 'category_name' => 'Verduras', 'unit_price' => null, 'quantity' => 1],
        ['product_name' => 'Sal', 'category_name' => null, 'unit_price' => 25, 'quantity' => 1],
    ] as $item) {
        ShoppingListItem::factory()->create([
            'shopping_list_id' => $list->id,
            ...$item,
        ]);
    }

    $this->getJson('/api/stats/categories?from=2026-03-01&to=2026-03-31')
        ->assertOk()
        ->assertJsonPath('data.categories.0.category_name', 'Frutas')
        ->assertJsonPath('data.categories.0.amount', 250)
        ->assertJsonPath('data.categories.0.items_count', 2)
        ->assertJsonPath('data.categories.1.category_name', 'Sin categoría')
        ->assertJsonPath('data.categories.1.amount', 25)
        ->assertJsonCount(2, 'data.categories')
        ->assertJsonPath('data.breakdown_total', 275)
        ->assertJsonPath('data.priced_items_count', 3)
        ->assertJsonPath('data.unpriced_items_count', 1)
        ->assertJsonPath('data.breakdown_coverage', 0.75);
});

test('categories keep the historical snapshot after the catalog changes', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $category = Category::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Frutas']);
    $product = Product::factory()->create([
        'workspace_id' => $workspace->id,
        'category_id' => $category->id,
        'name' => 'Manzana',
    ]);

    $list = completedListWithTotal($workspace, 100, '2026-03-01 10:00:00');

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'product_id' => $product->id,
        'product_name' => 'Manzana',
        'category_name' => 'Frutas',
        'unit_price' => 100,
        'quantity' => 1,
    ]);

    $product->update(['name' => 'Manzana Roja', 'category_id' => null]);
    $category->delete();

    $this->getJson('/api/stats/categories?from=2026-03-01&to=2026-03-31')
        ->assertOk()
        ->assertJsonPath('data.categories.0.category_name', 'Frutas')
        ->assertJsonPath('data.categories.0.amount', 100);

    $this->getJson('/api/stats/products?from=2026-03-01&to=2026-03-31')
        ->assertOk()
        ->assertJsonPath('data.products.0.product_name', 'Manzana');
});

test('monthly returns all twelve months with zeros', function () {
    [, $workspace] = actingAsWorkspaceUser();

    completedListWithTotal($workspace, 300, '2026-03-15 10:00:00');
    completedListWithTotal($workspace, 100, '2026-03-20 10:00:00');
    completedListWithTotal($workspace, 50, '2026-05-01 10:00:00');

    $this->getJson('/api/stats/monthly?year=2026')
        ->assertOk()
        ->assertJsonPath('data.year', 2026)
        ->assertJsonCount(12, 'data.months')
        ->assertJsonPath('data.months.0.month', '2026-01')
        ->assertJsonPath('data.months.0.total_spent', 0)
        ->assertJsonPath('data.months.2.month', '2026-03')
        ->assertJsonPath('data.months.2.total_spent', 400)
        ->assertJsonPath('data.months.2.lists_count', 2)
        ->assertJsonPath('data.months.4.month', '2026-05')
        ->assertJsonPath('data.months.4.total_spent', 50);
});

test('top products rank by times purchased and separate quantities by unit', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $manzana = Product::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Manzana',
    ]);

    $leche = Product::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Leche',
    ]);

    $first = completedListWithTotal($workspace, 100, '2026-03-01 10:00:00');
    $second = completedListWithTotal($workspace, 100, '2026-03-10 10:00:00');
    $third = completedListWithTotal($workspace, 100, '2026-03-20 10:00:00');
    $fourth = completedListWithTotal($workspace, 100, '2026-03-05 10:00:00');
    $fifth = completedListWithTotal($workspace, 100, '2026-03-15 10:00:00');

    foreach ([
        [$first, $manzana->id, 'Manzana', 'kg', 1.5],
        [$second, $manzana->id, 'Manzana', 'kg', 2],
        [$third, $manzana->id, 'Manzana', 'kg', 0.5],
        [$first, $leche->id, 'Leche', 'unit', 2],
        [$fourth, null, 'Pan', 'unit', 1],
        [$fifth, null, 'Pan', 'unit', 1],
    ] as [$list, $productId, $name, $unit, $quantity]) {
        ShoppingListItem::factory()->create([
            'shopping_list_id' => $list->id,
            'product_id' => $productId,
            'product_name' => $name,
            'unit' => $unit,
            'quantity' => $quantity,
        ]);
    }

    $this->getJson('/api/stats/products?from=2026-03-01&to=2026-03-31&limit=10')
        ->assertOk()
        ->assertJsonCount(3, 'data.products')
        ->assertJsonPath('data.products.0.product_name', 'Manzana')
        ->assertJsonPath('data.products.0.times_purchased', 3)
        ->assertJsonPath('data.products.0.quantities.kg', 4)
        ->assertJsonPath('data.products.0.average_days_between', 9.5)
        ->assertJsonPath('data.products.1.product_name', 'Pan')
        ->assertJsonPath('data.products.1.times_purchased', 2)
        ->assertJsonPath('data.products.1.quantities.unit', 2)
        ->assertJsonPath('data.products.2.product_name', 'Leche')
        ->assertJsonPath('data.products.2.times_purchased', 1)
        ->assertJsonPath('data.products.2.quantities.unit', 2);
});

test('top products respect the requested limit', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = completedListWithTotal($workspace, 300, '2026-03-01 10:00:00');

    foreach (['A', 'B', 'C'] as $name) {
        ShoppingListItem::factory()->create([
            'shopping_list_id' => $list->id,
            'product_name' => $name,
        ]);
    }

    $this->getJson('/api/stats/products?from=2026-03-01&to=2026-03-31&limit=1')
        ->assertOk()
        ->assertJsonCount(1, 'data.products')
        ->assertJsonPath('data.limit', 1);
});

test('stats reject invalid filters', function () {
    actingAsWorkspaceUser();

    $this->getJson('/api/stats/summary?from=2026-02-01&to=2026-01-01')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to');

    $this->getJson('/api/stats/summary?from=2026-13-01')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('from');

    $this->getJson('/api/stats/monthly?year=1999')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('year');

    $this->getJson('/api/stats/products?limit=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('limit');
});

test('stats return zeros when there is no data', function () {
    actingAsWorkspaceUser();

    $this->getJson('/api/stats/summary')
        ->assertOk()
        ->assertJsonPath('data.lists_count', 0)
        ->assertJsonPath('data.total_spent', 0)
        ->assertJsonPath('data.breakdown_total', 0)
        ->assertJsonPath('data.breakdown_coverage', 0)
        ->assertJsonPath('data.average_per_list', 0);

    $this->getJson('/api/stats/categories')
        ->assertOk()
        ->assertJsonCount(0, 'data.categories')
        ->assertJsonPath('data.breakdown_total', 0);

    $this->getJson('/api/stats/products')
        ->assertOk()
        ->assertJsonCount(0, 'data.products');

    $this->getJson('/api/stats/monthly?year=2026')
        ->assertOk()
        ->assertJsonCount(12, 'data.months')
        ->assertJsonPath('data.months.0.total_spent', 0);
});
