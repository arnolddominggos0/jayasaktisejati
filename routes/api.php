<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('user')->group(function () {
    // Public
    Route::post('/register', [AuthController::class, 'register'])->name('user.register');
    Route::post('/login',    [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('user.login');

    // Protected
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',     [AuthController::class, 'me'])->name('user.me');
        Route::post('/logout',[AuthController::class, 'logout'])->name('user.logout');
    });
});
