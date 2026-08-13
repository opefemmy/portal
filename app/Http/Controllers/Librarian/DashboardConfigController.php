<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the librarian audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['librarian', 'library_officer', 'library_assistant'];
    }

    protected function dashboardRouteName(): string
    {
        return 'librarian.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'librarian.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'librarian.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'librarian.dashboard.configure';
    }
}