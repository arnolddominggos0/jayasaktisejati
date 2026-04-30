<?php

namespace App\Exceptions;

/**
 * Exception thrown when a requested resource is not found.
 */
class NotFoundException extends ApplicationException
{
    protected int $statusCode = 404;
    protected string $errorCode = 'RESOURCE_NOT_FOUND';

    public function __construct(string $resource = 'Resource', string $identifier = '', array $context = [])
    {
        $message = $identifier 
            ? "{$resource} with identifier '{$identifier}' not found"
            : "{$resource} not found";
            
        parent::__construct($message, null, null, $context);
    }
}
