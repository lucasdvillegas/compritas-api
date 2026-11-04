<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductUnit;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace();

        return response()->json([
            'status' => true,
            'message' => 'Productos obtenidos correctamente.',
            'data' => $workspace->products()->with('category')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace();

        $validated = $request->validate($this->rules($workspace->id));

        $product = $workspace->products()->create($validated)->load('category');

        return response()->json([
            'status' => true,
            'message' => 'Producto creado correctamente.',
            'data' => $product,
        ], 201);
    }

    public function show(Request $request, int $product): JsonResponse
    {
        $product = $request->user()->currentWorkspace()
            ->products()
            ->with('category')
            ->findOrFail($product);

        return response()->json([
            'status' => true,
            'message' => 'Producto obtenido correctamente.',
            'data' => $product,
        ]);
    }

    public function update(Request $request, int $product): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace();

        $product = $workspace->products()->findOrFail($product);

        $validated = $request->validate($this->rules($workspace->id, $product->id));

        $product->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => $product->load('category'),
        ]);
    }

    public function destroy(Request $request, int $product): JsonResponse
    {
        $product = $request->user()->currentWorkspace()
            ->products()
            ->findOrFail($product);

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Producto eliminado correctamente.',
            'data' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(int $workspaceId, ?int $productId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')
                    ->where(fn ($query) => $query->where('workspace_id', $workspaceId))
                    ->ignore($productId),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where(fn ($query) => $query->where('workspace_id', $workspaceId)),
            ],
            'unit' => ['sometimes', Rule::enum(ProductUnit::class)],
        ];
    }
}
