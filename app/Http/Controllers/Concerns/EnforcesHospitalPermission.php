<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Permissions\PermissionService;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Trait for hospital controllers: enforce strict per-role permissions.
 *
 * Use `requirePermission()` at the top of any action that should be
 * restricted to certain roles; throws `AuthorizationException` (-> 403)
 * if the current user lacks the named permission.
 *
 * Reads the cross-domain `PermissionService` (role_permissions pivot,
 * populated by `RolePermissionsSeeder`). The legacy
 * `HospitalPermissions::allows()` wrapper is retained for backward
 * compatibility — direct use of the service here keeps the trait's
 * dependency obvious and lets non-hospital domains share the same
 * read path.
 */
trait EnforcesHospitalPermission
{
    /**
     * Throw 403 if the current user does not have the named permission.
     */
    protected function requirePermission(string $permission): void
    {
        if (!PermissionService::allows($permission)) {
            throw new AuthorizationException(
                "You are not authorised to perform this action ({$permission})."
            );
        }
    }
}