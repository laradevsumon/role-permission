<?php

namespace Pkc\RolePermission\Traits;

use Pkc\RolePermission\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasPermissions
{
    /**
     * Get all permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function hasPermission(string $slug): bool
    {
        // Master admin has all permissions
        if ($this->slug === 'master-admin') {
            return true;
        }

        $permission = Permission::where('slug', $slug)->first();
        if (!$permission) {
            return false;
        }

        // 1. Direct Assignment: If the exact permission is assigned, always allow.
        if ($this->permissions()->where('permissions.id', $permission->id)->exists()) {
            return true;
        }

        // 2. Descendant Visibility: ONLY for modules, allow if any descendant is assigned.
        // This ensures the parent module (e.g., FMDF) is visible if a child (e.g., Stock) is assigned.
        if ($permission->type === 'module') {
            $descendantIds = $permission->getAllDescendantIds();
            if ($this->permissions()->whereIn('permissions.id', $descendantIds)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sync permissions to the role.
     * 
     * @param array $permissionIds Array of permission IDs
     * @param bool $recursive If true, will automatically include all children of the given permissions
     */
    public function syncPermissions(array $permissionIds, bool $recursive = false): void
    {
        if ($recursive) {
            $allIds = $permissionIds;
            $permissions = Permission::whereIn('id', $permissionIds)->get();
            foreach ($permissions as $permission) {
                $allIds = array_merge($allIds, $permission->getAllDescendantIds());
            }
            $permissionIds = array_unique($allIds);
        }

        $this->permissions()->sync($permissionIds);
    }

    /**
     * Alias for syncPermissions (for backward compatibility).
     */
    public function syncPermissionsWithChildren(array $permissionIds): void
    {
        $this->syncPermissions($permissionIds);
    }

    /**
     * Get all permission IDs including inherited from parents.
     */
    public function getAllPermissionIds(): array
    {
        return $this->permissions()->pluck('permissions.id')->toArray();
    }
}
