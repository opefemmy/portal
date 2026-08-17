<?php

namespace App\Services\Admin;

/**
 * Centralised role → permission map for the admin / system-management
 * module.
 *
 * Slice 8i-admin-academic-structure (sub-slice 1 of 8i-admin):
 * covers the 6 academic-structure controllers — SchoolController,
 * DepartmentController, ProgrammeController, SessionController,
 * GradingController, GradeController. Future sub-slices (8i-admin-
 * users, 8i-admin-students, 8i-admin-academic-ops, 8i-admin-fees,
 * 8i-admin-facilities, 8i-admin-misc) will add their slugs here.
 *
 * Per-controller slug shape (one slug covers all CRUD verbs on a
 * single resource) — mirrors Laravel's ResourceController
 * convention. A future per-verb split is a one-line change per
 * controller.
 *
 * super_admin / admin / cmd pass every gate via wildcard. The
 * `ict_admin` and `staff` roles (currently the route's
 * `role:super_admin,admin,ict_admin,staff` allowlist) also pass
 * every gate via an explicit grant list — this slice does NOT
 * shrink their access; it just makes it visible in the catalogue.
 *
 * Sub-slice 1 is the pilot for the broader 8i-admin migration. The
 * grant list deliberately grants EVERY academic-structure slug to
 * ict_admin and staff, because today those roles reach every
 * academic-structure endpoint via the route-level `role:`
 * middleware. After this slice, the controller trait gate becomes
 * the layer that enforces access — and the catalogue rows for
 * ict_admin / staff mirror their current behaviour to avoid a 403
 * regression. A future ops review (out of scope for this slice)
 * can split ict_admin into view-only and full-access roles and
 * trim the grant list.
 */
class AdminPermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        // Existing route-allowlist roles — full academic-structure
        // access (preserves current behaviour).
        'ict_admin' => [
            'admin.schools.manage',
            'admin.departments.manage',
            'admin.programmes.manage',
            'admin.sessions.manage',
            'admin.grading.manage',
            'admin.grades.manage',
        ],
        'staff' => [
            // Same set as ict_admin — staff is the other half of
            // the route's allowlist. They get full academic-structure
            // access today, and this slice doesn't change that.
            'admin.schools.manage',
            'admin.departments.manage',
            'admin.programmes.manage',
            'admin.sessions.manage',
            'admin.grading.manage',
            'admin.grades.manage',
        ],
    ];
}