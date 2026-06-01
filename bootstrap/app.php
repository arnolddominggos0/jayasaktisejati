<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

use App\Http\Middleware\ScopeByBranch;
use App\Http\Middleware\ApiExceptionHandler;
use App\Exceptions\ApplicationException;


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
            'api.exception'       => ApiExceptionHandler::class,
        ]);
    })
    // bootstrap/app.php
    ->withExceptions(function (Illuminate\Foundation\Configuration\Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Illuminate\Http\Request $request) {
            if (! $request->is('api/*')) {
                return null; 
            }

            // Handle custom ApplicationException
            if ($e instanceof ApplicationException) {
                return response()->json($e->toArray(), $e->getStatusCode());
            }

            if ($e instanceof Illuminate\Validation\ValidationException) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'The given data was invalid.',
                        'context' => $e->errors(),
                    ]
                ], $e->status);
            }

            if ($e instanceof Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $status  = $e->getStatusCode();
                $message = trim($e->getMessage()) ?: 'Error';
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'HTTP_ERROR',
                        'message' => $message,
                        'context' => [],
                    ]
                ], $status);
            }

            // Let Laravel handle other exceptions in non-production
            if (config('app.debug')) {
                return null;
            }

            // Production: return generic error
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'An unexpected error occurred.',
                    'context' => [],
                ]
            ], 500);
        });
    })
    ->create();
