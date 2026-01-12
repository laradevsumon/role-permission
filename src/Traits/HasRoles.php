<?php

namespace Pkc\RolePermission\Traits;

use Pkc\RolePermission\Models\Role;
use Pkc\RolePermission\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasRoles
{
    /**
     * Get the role of the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $slug): bool
    {
        return $this->role?->hasPermission($slug) ?? false;
    }

    /**
     * Check if the user can access a specific route.
     */
    public function canAccessRoute(string $routeName): bool
    {
        $permission = Permission::where('route_name', $routeName)->first();
        
        // If no permission is defined for this route, allow access
        if (!$permission) {
            return true;
        }
        
        return $this->hasPermission($permission->slug);
    }

    /**
     * Scope to exclude MasterAdmin users from queries.
     */
    public function scopeVisible($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('slug', '!=', 'master-admin');
        });
    }
}
