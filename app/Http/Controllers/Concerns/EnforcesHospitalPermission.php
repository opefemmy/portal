<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Hospital\HospitalPermissions;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Trait for hospital controllers: enforce strict per-role permissions.
 *
 * Use `requirePermission()` at the top of any action that should be
 * restricted to certain roles; throws `AuthorizationException` (-> 403)
 * if the current user lacks the named permission.
 */
trait EnforcesHospitalPermission
{
    /**
     * Throw 403 if the current user does not have the named permission.
     */
    protected function requirePermission(string $permission): void
    {
        if (!HospitalPermissions::allows($permission)) {
            throw new AuthorizationException(
                "You are not authorised to perform this action ({$permission})."
            );
        }
    }
}