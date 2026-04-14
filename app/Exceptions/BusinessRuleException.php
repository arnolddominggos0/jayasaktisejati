<?php

namespace App\Exceptions;

/**
 * Exception thrown when a business rule is violated.
 */
class BusinessRuleException extends ApplicationException
{
    protected int $statusCode = 422;
    protected string $errorCode = 'BUSINESS_RULE_VIOLATION';

    public function __construct(string $message = 'Business rule violation', array $context = [])
    {
        parent::__construct($message, null, null, $context);
    }
}
