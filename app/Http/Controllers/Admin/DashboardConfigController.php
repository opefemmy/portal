<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\DashboardConfig\AbstractDashboardConfigController;

/**
 * Per-user dashboard widget configurator for the admin audience.
 *
 * Cross-user configuration (configuring another user's dashboard) is
 * restricted to super_admin via the route middleware at routes/web.php
 * — the admin role list returned here is informational; the middleware
 * is the actual gate.
 */
class DashboardConfigController extends AbstractDashboardConfigController
{
    protected function audienceRoles(): array
    {
        return ['super_admin', 'admin', 'ict_admin', 'staff'];
    }

    protected function dashboardRouteName(): string
    {
        return 'admin.dashboard';
    }
}