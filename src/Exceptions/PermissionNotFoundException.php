<?php

namespace Pkc\RolePermission\Exceptions;

use Exception;

class PermissionNotFoundException extends Exception
{
    public function __construct(string $slug)
    {
        parent::__construct("Permission with slug '{$slug}' not found.");
    }
}

