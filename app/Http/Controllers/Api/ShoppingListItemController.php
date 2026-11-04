<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkStoreShoppingListItemsRequest;
use App\Http\Requests\StoreShoppingListItemRequest;
use App\Http\Requests\UpdateShoppingListItemRequest;
use App\Http\Resources\ShoppingListItemResource;
use App\Models\ShoppingList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ShoppingListItemController extends Controller
{
    public function store(StoreShoppingListItemRequest $request, int $list): JsonResponse
    {
        $shoppingList = $this->findList($request, $list);

        Gate::authorize('manageItems', $shoppingList);

        $workspace = $request->user()->currentWorkspace();
        $product = $workspace->products()
            ->with('category')
            ->findOrFail($request->integer('product_id'));

        $item = $shoppingList->items()->firstOrCreate(
            ['product_id' => $product->id],
            [
                'product_name' => $product->name,
                'category_name' => $product->category?->name,
                'quantity' => $request->integer('quantity', 1),
                'checked' => $request->boolean('checked'),
                'checked_at' => $request->boolean('checked') ? now() : null,
            ],
        );

        $created = $item->wasRecentlyCreated;

        return response()->json([
            'status' => true,
            'message' => $created
                ? 'Producto agregado a la lista.'
                : 'El producto ya estaba en la lista.',
            'data' => new ShoppingListItemResource($item),
        ], $created ? 201 : 200);
    }

    public function bulkStore(BulkStoreShoppingListItemsRequest $request, int $list): JsonResponse
    {
        $shoppingList = $this->findList($request, $list);

        Gate::authorize('manageItems', $shoppingList);

        $workspace = $request->user()->currentWorkspace();
        $requestedItems = $request->validated('items');

        $items = DB::transaction(function () use ($workspace, $shoppingList, $requestedItems) {
            $productIds = collect($requestedItems)->pluck('product_id')->all();

            $products = $workspace->products()
                ->with('category')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $existingItems = $shoppingList->items()
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');

            return collect($requestedItems)->map(function (array $requestedItem) use ($products, $existingItems, $shoppingList) {
                $productId = $requestedItem['product_id'];

                if ($existingItems->has($productId)) {
                    return $existingItems->get($productId);
                }

                $product = $products->get($productId);

                return $shoppingList->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'category_name' => $product->category?->name,
                    'quantity' => $requestedItem['quantity'] ?? 1,
                ]);
            });
        });

        return response()->json([
            'status' => true,
            'message' => 'Productos agregados correctamente.',
            'data' => ShoppingListItemResource::collection($items),
        ]);
    }

    public function update(UpdateShoppingListItemRequest $request, int $list, int $item): JsonResponse
    {
        $shoppingList = $this->findList($request, $list);

        Gate::authorize('manageItems', $shoppingList);

        $item = $shoppingList->items()->findOrFail($item);

        $validated = $request->validated();

        if (array_key_exists('quantity', $validated)) {
            $item->quantity = $validated['quantity'];
        }

        if (array_key_exists('checked', $validated)) {
            $item->checked = (bool) $validated['checked'];
            $item->checked_at = $item->checked ? now() : null;
        }

        $item->save();

        return response()->json([
            'status' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => new ShoppingListItemResource($item),
        ]);
    }

    public function destroy(Request $request, int $list, int $item): JsonResponse
    {
        $shoppingList = $this->findList($request, $list);

        Gate::authorize('manageItems', $shoppingList);

        $shoppingList->items()->findOrFail($item)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Producto eliminado de la lista.',
            'data' => null,
        ]);
    }

    private function findList(Request $request, int $listId): ShoppingList
    {
        return $request->user()->currentWorkspace()
            ->shoppingLists()
            ->findOrFail($listId);
    }
}
