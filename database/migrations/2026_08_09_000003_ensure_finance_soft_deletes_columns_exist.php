<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only safety net for soft-delete columns on finance tables.
 *
 * Same root cause as the 2026_08_09_000002 hospital safety net: the
 * 2024_07_06_000003_create_finance_tables migration never declared
 * `deleted_at` on any of the finance_* tables, but every Finance\*
 * model uses the SoftDeletes trait. That works in production because
 * a later migration added the columns there, but the local DB restored
 * from `database_backup_20260724.sql` predates that follow-up, so the
 * columns never landed.
 *
 * Symptom: any Finance model query 500s with
 * `Unknown column 'finance_*.deleted_at' in 'where clause'`. The
 * Finance Dashboard hits this first via
 * `Finance\DashboardController::index` reading `finance_receipts`.
 *
 * Production already has every column below (no-op there).
 *
 * Mirrors the existing defensive patterns: per-column Schema::hasColumn
 * guards, down() left as a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        $financeTables = [
            'finance_ledgers',
            'finance_allowances',
            'finance_deductions',
            'finance_vendors',
            'finance_invoices',
            'finance_receipts',
            'finance_transactions',
            'finance_refunds',
            'finance_cash_book',
            'finance_department_ledgers',
            'finance_budgets',
            'finance_budget_allocations',
            'finance_payroll',
            'finance_staff_allowances',
            'finance_staff_deductions',
            'finance_purchase_orders',
            'finance_vendor_payments',
        ];

        foreach ($financeTables as $table) {
            if (Schema::hasTable($table)
                && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        // No-op. Production keeps these columns; rolling them back
        // would break every SoftDeletes-using model read.
    }
};
