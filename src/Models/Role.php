<?php

namespace Pkc\RolePermission\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Pkc\RolePermission\Traits\HasPermissions;

class Role extends Model
{
    use HasFactory, HasPermissions;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get all users with this role.
     */
    public function users(): HasMany
    {
        $userModel = config('role-permission.user_model');
        
        return $this->hasMany($userModel);
    }

    /**
     * Scope to exclude MasterAdmin role from queries.
     */
    public function scopeVisible(Builder $query): Builder
    {
        $masterAdminSlug = config('role-permission.master_admin_slug', 'master-admin');
        
        return $query->where('slug', '!=', $masterAdminSlug);
    }

    /**
     * Scope to get only active roles.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Check if this is the master admin role.
     */
    public function isMasterAdmin(): bool
    {
        $masterAdminSlug = config('role-permission.master_admin_slug', 'master-admin');
        
        return $this->slug === $masterAdminSlug;
    }
}
