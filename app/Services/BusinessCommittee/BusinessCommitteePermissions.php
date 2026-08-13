<?php

namespace App\Services\BusinessCommittee;

/**
 * Centralised role → permission map for the business committee module.
 *
 * Created in slice 8e because the business_committee audience had
 * no permission slugs prior to this slice — the role existed in
 * `roles` table and was routed via `LoginController::authenticated()`,
 * but every controller it reached was un-gated because there was
 * nothing to check against. Now the role has its own slug family.
 *
 * Covers the result-approval queue (`business_committee.results.*`)
 * and the dashboard surface. Mirrors the shape of every other
 * `*Permissions` class so the seeders pick it up automatically
 * once it's added to `PermissionCatalog::all()`.
 */
class BusinessCommitteePermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'business_committee' => [
            'business_committee.results.view',
            'business_committee.results.approve',
            'business_committee.dashboard.view',
            'business_committee.dashboard.configure',
        ],
    ];
}