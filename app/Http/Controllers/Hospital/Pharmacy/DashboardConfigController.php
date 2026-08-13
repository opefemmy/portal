<?php

namespace App\Http\Controllers\Hospital\Pharmacy;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the pharmacy audience.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['pharmacist', 'store_keeper', 'cmd', 'super_admin', 'admin'];
    }

    protected function dashboardRouteName(): string
    {
        return 'hospital.pharmacy.dashboard';
    }

    protected function configEditRouteName(): string
    {
        return 'hospital.pharmacy.dashboard-config.edit';
    }

    protected function configUpdateRouteName(): string
    {
        return 'hospital.pharmacy.dashboard-config.update';
    }

    protected function dashboardConfigPermissionSlug(): string
    {
        return 'pharmacy.view';
    }
}