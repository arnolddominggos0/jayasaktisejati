<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Sanctum (untuk SPA/session-cookie di /api/*)
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// Spatie Permission (alias role/permission)
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /**
         * Global middleware (jarang perlu diubah di MVP).
         * Contoh: $middleware->append(\App\Http\Middleware\TrustProxies::class);
         */

        /**
         * Web group — biarkan default bawaan Laravel.
         * (Login/logout kamu sudah pakai middleware('web') dari routes.)
         */
        $middleware->web([
            // Tambah custom web middleware kamu di sini bila perlu
        ]);

        /**
         * API group — tambahkan Sanctum stateful checker
         * agar request ke /api/* yang membawa cookie sesi diperlakukan sebagai stateful.
         * Ini mendukung flow: GET /sanctum/csrf-cookie → POST /api/login (web) → GET /api/me (auth:sanctum).
         */
        $middleware->api([
            EnsureFrontendRequestsAreStateful::class,
            // Bisa menambahkan rate limiter khusus API di sini jika perlu
        ]);

        /**
         * Alias middleware (pengganti $routeMiddleware di Laravel ≤10).
         * Setelah ini, kamu bisa pakai:
         *   ->middleware(['auth:sanctum','role:office_admin'])
         *   ->middleware(['permission:shipment.create'])
         */
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // custom exception handling kalau perlu
    })->create();
