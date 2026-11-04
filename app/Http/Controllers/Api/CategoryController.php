<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace();

        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);
        $search = trim((string) ($validated['search'] ?? ''));

        $categories = $workspace->categories()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Categorías obtenidas correctamente.',
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'has_more' => $categories->hasMorePages(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->where(fn ($query) => $query->where('workspace_id', $workspace->id)),
            ],
        ]);

        $category = $workspace->categories()->create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Categoría creada correctamente.',
            'data' => $category,
        ], 201);
    }

    public function show(Request $request, int $category): JsonResponse
    {
        $category = $request->user()->currentWorkspace()
            ->categories()
            ->findOrFail($category);

        return response()->json([
            'status' => true,
            'message' => 'Categoría obtenida correctamente.',
            'data' => $category,
        ]);
    }

    public function update(Request $request, int $category): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace();

        $category = $workspace->categories()->findOrFail($category);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->where(fn ($query) => $query->where('workspace_id', $workspace->id))
                    ->ignore($category->id),
            ],
        ]);

        $category->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Categoría actualizada correctamente.',
            'data' => $category,
        ]);
    }

    public function destroy(Request $request, int $category): JsonResponse
    {
        $category = $request->user()->currentWorkspace()
            ->categories()
            ->findOrFail($category);

        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Categoría eliminada correctamente.',
            'data' => null,
        ]);
    }
}
