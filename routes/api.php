<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserController::class, 'getProfile']);

    Route::prefix('orders')->group(function (Router $route) {
        $route->get('/', [OrderController::class, 'getOrders']);
        $route->post('/', [OrderController::class, 'storeOrder']);
        $route->post('/{orderId}/cancel', [OrderController::class, 'cancelOrder']);

    });

});
