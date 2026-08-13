<?php

namespace App\Http\Controllers\Hospital\Lab;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the lab audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['lab_scientist', 'cmd', 'super_admin', 'admin'];
    }

    protected function dashboardRouteName(): string
    {
        return 'hospital.lab.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'hospital.lab.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'hospital.lab.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'lab.view';
    }
}