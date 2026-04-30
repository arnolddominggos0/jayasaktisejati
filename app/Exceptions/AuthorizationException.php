<?php

namespace App\Exceptions;

/**
 * Exception thrown when user is not authorized to perform an action.
 */
class AuthorizationException extends ApplicationException
{
    protected int $statusCode = 403;
    protected string $errorCode = 'FORBIDDEN';

    public function __construct(string $message = 'You are not authorized to perform this action')
    {
        parent::__construct($message);
    }
}
