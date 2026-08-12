<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the auditor audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['auditor', 'internal_auditor', 'external_auditor', 'super_admin', 'admin'];
    }

    protected function dashboardRouteName(): string
    {
        return 'auditor.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'auditor.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'auditor.dashboard-config.update';
    }
}