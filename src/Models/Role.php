<?php

namespace Pkc\RolePermission\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Pkc\RolePermission\Traits\HasPermissions;
use App\Models\User; // Assuming User is in App\Models, dependent on main app

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
        return $this->hasMany(User::class);
    }

    /**
     * Scope to exclude MasterAdmin role from queries.
     */
    public function scopeVisible($query)
    {
        return $query->where('slug', '!=', 'master-admin');
    }
}
