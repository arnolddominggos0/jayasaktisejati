<?php

namespace App\Http\Middleware;

use App\Exceptions\ApplicationException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiExceptionHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if response is an error and request expects JSON
        if (!$response->isSuccessful() && $request->expectsJson()) {
            $this->formatErrorResponse($response);
        }

        return $response;
    }

    /**
     * Handle exceptions that occur during request handling.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Cleanup if needed
    }

    /**
     * Format error response for API consistency.
     */
    private function formatErrorResponse(Response $response): void
    {
        $statusCode = $response->getStatusCode();
        
        if ($statusCode >= 400 && $statusCode < 600) {
            $content = $response->getContent();
            $data = json_decode($content, true) ?? [];

            $formatted = [
                'success' => false,
                'error' => [
                    'code' => $this->getErrorCode($statusCode),
                    'message' => $data['message'] ?? $this->getDefaultMessage($statusCode),
                    'context' => $data['errors'] ?? [],
                ],
            ];

            $response->setContent(json_encode($formatted));
            $response->headers->set('Content-Type', 'application/json');
        }
    }

    /**
     * Get error code based on HTTP status.
     */
    private function getErrorCode(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            422 => 'VALIDATION_ERROR',
            429 => 'TOO_MANY_REQUESTS',
            500 => 'INTERNAL_ERROR',
            502 => 'BAD_GATEWAY',
            503 => 'SERVICE_UNAVAILABLE',
            default => 'ERROR',
        };
    }

    /**
     * Get default message based on HTTP status.
     */
    private function getDefaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad request',
            401 => 'Authentication required',
            403 => 'Access denied',
            404 => 'Resource not found',
            422 => 'Validation failed',
            429 => 'Too many requests',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
            default => 'An error occurred',
        };
    }
}
