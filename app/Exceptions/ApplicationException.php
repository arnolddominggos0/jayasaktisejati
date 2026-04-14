<?php

namespace App\Exceptions;

use Exception;

/**
 * Base exception class for all application-specific exceptions.
 * 
 * Provides standardized error responses for API and web contexts.
 */
abstract class ApplicationException extends Exception
{
    /**
     * HTTP status code for this exception
     */
    protected int $statusCode = 500;

    /**
     * Error code for client reference
     */
    protected string $errorCode = 'INTERNAL_ERROR';

    /**
     * Additional error context
     */
    protected array $context = [];

    public function __construct(
        string $message = '',
        ?int $statusCode = null,
        ?string $errorCode = null,
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        
        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }
        
        if ($errorCode !== null) {
            $this->errorCode = $errorCode;
        }
        
        $this->context = $context;
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get error context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Convert exception to API response array
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage() ?: 'An error occurred',
                'context' => $this->context,
            ],
        ];
    }
}
