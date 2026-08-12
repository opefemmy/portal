<?php

namespace App\Http\Controllers\HOD;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the HOD audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['hod'];
    }

    protected function dashboardRouteName(): string
    {
        return 'hod.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'hod.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'hod.dashboard-config.update';
    }
}