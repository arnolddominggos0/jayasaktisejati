<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/_debug/fc-login-mw', function () {
    $route = Route::getRoutes()->getByName('filament.fc.auth.login');

    return response()->json([
        'uri' => $route?->uri(),
        'action' => $route?->getActionName(),
        'middleware' => $route?->gatherMiddleware(),
    ]);
});