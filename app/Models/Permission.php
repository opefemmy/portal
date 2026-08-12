<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'slug', 'group', 'description'];

    /**
     * Roles that hold this permission, via the role_permissions pivot.
     * Used by RolePermissionsSeeder to bulk-attach permissions and by
     * ad-hoc admin screens once a CRUD exists in a later slice.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }
}
