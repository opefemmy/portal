<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Permissions the role grants, via the role_permissions pivot.
     *
     * The DB-backed pivot is the canonical read path; the legacy
     * `permissions` JSON column is still written by ERPRolesSeeder for
     * backward compatibility with code that hasn't migrated yet, but
     * new code reads through this relation (and through
     * App\Services\Permissions\PermissionService) instead.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
}