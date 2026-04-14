<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Exceptions\ApplicationException;

trait ApiResponseTrait
{
    /**
     * Return a successful JSON response.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated successful response.
     */
    protected function paginatedResponse(
        mixed $paginator,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
        ], $statusCode);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(
        string $message,
        string $errorCode = 'ERROR',
        int $statusCode = 400,
        array $context = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'context' => $context,
            ],
        ], $statusCode);
    }

    /**
     * Return response from an ApplicationException.
     */
    protected function exceptionResponse(ApplicationException $exception): JsonResponse
    {
        return response()->json($exception->toArray(), $exception->getStatusCode());
    }

    /**
     * Return a created response (201).
     */
    protected function createdResponse(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a no content response (204).
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
