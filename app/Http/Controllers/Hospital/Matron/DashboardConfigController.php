<?php

namespace App\Http\Controllers\Hospital\Matron;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the matron audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['matron', 'cmd', 'super_admin', 'admin'];
    }

    protected function dashboardRouteName(): string
    {
        return 'hospital.matron.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'hospital.matron.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'hospital.matron.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'wards.view';
    }
}