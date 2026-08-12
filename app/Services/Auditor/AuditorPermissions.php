<?php

namespace App\Services\Auditor;

/**
 * Centralised role → permission map for the audit module.
 *
 * Read-only cross-domain role: can see audit logs, failed actions,
 * deleted records and pending refunds across the system, plus
 * finance transactions for reconciliation. The actual
 * `audit.*` audit-log writes are produced by the framework's
 * middleware — auditors don't create their own authority, they
 * observe.
 *
 * Mirrors the shape of `HospitalPermissions::ROLE_PERMISSIONS`.
 */
class AuditorPermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'auditor' => [
            'auditor.audit.view',
            'auditor.audit.logs',
            'auditor.audit.failed-actions',
            'auditor.audit.deleted-records',
            'auditor.audit.pending-refunds',
            'auditor.finance.transactions.view',
            'auditor.finance.receipts.view',
            'auditor.bursar.payments.view',
            'auditor.bursar.debtors.view',
            'auditor.bursar.reports.view',
            'auditor.dashboard.view',
            'auditor.dashboard.configure',
        ],

        'internal_auditor' => [
            'auditor.audit.view',
            'auditor.audit.logs',
            'auditor.audit.failed-actions',
            'auditor.audit.deleted-records',
            'auditor.finance.transactions.view',
            'auditor.finance.receipts.view',
            'auditor.bursar.payments.view',
            'auditor.dashboard.view',
        ],

        'external_auditor' => [
            'auditor.audit.view',
            'auditor.audit.logs',
            'auditor.finance.transactions.view',
            'auditor.finance.receipts.view',
            'auditor.bursar.payments.view',
            'auditor.bursar.reports.view',
            'auditor.dashboard.view',
        ],

        'audit_bursar' => [
            'auditor.audit.view',
            'auditor.audit.logs',
            'auditor.bursar.payments.view',
            'auditor.bursar.debtors.view',
            'auditor.bursar.reports.view',
            'auditor.dashboard.view',
        ],
    ];
}