<?php

namespace App\Services\Executive;

/**
 * Centralised role → permission map for the executive module.
 *
 * Read-mostly across students, staff, finance revenue and hospital
 * admitted counts. The executive dashboard aggregates widgets from
 * every audience's resolver — the permissions here just gate the
 * view, not the underlying data writers.
 *
 * Mirrors the shape of `HospitalPermissions::ROLE_PERMISSIONS`.
 */
class ExecutivePermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'rector' => [
            'executive.students.view',
            'executive.students.stats',
            'executive.staff.view',
            'executive.finance.revenue.view',
            'executive.bursar.payments.view',
            'executive.bursar.debtors.view',
            'executive.hospital.admitted.view',
            'executive.hospital.revenue.view',
            'executive.dashboard.view',
            'executive.dashboard.configure',
        ],
    ];
}