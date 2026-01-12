<?php

namespace Pkc\RolePermission\Traits;

use Pkc\RolePermission\Models\Role;
use Pkc\RolePermission\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Pkc\RolePermission\Exceptions\PermissionNotFoundException;

trait HasRoles
{
    /**
     * Get the role of the user.
     */
    public function role(): BelongsTo
    {
        $roleModel = config('role-permission.role_model');
        
        return $this->belongsTo($roleModel);
    }

    /**
     * Check if the user has a specific permission.
     * 
     * @param string $slug The permission slug to check
     * @return bool
     */
    public function hasPermission(string $slug): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->hasPermission($slug);
    }

    /**
     * Check if the user can access a specific route.
     * 
     * @param string $routeName The route name to check
     * @return bool
     */
    public function canAccessRoute(string $routeName): bool
    {
        if (!$routeName) {
            return true;
        }

        $permissionModel = config('role-permission.permission_model');
        $permission = $permissionModel::where('route_name', $routeName)->first();
        
        // If no permission is defined for this route, allow access
        if (!$permission) {
            return true;
        }
        
        return $this->hasPermission($permission->slug);
    }

    /**
     * Check if the user has any of the given permissions.
     * 
     * @param array $slugs Array of permission slugs
     * @return bool
     */
    public function hasAnyPermission(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user has all of the given permissions.
     * 
     * @param array $slugs Array of permission slugs
     * @return bool
     */
    public function hasAllPermissions(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if (!$this->hasPermission($slug)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the user has a specific role.
     * 
     * @param string $slug The role slug to check
     * @return bool
     */
    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    /**
     * Check if the user has any of the given roles.
     * 
     * @param array $slugs Array of role slugs
     * @return bool
     */
    public function hasAnyRole(array $slugs): bool
    {
        if (!$this->role) {
            return false;
        }

        return in_array($this->role->slug, $slugs, true);
    }

    /**
     * Scope to exclude MasterAdmin users from queries.
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeVisible(Builder $query): Builder
    {
        $masterAdminSlug = config('role-permission.master_admin_slug', 'master-admin');
        
        return $query->whereHas('role', function ($q) use ($masterAdminSlug) {
            $q->where('slug', '!=', $masterAdminSlug);
        });
    }
}
