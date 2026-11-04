<?php

namespace App\Http\Controllers\Api;

use App\Enums\ShoppingListRole;
use App\Enums\ShoppingListStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShoppingListRequest;
use App\Http\Requests\UpdateShoppingListRequest;
use App\Http\Resources\ShoppingListResource;
use App\Models\ShoppingList;
use App\Notifications\ListCompleted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ShoppingListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::enum(ShoppingListStatus::class)],
        ]);

        $lists = $request->user()->shoppingLists()
            ->withCount(['items', 'checkedItems', 'members'])
            ->when(
                isset($validated['status']),
                fn ($query) => $query->where('shopping_lists.status', $validated['status']),
            )
            ->orderByDesc('shopping_lists.created_at')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Listas obtenidas correctamente.',
            'data' => ShoppingListResource::collection($lists),
        ]);
    }

    public function store(StoreShoppingListRequest $request): JsonResponse
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace();
        $validated = $request->validated();

        $list = DB::transaction(function () use ($workspace, $user, $validated): ShoppingList {
            $list = $workspace->shoppingLists()->create($validated);

            $list->members()->attach($user->id, [
                'role' => ShoppingListRole::Owner->value,
            ]);

            return $list;
        });

        $list = $user->shoppingLists()
            ->withCount(['items', 'checkedItems', 'members'])
            ->findOrFail($list->id);

        return response()->json([
            'status' => true,
            'message' => 'Lista creada correctamente.',
            'data' => new ShoppingListResource($list),
        ], 201);
    }

    public function show(Request $request, int $list): JsonResponse
    {
        $list = $request->user()->shoppingLists()
            ->with('items')
            ->withCount(['items', 'checkedItems', 'members'])
            ->findOrFail($list);

        return response()->json([
            'status' => true,
            'message' => 'Lista obtenida correctamente.',
            'data' => new ShoppingListResource($list),
        ]);
    }

    public function update(UpdateShoppingListRequest $request, int $list): JsonResponse
    {
        $list = $request->user()->shoppingLists()->findOrFail($list);

        Gate::authorize('update', $list);

        $list->update($request->validated());
        $list->loadCount(['items', 'checkedItems', 'members']);

        return response()->json([
            'status' => true,
            'message' => 'Lista actualizada correctamente.',
            'data' => new ShoppingListResource($list),
        ]);
    }

    public function destroy(Request $request, int $list): JsonResponse
    {
        $list = $request->user()->shoppingLists()->findOrFail($list);

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
        $list = $request->user()->shoppingLists()->findOrFail($list);

        Gate::authorize('complete', $list);

        $list->load('items');

        $list->update([
            'status' => ShoppingListStatus::Completed,
            'completed_at' => now(),
            'total_amount' => $list->effectiveTotal(),
        ]);

        $recipients = $list->members()
            ->where('users.id', '!=', $request->user()->id)
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ListCompleted($list, $request->user()));
        }

        $list->loadCount(['items', 'checkedItems', 'members']);

        return response()->json([
            'status' => true,
            'message' => 'Lista completada correctamente.',
            'data' => new ShoppingListResource($list),
        ]);
    }

    public function members(Request $request, int $list): JsonResponse
    {
        $list = $request->user()->shoppingLists()->findOrFail($list);

        Gate::authorize('view', $list);

        $members = $list->members()
            ->orderBy('name')
            ->get()
            ->map(fn ($member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role,
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Miembros obtenidos correctamente.',
            'data' => $members,
        ]);
    }

    public function removeMember(Request $request, int $list, int $user): JsonResponse
    {
        $list = $request->user()->shoppingLists()->findOrFail($list);

        Gate::authorize('manageMembers', $list);

        $member = $list->members()->where('users.id', $user)->firstOrFail();

        if ($member->pivot->role === ShoppingListRole::Owner->value) {
            return response()->json([
                'status' => false,
                'message' => 'No se puede quitar al dueño de la lista.',
                'data' => null,
            ], 422);
        }

        $list->members()->detach($member->id);

        return response()->json([
            'status' => true,
            'message' => 'Miembro quitado correctamente.',
            'data' => null,
        ]);
    }

    public function leave(Request $request, int $list): JsonResponse
    {
        $list = $request->user()->shoppingLists()->findOrFail($list);

        $member = $list->members()
            ->where('users.id', $request->user()->id)
            ->firstOrFail();

        if ($member->pivot->role === ShoppingListRole::Owner->value) {
            return response()->json([
                'status' => false,
                'message' => 'El dueño no puede salir de la lista. Podés eliminarla.',
                'data' => null,
            ], 422);
        }

        $list->members()->detach($request->user()->id);

        return response()->json([
            'status' => true,
            'message' => 'Saliste de la lista correctamente.',
            'data' => null,
        ]);
    }
}
