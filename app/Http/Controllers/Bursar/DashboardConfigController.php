<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the bursar audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['bursar', 'bursary_officer', 'fees_officer', 'payment_officer',
                'cashier', 'accountant', 'account_officer', 'finance_officer',
                'finance', 'auditor', 'internal_auditor', 'external_auditor',
                'ict_admin', 'hospital_accountant', 'audit_bursar'];
    }

    protected function dashboardRouteName(): string
    {
        return 'bursar.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'bursar.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'bursar.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'bursar.dashboard.configure';
    }
}
