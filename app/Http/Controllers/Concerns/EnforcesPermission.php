<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Permissions\PermissionService;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Trait for any controller that needs strict per-role permissions.
 *
 * Cross-domain — used by hospital, bursar, registrar, librarian,
 * finance, executive, auditor, academic and business committee
 * controllers alike. The implementation is the same regardless of
 * audience: it reads through `PermissionService::allows()`, which
 * resolves against the `permissions` + `role_permissions` pivot
 * populated by `PermissionsSeeder` and `RolePermissionsSeeder`.
 *
 * The wildcard `'*'` from any `*Permissions::ROLE_PERMISSIONS` is
 * honoured by the service, so `super_admin`/`admin`/`cmd` etc.
 * pass every gate. Focused roles (bursar, registrar, librarian, …)
 * pass only the slugs their role holds in the pivot.
 *
 * Use `requirePermission()` at the top of any action that should be
 * restricted to certain roles; throws `AuthorizationException`
 * (-> 403) if the current user lacks the named permission.
 *
 * Sliced from `EnforcesHospitalPermission` in slice 8e — the body
 * is identical; the rename reflects that the trait is no longer
 * hospital-only.
 */
trait EnforcesPermission
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