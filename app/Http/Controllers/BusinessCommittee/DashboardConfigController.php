<?php

namespace App\Http\Controllers\BusinessCommittee;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the business-committee audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['business_committee'];
    }

    protected function dashboardRouteName(): string
    {
        return 'business-committee.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'business-committee.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'business-committee.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'business_committee.dashboard.configure';
    }
}