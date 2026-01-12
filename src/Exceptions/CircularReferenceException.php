<?php

namespace Pkc\RolePermission\Exceptions;

use Exception;

class CircularReferenceException extends Exception
{
    public function __construct(string $message = 'Circular reference detected in permission hierarchy.')
    {
        parent::__construct($message);
    }
}

