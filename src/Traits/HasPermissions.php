<?php

namespace Pkc\RolePermission\Traits;

use Pkc\RolePermission\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Pkc\RolePermission\Exceptions\InvalidPermissionAssignmentException;
use Pkc\RolePermission\Exceptions\PermissionNotFoundException;

trait HasPermissions
{
    /**
     * Get all permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        $permissionModel = config('role-permission.permission_model');
        $rolePermissionsTable = config('role-permission.table_names.role_permissions', 'role_permissions');
        
        return $this->belongsToMany($permissionModel, $rolePermissionsTable);
    }

    /**
     * Check if the role has a specific permission.
     * 
     * @param string $slug The permission slug to check
     * @return bool
     */
    public function hasPermission(string $slug): bool
    {
        $masterAdminSlug = config('role-permission.master_admin_slug', 'master-admin');
        
        // Master admin has all permissions
        if ($this->slug === $masterAdminSlug) {
            return true;
        }

        // Check cache if enabled
        $cacheEnabled = config('role-permission.cache_enabled', true);
        
        if ($cacheEnabled) {
            $cacheKey = $this->getPermissionCacheKey($slug);
            $cacheTtl = config('role-permission.cache_ttl', 3600);
            
            return Cache::remember($cacheKey, $cacheTtl, function () use ($slug) {
                return $this->checkPermission($slug);
            });
        }

        return $this->checkPermission($slug);
    }

    /**
     * Internal method to check permission without caching.
     * 
     * @param string $slug
     * @return bool
     */
    protected function checkPermission(string $slug): bool
    {
        $permissionModel = config('role-permission.permission_model');
        $permission = $permissionModel::where('slug', $slug)->first();
        
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
            if (!empty($descendantIds) && $this->permissions()->whereIn('permissions.id', $descendantIds)->exists()) {
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
     * @return void
     * @throws InvalidPermissionAssignmentException
     */
    public function syncPermissions(array $permissionIds, bool $recursive = false): void
    {
        $permissionModel = config('role-permission.permission_model');
        
        // Validate all permission IDs exist
        if (!empty($permissionIds)) {
            $existingIds = $permissionModel::whereIn('id', $permissionIds)->pluck('id')->toArray();
            $invalidIds = array_diff($permissionIds, $existingIds);
            
            if (!empty($invalidIds)) {
                throw new InvalidPermissionAssignmentException(
                    "Invalid permission IDs: " . implode(', ', $invalidIds)
                );
            }
        }

        if ($recursive) {
            $allIds = $permissionIds;
            $permissions = $permissionModel::whereIn('id', $permissionIds)->get();
            
            foreach ($permissions as $permission) {
                $allIds = array_merge($allIds, $permission->getAllDescendantIds());
            }
            
            $permissionIds = array_unique($allIds);
        }

        $this->permissions()->sync($permissionIds);
        
        // Clear cache after syncing permissions
        $this->clearPermissionCache();
    }

    /**
     * Alias for syncPermissions (for backward compatibility).
     * 
     * @param array $permissionIds
     * @return void
     */
    public function syncPermissionsWithChildren(array $permissionIds): void
    {
        $this->syncPermissions($permissionIds, false);
    }

    /**
     * Get all permission IDs including inherited from parents.
     * 
     * @return array
     */
    public function getAllPermissionIds(): array
    {
        return $this->permissions()->pluck('permissions.id')->toArray();
    }

    /**
     * Clear permission cache for this role.
     * 
     * Note: For optimal performance, use Redis or Memcached cache driver
     * which supports cache tags. Otherwise, this will clear all cache.
     * 
     * @return void
     */
    public function clearPermissionCache(): void
    {
        if (!config('role-permission.cache_enabled', true)) {
            return;
        }

        $cachePrefix = config('role-permission.cache_prefix', 'role_permission');
        $cacheKeyPrefix = "{$cachePrefix}.role.{$this->id}";
        
        try {
            // Try to use cache tags if available (Redis, Memcached)
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags([$cacheKeyPrefix])->flush();
            } else {
                // For file/database cache, we need to clear all cache
                // In production, consider using Redis/Memcached for better cache management
                Cache::flush();
            }
        } catch (\Exception $e) {
            // If cache clearing fails, just log it (don't break the application)
            Log::warning('Failed to clear permission cache', [
                'role_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get cache key for permission check.
     * 
     * @param string $slug
     * @return string
     */
    protected function getPermissionCacheKey(string $slug): string
    {
        $cachePrefix = config('role-permission.cache_prefix', 'role_permission');
        
        return "{$cachePrefix}.role.{$this->id}.permission." . md5($slug);
    }
}
