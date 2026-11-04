<?php

namespace App\Http\Controllers\Api;

use App\Enums\ShoppingListStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShoppingListRequest;
use App\Http\Requests\UpdateShoppingListRequest;
use App\Http\Resources\ShoppingListResource;
use App\Models\ShoppingList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ShoppingListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::enum(ShoppingListStatus::class)],
        ]);

        $workspace = $request->user()->currentWorkspace();

        $lists = $workspace->shoppingLists()
            ->withCount(['items', 'checkedItems'])
            ->when(
                isset($validated['status']),
                fn ($query) => $query->where('status', $validated['status']),
            )
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Listas obtenidas correctamente.',
            'data' => ShoppingListResource::collection($lists),
        ]);
    }

    public function store(StoreShoppingListRequest $request): JsonResponse
    {
        $list = $request->user()->currentWorkspace()
            ->shoppingLists()
            ->create($request->validated());

        $list->loadCount(['items', 'checkedItems']);

        return response()->json([
            'status' => true,
            'message' => 'Lista creada correctamente.',
            'data' => new ShoppingListResource($list),
        ], 201);
    }

    public function show(Request $request, int $list): JsonResponse
    {
        $list = $this->findList($request, $list)
            ->load('items')
            ->loadCount(['items', 'checkedItems']);

        return response()->json([
            'status' => true,
            'message' => 'Lista obtenida correctamente.',
            'data' => new ShoppingListResource($list),
        ]);
    }

    public function update(UpdateShoppingListRequest $request, int $list): JsonResponse
    {
        $list = $this->findList($request, $list);

        Gate::authorize('update', $list);

        $list->update($request->validated());
        $list->loadCount(['items', 'checkedItems']);

        return response()->json([
            'status' => true,
            'message' => 'Lista actualizada correctamente.',
            'data' => new ShoppingListResource($list),
        ]);
    }

    public function destroy(Request $request, int $list): JsonResponse
    {
        $list = $this->findList($request, $list);

        Gate::authorize('delete', $list);

        $list->delete();

        return response()->json([
            'status' => true,
            'message' => 'Lista eliminada correctamente.',
            'data' => null,
        ]);
    }

    public function complete(Request $request, int $list): JsonResponse
    {
        $list = $this->findList($request, $list);

        Gate::authorize('complete', $list);

        $list->load('items');

        $list->update([
            'status' => ShoppingListStatus::Completed,
            'completed_at' => now(),
            'total_amount' => $list->effectiveTotal(),
        ]);

        $list->loadCount(['items', 'checkedItems']);

        return response()->json([
            'status' => true,
            'message' => 'Lista completada correctamente.',
            'data' => new ShoppingListResource($list),
        ]);
    }

    private function findList(Request $request, int $listId): ShoppingList
    {
        return $request->user()->currentWorkspace()
            ->shoppingLists()
            ->findOrFail($listId);
    }
}
