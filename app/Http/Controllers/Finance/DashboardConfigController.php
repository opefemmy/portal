<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the finance audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['finance', 'finance_officer', 'accountant', 'account_officer',
                'cashier', 'hospital_accountant', 'bursary_officer',
                'fees_officer', 'payment_officer', 'auditor',
                'bursar', 'audit_bursar', 'audit'];
    }

    protected function dashboardRouteName(): string
    {
        return 'finance.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'finance.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'finance.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'finance.dashboard.configure';
    }
}