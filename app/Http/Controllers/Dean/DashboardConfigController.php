<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the dean audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['dean'];
    }

    protected function dashboardRouteName(): string
    {
        return 'dean.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'dean.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'dean.dashboard-config.update';
    }
}