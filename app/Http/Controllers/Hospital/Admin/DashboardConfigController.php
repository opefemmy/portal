<?php

namespace App\Http\Controllers\Hospital\Admin;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the hospital_admin audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['hospital_admin', 'cmd', 'super_admin', 'admin'];
    }

    protected function dashboardRouteName(): string
    {
        return 'hospital.admin.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'hospital.admin.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'hospital.admin.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'reports.daily-revenue';
    }
}