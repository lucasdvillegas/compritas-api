<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ShoppingListController;
use App\Http\Controllers\Api\ShoppingListInvitationController;
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

    Route::get('/lists/{list}/members', [ShoppingListController::class, 'members']);
    Route::delete('/lists/{list}/members/me', [ShoppingListController::class, 'leave']);
    Route::delete('/lists/{list}/members/{user}', [ShoppingListController::class, 'removeMember'])
        ->whereNumber('user');

    Route::post('/lists/{list}/invitations', [ShoppingListInvitationController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::get('/lists/{list}/invitations', [ShoppingListInvitationController::class, 'index']);
    Route::delete('/lists/{list}/invitations/{invitation}', [ShoppingListInvitationController::class, 'destroy'])
        ->whereNumber('invitation');

    Route::post('/invitations/{invitation}/accept', [ShoppingListInvitationController::class, 'accept'])
        ->whereNumber('invitation');
    Route::post('/invitations/{invitation}/decline', [ShoppingListInvitationController::class, 'decline'])
        ->whereNumber('invitation');

    Route::post('/lists/{list}/items/bulk', [ShoppingListItemController::class, 'bulkStore']);
    Route::post('/lists/{list}/items', [ShoppingListItemController::class, 'store']);
    Route::patch('/lists/{list}/items/{item}', [ShoppingListItemController::class, 'update']);
    Route::delete('/lists/{list}/items/{item}', [ShoppingListItemController::class, 'destroy']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/pin/regenerate', [ProfileController::class, 'regeneratePin']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::prefix('stats')->controller(StatsController::class)->group(function () {
        Route::get('/summary', 'summary');
        Route::get('/monthly', 'monthly');
        Route::get('/categories', 'categories');
        Route::get('/products', 'products');
    });
});
