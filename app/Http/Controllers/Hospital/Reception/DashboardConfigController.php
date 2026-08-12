<?php

namespace App\Http\Controllers\Hospital\Reception;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the receptionist audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['hospital_receptionist', 'cmd', 'super_admin', 'admin'];
    }

    protected function dashboardRouteName(): string
    {
        return 'hospital.reception.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'hospital.reception.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'hospital.reception.dashboard-config.update';
    }
}