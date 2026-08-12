<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Services\Permissions\PermissionCatalog;
use Illuminate\Database\Seeder;

/**
 * Populate the `role_permissions` pivot from every domain's
 * `*Permissions::ROLE_PERMISSIONS` constant.
 *
 * Union semantics: a role slug that appears in MULTIPLE `*Permissions`
 * classes (e.g. `bursar` shows up in `HospitalPermissions` AND
 * `BursarPermissions`) ends up with the union of every permission
 * the catalogue grants it. This is the agreed contract — the slice
 * plan documents it; the regression test in
 * `tests/Feature/Permissions/PermissionServiceTest.php` pins it.
 *
 * Idempotent: the pivot uses `sync()` on each role, so re-running
 * the seeder rebuilds the EXACT membership expected from the
 * constants. A role that has been granted an ad-hoc permission via
 * the admin UI will lose that grant in the next run — that's the
 * trade-off for keeping the catalogue single-sourced.
 *
 * Wildcards: any role whose accumulated array contains `'*'` is
 * granted every permission in the catalogue (matches the previous
 * behaviour of `HospitalPermissions::roleAllows`).
 */
class RolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Phase 1: union every *Permissions class's contribution per role slug.
        // $accum['bursar'] ends up with hospital-bursar slugs + bursar class slugs.
        $accum = [];
        foreach (PermissionCatalog::all() as $permissionsClass) {
            foreach ($permissionsClass::ROLE_PERMISSIONS as $roleSlug => $perms) {
                $accum[$roleSlug] = array_merge($accum[$roleSlug] ?? [], $perms);
            }
        }

        // Phase 2: write the union to the pivot. Wildcards expand to
        // every catalogue row; otherwise the slugs are resolved to
        // permission ids via the Permission model.
        $allPermissionIds = Permission::pluck('id')->all();

        foreach ($accum as $roleSlug => $perms) {
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