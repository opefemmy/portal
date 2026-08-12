<?php

namespace App\Services\Permissions;

use App\Models\User;
use App\Services\Hospital\HospitalPermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Read-only permission resolver.
 *
 * The hospital module has had a per-role permission matrix since the
 * module landed (see `HospitalPermissions::ROLE_PERMISSIONS`). This
 * service is the cross-domain replacement: a single `allows()` call
 * that works for any role (hospital, bursar, registrar, admin, …) and
 * that reads the role_permissions DB pivot populated by
 * RolePermissionsSeeder.
 *
 * The wildcard entry `'*'` from `HospitalPermissions::ROLE_PERMISSIONS`
 * is honoured as "this role can claim every permission in every group"
 * — same semantics as before, so dropping this in front of the existing
 * callers is a behaviour-preserving change.
 *
 * Per-request cache: the same user is queried many times in a single
 * render (sidebar + dashboard + controller + blade directives). The
 * cache avoids a per-call pivot query. Call `flush()` from a model
 * observer if you update `role_permissions` at runtime.
 */
class PermissionService
{
    /** @var array<int, string[]> user_id => [permission_slug, …] */
    protected static array $cache = [];

    /**
     * Whether the user has the named permission.
     *
     * @param  \App\Models\User|null  $user  Defaults to the currently authenticated user.
     */
    public static function allows(string $permission, ?User $user = null): bool
    {
        $user ??= Auth::user();
        if (!$user) {
            return false;
        }

        return in_array($permission, self::allPermissionsFor($user), true);
    }

    /**
     * Whether the user has at least one of the named permissions.
     *
     * @param  array<int, string>  $permissions
     */
    public static function allowsAny(array $permissions, ?User $user = null): bool
    {
        $user ??= Auth::user();
        if (!$user) {
            return false;
        }

        $slugs = self::allPermissionsFor($user);
        foreach ($permissions as $permission) {
            if (in_array($permission, $slugs, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * The primary role slug of the given user, or null if unauthenticated.
     *
     * This is the same resolver consumed by `HospitalPermissions::currentRole()`.
     */
    public static function roleSlugFor(?User $user = null): ?string
    {
        $user ??= Auth::user();
        return $user?->role?->slug;
    }

    /**
     * All permission slugs the user can claim, including those granted
     * by any role in the user_role pivot (multi-role support).
     *
     * @return array<int, string>
     */
    public static function allPermissionsFor(User $user): array
    {
        $cacheKey = $user->id;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        // `HospitalPermissions::ROLE_PERMISSIONS` is the single source
        // of truth for the wildcard marker; any role whose array
        // contains `'*'` is granted every permission in the catalogue.
        $roleSlugs = $user->allRoleSlugs();
        foreach ($roleSlugs as $roleSlug) {
            $perms = HospitalPermissions::ROLE_PERMISSIONS[$roleSlug] ?? [];
            if (in_array('*', $perms, true)) {
                return self::$cache[$cacheKey] = Permission::pluck('slug')->all();
            }
        }

        // Otherwise load the pivot rows that join permissions to the
        // user's roles (primary + pivot). One query, no joins in PHP.
        $slugs = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
            ->whereIn('roles.slug', $roleSlugs)
            ->pluck('permissions.slug')
            ->all();

        return self::$cache[$cacheKey] = array_values(array_unique($slugs));
    }

    /**
     * Drop the per-request cache. Useful after a runtime update of
     * `role_permissions` (e.g. when an admin grants a new permission).
     */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
