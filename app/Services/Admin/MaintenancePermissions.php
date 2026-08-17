<?php

namespace App\Services\Admin;

/**
 * Centralised role → permission map for the system maintenance module.
 *
 * Covers Admin\MaintenanceController's 22 methods across 10 domains:
 * dashboard, health, updates, repairs, scanners, cache, backups,
 * logs, versions, report. Each domain is split into read-only
 * `*.view` and destructive `*.apply` / `*.repair` / `*.run` /
 * `*.manage` / `*.create` so a future maintenance_viewer role can
 * be granted the read-only subset without exposing destructive
 * endpoints.
 *
 * Slice 8i-maintenance: one slug per domain, not per method. The
 * maintenance surface is tool-shaped (run-migrations, clear-cache,
 * create-backup) rather than resource-shaped, so the per-domain
 * shape is the right granularity for the future role split.
 *
 * super_admin / admin / cmd pass every gate via wildcard. The
 * `ict_admin` and `staff` roles (currently the route's
 * `role:super_admin,admin,ict_admin,staff` allowlist) also pass
 * every gate via an explicit grant list — this slice does NOT
 * shrink their access; it just makes it visible in the catalogue.
 *
 * The grant list deliberately grants EVERY maintenance.* slug to
 * ict_admin and staff, because today those roles reach every
 * maintenance endpoint via the route-level `role:` middleware.
 * After this slice, the controller trait gate becomes the layer
 * that enforces access — and the catalogue rows for ict_admin /
 * staff mirror their current behaviour to avoid a 403 regression.
 * A future ops review (out of scope for this slice) can split
 * ict_admin into view-only and full-access roles and trim the
 * grant list.
 */
class MaintenancePermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        // Existing route-allowlist roles — full maintenance access
        // (preserves current behaviour).
        'ict_admin' => [
            'maintenance.dashboard.view',
            'maintenance.health.view',
            'maintenance.health.repair',
            'maintenance.updates.view',
            'maintenance.updates.apply',
            'maintenance.repairs.view',
            'maintenance.repairs.run',
            'maintenance.scanners.view',
            'maintenance.cache.view',
            'maintenance.cache.manage',
            'maintenance.backups.view',
            'maintenance.backups.create',
            'maintenance.logs.view',
            'maintenance.versions.view',
            'maintenance.versions.manage',
            'maintenance.report.view',
        ],
        'staff' => [
            // Same set as ict_admin — staff is the other half of the
            // route's allowlist. They get full maintenance access
            // today, and this slice doesn't change that.
            'maintenance.dashboard.view',
            'maintenance.health.view',
            'maintenance.health.repair',
            'maintenance.updates.view',
            'maintenance.updates.apply',
            'maintenance.repairs.view',
            'maintenance.repairs.run',
            'maintenance.scanners.view',
            'maintenance.cache.view',
            'maintenance.cache.manage',
            'maintenance.backups.view',
            'maintenance.backups.create',
            'maintenance.logs.view',
            'maintenance.versions.view',
            'maintenance.versions.manage',
            'maintenance.report.view',
        ],
    ];
}