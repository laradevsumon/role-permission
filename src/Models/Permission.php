<?php

namespace Pkc\RolePermission\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Pkc\RolePermission\Exceptions\CircularReferenceException;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'type',
        'route_name',
        'order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'order' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Permission $permission) {
            // Prevent permission from being its own parent (only if permission exists)
            if ($permission->parent_id && $permission->id && $permission->parent_id === $permission->id) {
                throw new CircularReferenceException('Permission cannot be its own parent.');
            }

            // Check for circular references (only if permission exists and has parent_id)
            if ($permission->exists && $permission->parent_id && $permission->isDirty('parent_id')) {
                // Get all descendants of the current permission
                $descendants = static::getAllDescendantIdsRecursive($permission->id);
                // Check if the new parent is a descendant of this permission
                if (in_array($permission->parent_id, $descendants, true)) {
                    throw new CircularReferenceException('Circular reference detected: cannot set a descendant as parent.');
                }
            }
        });
    }

    /**
     * Get the parent permission.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    /**
     * Get the child permissions.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Permission::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get all descendants recursively (eager loading).
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        $roleModel = config('role-permission.role_model');
        $rolePermissionsTable = config('role-permission.table_names.role_permissions', 'role_permissions');
        
        return $this->belongsToMany($roleModel, $rolePermissionsTable);
    }

    /**
     * Get all descendant IDs (flat array) recursively.
     * Optimized to use a single query when possible.
     */
    public function getAllDescendantIds(): array
    {
        // Use a more efficient approach with a single query
        return static::getAllDescendantIdsRecursive($this->id);
    }

    /**
     * Static method to get all descendant IDs for a permission ID.
     * This method uses a recursive query approach.
     */
    protected static function getAllDescendantIdsRecursive(int $permissionId): array
    {
        $descendants = [];
        $children = static::where('parent_id', $permissionId)->pluck('id')->toArray();
        
        foreach ($children as $childId) {
            $descendants[] = $childId;
            $descendants = array_merge($descendants, static::getAllDescendantIdsRecursive($childId));
        }
        
        return array_unique($descendants);
    }

    /**
     * Get all ancestor IDs for a permission ID (used for circular reference check).
     */
    protected static function getAncestorIds(int $permissionId): array
    {
        $ancestors = [];
        $permission = static::find($permissionId);
        
        while ($permission && $permission->parent_id) {
            $ancestors[] = $permission->parent_id;
            $permission = static::find($permission->parent_id);
        }
        
        return $ancestors;
    }

    /**
     * Get all ancestor permissions (parent chain).
     */
    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $parent = $this->parent;
        
        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }
        
        return $ancestors;
    }

    /**
     * Scope to get only root permissions (no parent).
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get only active permissions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Get the full tree structure for a permission and its descendants.
     */
    public function toTreeArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'route_name' => $this->route_name,
            'order' => $this->order,
            'status' => $this->status,
            'children' => $this->children->map(fn($child) => $child->toTreeArray())->toArray(),
        ];
    }
}
