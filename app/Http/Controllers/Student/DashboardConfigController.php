<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the student audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['student'];
    }

    protected function dashboardRouteName(): string
    {
        return 'student.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'student.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'student.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'student.dashboard.configure';
    }
}