<?php

namespace App\Http\Controllers\Hospital\Doctor;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the doctor audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['doctor', 'cmd', 'super_admin', 'admin'];
    }

    protected function dashboardRouteName(): string
    {
        return 'hospital.doctor.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'hospital.doctor.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'hospital.doctor.dashboard-config.update';
    }
}