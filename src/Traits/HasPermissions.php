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
     * Sync permissions (Strictly Granular).
     * Does NOT automatically include children.
     */
    public function syncPermissionsWithChildren(array $permissionIds): void
    {
        // STRICTLY GRANULAR: Only sync specifically selected IDs
        // We do not want to auto-select children anymore.
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Get all permission IDs including inherited from parents.
     */
    public function getAllPermissionIds(): array
    {
        return $this->permissions()->pluck('permissions.id')->toArray();
    }
}
