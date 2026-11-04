<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShoppingListController;
use App\Http\Controllers\Api\ShoppingListItemController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);

    Route::apiResource('lists', ShoppingListController::class);
    Route::patch('/lists/{list}/complete', [ShoppingListController::class, 'complete']);

    Route::post('/lists/{list}/items/bulk', [ShoppingListItemController::class, 'bulkStore']);
    Route::post('/lists/{list}/items', [ShoppingListItemController::class, 'store']);
    Route::patch('/lists/{list}/items/{item}', [ShoppingListItemController::class, 'update']);
    Route::delete('/lists/{list}/items/{item}', [ShoppingListItemController::class, 'destroy']);

    Route::prefix('stats')->controller(StatsController::class)->group(function () {
        Route::get('/summary', 'summary');
        Route::get('/monthly', 'monthly');
        Route::get('/categories', 'categories');
        Route::get('/products', 'products');
    });
});
