<?php

namespace App\Services;

use Exception;
use Throwable;

class ItsIntegrationException extends Exception
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
