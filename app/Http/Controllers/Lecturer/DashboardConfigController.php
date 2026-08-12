<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the lecturer audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['lecturer'];
    }

    protected function dashboardRouteName(): string
    {
        return 'lecturer.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'lecturer.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'lecturer.dashboard-config.update';
    }
}