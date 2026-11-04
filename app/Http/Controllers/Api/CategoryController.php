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

        return response()->json([
            'status' => true,
            'message' => 'Categorías obtenidas correctamente.',
            'data' => $workspace->categories()->orderBy('name')->get(),
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
