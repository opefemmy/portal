<?php

namespace App\Http\Controllers\Hospital\Nurse;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the nurse audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['nurse', 'cmd', 'super_admin', 'admin'];
    }

    protected function dashboardRouteName(): string
    {
        return 'hospital.nurse.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'hospital.nurse.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'hospital.nurse.dashboard-config.update';
    }
}