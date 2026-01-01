<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', function (Request $request) {
        return $request->user();
    });
    Route::prefix('orders')->group(function (Router $route) {
        $route->post('/', [OrderController::class, 'storeOrder']);
        $route->post('/{orderId}/cancel', [OrderController::class, 'cancelOrder']);

    });

});
