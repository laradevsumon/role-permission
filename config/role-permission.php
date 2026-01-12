<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class that uses the HasRoles trait.
    | You can override this in your .env file if needed.
    |
    */
    'user_model' => env('ROLE_PERMISSION_USER_MODEL', \App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Role Model
    |--------------------------------------------------------------------------
    |
    | The role model class. You can extend this model in your application.
    |
    */
    'role_model' => \Pkc\RolePermission\Models\Role::class,

    /*
    |--------------------------------------------------------------------------
    | Permission Model
    |--------------------------------------------------------------------------
    |
    | The permission model class. You can extend this model in your application.
    |
    */
    'permission_model' => \Pkc\RolePermission\Models\Permission::class,

    /*
    |--------------------------------------------------------------------------
    | Master Admin Slug
    |--------------------------------------------------------------------------
    |
    | The slug of the master admin role that has all permissions by default.
    | This role bypasses all permission checks.
    |
    */
    'master_admin_slug' => env('ROLE_MASTER_ADMIN_SLUG', 'master-admin'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Enable caching for permission checks to improve performance.
    | Cache TTL is in seconds (default: 1 hour).
    |
    */
    'cache_enabled' => env('ROLE_PERMISSION_CACHE_ENABLED', true),
    'cache_ttl' => env('ROLE_PERMISSION_CACHE_TTL', 3600),
    'cache_prefix' => env('ROLE_PERMISSION_CACHE_PREFIX', 'role_permission'),

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | You can customize the table names if needed.
    |
    */
    'table_names' => [
        'roles' => env('ROLE_PERMISSION_ROLES_TABLE', 'roles'),
        'permissions' => env('ROLE_PERMISSION_PERMISSIONS_TABLE', 'permissions'),
        'role_permissions' => env('ROLE_PERMISSION_ROLE_PERMISSIONS_TABLE', 'role_permissions'),
    ],
];

