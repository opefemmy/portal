<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Services\Hospital\HospitalPermissions;
use Illuminate\Database\Seeder;

/**
 * Populate the `role_permissions` pivot from the existing
 * `HospitalPermissions::ROLE_PERMISSIONS` constant.
 *
 * Idempotent: the pivot uses `sync()` on each role, so re-running
 * the seeder rebuilds the EXACT membership expected from the
 * constant. A role that has been granted an ad-hoc permission via
 * the admin UI will lose that grant in the next run — that's the
 * trade-off for keeping the catalogue single-sourced.
 *
 * Wildcards: any role whose array contains `'*'` is granted every
 * permission in the catalogue (matches the previous behaviour of
 * `HospitalPermissions::roleAllows`).
 */
class RolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissionIds = Permission::pluck('id')->all();

        foreach (HospitalPermissions::ROLE_PERMISSIONS as $roleSlug => $perms) {
            $role = Role::where('slug', $roleSlug)->first();
            if (!$role) {
                continue;
            }

            $isWildcard = in_array('*', $perms, true);

            $ids = $isWildcard
                ? $allPermissionIds
                : Permission::whereIn('slug', $perms)->pluck('id')->all();

            $role->permissions()->sync($ids);
        }
    }
}
