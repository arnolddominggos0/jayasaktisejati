<?php

namespace App\Exceptions;

/**
 * Exception thrown when validation fails.
 */
class ValidationException extends ApplicationException
{
    protected int $statusCode = 422;
    protected string $errorCode = 'VALIDATION_ERROR';

    public function __construct(array $errors = [], string $message = 'Validation failed')
    {
        parent::__construct($message, null, null, ['errors' => $errors]);
    }

    /**
     * Create exception from Laravel ValidationException
     */
    public static function fromLaravel(\Illuminate\Validation\ValidationException $exception): self
    {
        return new self($exception->errors(), $exception->getMessage());
    }
}
