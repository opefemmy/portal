<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the hospital root (cmd /
 * hospital_admin landing) audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['cmd', 'hospital_admin', 'super_admin', 'admin'];
    }

    protected function dashboardRouteName(): string
    {
        return 'hospital.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'hospital.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'hospital.dashboard-config.update';
    }
}