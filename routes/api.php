<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserController::class, 'getProfile']);
    Route::prefix('profile')->group(function (Router $route) {
        $route->get('/user', [UserController::class, 'getProfile']);
        $route->get('/my-orders', [UserController::class, 'getMyOrders']);
    });
    Route::prefix('orders')->group(function (Router $route) {
        $route->get('/', [OrderController::class, 'getOrders']);
        $route->post('/preview', [OrderController::class, 'previewOrder']);
        $route->post('/', [OrderController::class, 'storeOrder']);
        $route->post('/{orderId}/cancel', [OrderController::class, 'cancelOrder']);
    });

});
