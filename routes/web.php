<?php

use App\Http\Controllers\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthenticationController::class, 'login'])
    ->middleware('guest');

Route::post('/logout', [AuthenticationController::class, 'logout'])
    ->middleware('auth');

Route::view('/{any?}', 'app')->where('any', '.*');
