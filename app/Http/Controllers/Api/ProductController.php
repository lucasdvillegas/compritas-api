<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Productos obtenidos correctamente.',
            'data' => Product::with('category')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $product = Product::create($validated)->load('category');

        return response()->json([
            'status' => true,
            'message' => 'Producto creado correctamente.',
            'data' => $product,
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Producto obtenido correctamente.',
            'data' => $product->load('category'),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:products,name,'.$product->id,
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $product->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => $product->load('category'),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Producto eliminado correctamente.',
            'data' => null,
        ]);
    }
}
