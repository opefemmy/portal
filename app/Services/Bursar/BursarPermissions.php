<?php

namespace App\Services\Bursar;

/**
 * Centralised role → permission map for the bursary / payments module.
 *
 * Mirrors the shape of `HospitalPermissions::ROLE_PERMISSIONS`. The
 * cross-domain `PermissionsSeeder` iterates
 * `PermissionCatalog::all()` and unions per-role grants from every
 * `*Permissions` class, so a bursar with `bursar.*` slugs here also
 * keeps any hospital-bursar slugs that `HospitalPermissions` granted
 * (e.g. `billing.invoice`, `billing.payment`).
 *
 * Wildcards (`'*'`) grant every catalogue row to the role — same
 * semantics as `PermissionService::allPermissionsFor()`.
 */
class BursarPermissions
{
    public const ROLE_PERMISSIONS = [
        // Super-roles: full access across every domain (wildcard).
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        // Head bursar: every bursar.* slug.
        'bursar' => [
            'bursar.payments.view',
            'bursar.payments.create',
            'bursar.payments.verify',
            'bursar.payments.export',
            'bursar.debtors.view',
            'bursar.debtors.export',
            'bursar.fees.view',
            'bursar.fees.configure',
            'bursar.regimes.view',
            'bursar.regimes.configure',
            'bursar.reports.view',
            'bursar.reports.export',
            'bursar.dashboard.view',
            'bursar.dashboard.configure',
        ],

        // Focused officers: view + verify, no configure.
        'bursary_officer' => [
            'bursar.payments.view',
            'bursar.payments.verify',
            'bursar.debtors.view',
            'bursar.debtors.export',
            'bursar.fees.view',
            'bursar.reports.view',
            'bursar.dashboard.view',
        ],

        'fees_officer' => [
            'bursar.payments.view',
            'bursar.debtors.view',
            'bursar.fees.view',
            'bursar.fees.configure',
            'bursar.regimes.view',
            'bursar.reports.view',
            'bursar.dashboard.view',
        ],

        'payment_officer' => [
            'bursar.payments.view',
            'bursar.payments.create',
            'bursar.payments.verify',
            'bursar.debtors.view',
            'bursar.reports.view',
            'bursar.dashboard.view',
        ],

        // Cash-side staff: create payments + receipts, no verify.
        'cashier' => [
            'bursar.payments.view',
            'bursar.payments.create',
            'bursar.dashboard.view',
        ],

        'accountant' => [
            'bursar.payments.view',
            'bursar.payments.create',
            'bursar.debtors.view',
            'bursar.fees.view',
            'bursar.reports.view',
            'bursar.dashboard.view',
        ],

        'account_officer' => [
            'bursar.payments.view',
            'bursar.debtors.view',
            'bursar.fees.view',
            'bursar.reports.view',
            'bursar.dashboard.view',
        ],

        'finance_officer' => [
            'bursar.payments.view',
            'bursar.payments.export',
            'bursar.debtors.view',
            'bursar.fees.view',
            'bursar.regimes.view',
            'bursar.reports.view',
            'bursar.reports.export',
            'bursar.dashboard.view',
        ],

        // Hospital accounting staff: bursar views + hospital cash side.
        // Hospital-side slugs (`billing.*`) are seeded by HospitalPermissions.
        'hospital_accountant' => [
            'bursar.payments.view',
            'bursar.payments.create',
            'bursar.debtors.view',
            'bursar.reports.view',
            'bursar.dashboard.view',
        ],
    ];
}