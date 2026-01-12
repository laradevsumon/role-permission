<?php

namespace Pkc\RolePermission\Exceptions;

use Exception;

class InvalidPermissionAssignmentException extends Exception
{
    public function __construct(string $message = 'Invalid permission assignment.')
    {
        parent::__construct($message);
    }
}

