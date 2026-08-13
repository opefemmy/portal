<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the executive audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['rector', 'super_admin'];
    }

    protected function dashboardRouteName(): string
    {
        return 'executive.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'executive.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'executive.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'executive.dashboard.configure';
    }
}