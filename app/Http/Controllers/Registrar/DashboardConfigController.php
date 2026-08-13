<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the registrar audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['registrar', 'admission_officer', 'super_admin', 'admin'];
    }

    protected function dashboardRouteName(): string
    {
        return 'registrar.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'registrar.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'registrar.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'registrar.dashboard.configure';
    }
}