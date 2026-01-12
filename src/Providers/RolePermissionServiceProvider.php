<?php

namespace Pkc\RolePermission\Providers;

use Illuminate\Support\ServiceProvider;

class RolePermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/role-permission.php',
            'role-permission'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Publish configuration file
        $this->publishes([
            __DIR__.'/../../config/role-permission.php' => config_path('role-permission.php'),
        ], 'role-permission-config');

        // Publish migrations (optional)
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'role-permission-migrations');
    }
}
