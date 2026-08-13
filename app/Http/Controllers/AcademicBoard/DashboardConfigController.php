<?php

namespace App\Http\Controllers\AcademicBoard;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the academic-board audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['academic_board'];
    }

    protected function dashboardRouteName(): string
    {
        return 'academic-board.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'academic-board.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'academic-board.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'academic.dashboard.configure';
    }
}