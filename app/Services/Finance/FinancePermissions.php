<?php

namespace App\Services\Finance;

/**
 * Centralised role → permission map for the finance module.
 *
 * Covers transactions, invoices, receipts, budgets, vendors and
 * payroll workflows. Note that bursar-specific slugs (payments
 * verification, debtors, regimes) live in `BursarPermissions` to
 * keep the two domains' permission sets independent — this class
 * is the broader finance operations layer (vouchers, vendors,
 * payroll) while bursary is the tuition-fee intake side.
 *
 * Mirrors the shape of `HospitalPermissions::ROLE_PERMISSIONS`.
 */
class FinancePermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'finance' => [
            'finance.transactions.view',
            'finance.transactions.create',
            'finance.transactions.edit',
            'finance.transactions.delete',
            'finance.transactions.export',
            'finance.invoices.view',
            'finance.invoices.create',
            'finance.invoices.edit',
            'finance.invoices.delete',
            'finance.invoices.send',
            'finance.receipts.view',
            'finance.receipts.create',
            'finance.receipts.print',
            'finance.receipts.export',
            'finance.budgets.view',
            'finance.budgets.create',
            'finance.budgets.edit',
            'finance.budgets.approve',
            'finance.vendors.view',
            'finance.vendors.create',
            'finance.vendors.edit',
            'finance.payroll.view',
            'finance.payroll.create',
            'finance.payroll.approve',
            'finance.dashboard.view',
            'finance.dashboard.configure',
        ],

        'finance_officer' => [
            'finance.transactions.view',
            'finance.transactions.create',
            'finance.transactions.edit',
            'finance.invoices.view',
            'finance.invoices.create',
            'finance.invoices.edit',
            'finance.receipts.view',
            'finance.receipts.create',
            'finance.receipts.print',
            'finance.budgets.view',
            'finance.vendors.view',
            'finance.payroll.view',
            'finance.dashboard.view',
        ],
    ];
}