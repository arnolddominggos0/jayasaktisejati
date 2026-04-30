<?php

namespace App\Exceptions;

/**
 * Exception thrown when authentication fails.
 */
class AuthenticationException extends ApplicationException
{
    protected int $statusCode = 401;
    protected string $errorCode = 'UNAUTHORIZED';

    public function __construct(string $message = 'Authentication required')
    {
        parent::__construct($message);
    }
}
