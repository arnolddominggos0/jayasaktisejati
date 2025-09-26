<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

use App\Http\Middleware\ScopeByBranch;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web([]);

        $middleware->api([
            // EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'role'                => RoleMiddleware::class,
            'permission'          => PermissionMiddleware::class,
            'scope.branch'        => ScopeByBranch::class,
            
        ]);
    })
    // bootstrap/app.php
    ->withExceptions(function (Illuminate\Foundation\Configuration\Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Illuminate\Http\Request $request) {
            if (! $request->is('api/*')) {
                return null; 
            }

            if ($e instanceof Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], $e->status);
            }

            if ($e instanceof Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $status  = $e->getStatusCode();
                $message = trim($e->getMessage()) ?: 'Error';
                return response()->json(['message' => $message], $status);
            }

            // selain itu biarkan Laravel yang tangani
            return null;
        });
    })
    ->create();
