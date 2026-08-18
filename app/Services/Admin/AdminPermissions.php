<?php

namespace App\Services\Admin;

/**
 * Centralised role → permission map for the admin / system-management
 * module.
 *
 * Slice 8i-admin-academic-structure (sub-slice 1 of 8i-admin):
 * covers the 6 academic-structure controllers — SchoolController,
 * DepartmentController, ProgrammeController, SessionController,
 * GradingController, GradeController.
 *
 * Slice 8i-admin-users (sub-slice 2 of 8i-admin):
 * adds 3 user-management controllers — UserController (CRUD +
 * activate/deactivate/reset-password/upload/search), UserRoleController
 * (per-user multi-role assignment), UserUnlockController
 * (admin-only unlock + reset endpoints).
 *
 * Slice 8i-admin-students (sub-slice 3 of 8i-admin):
 * adds 4 student-management controllers — StudentController
 * (15 methods: index/show/create/store/edit/update/destroy/
 * resetPassword/getLGAs/upload/downloadTemplate/
 * showMeasurements/editMeasurements/updateMeasurements/
 * exportMeasurements), StudentImportController (3 methods),
 * StudentIdCardController (4 methods), AdmissionCentreController
 * (7 methods).
 *
 * Slice 8i-admin-academic-ops (sub-slice 4 of 8i-admin):
 * adds 6 academic-operations controllers — CourseController
 * (8 methods: index/uploadForm/upload/create/store/edit/update/
 * destroy), CourseAssignmentController (6 methods),
 * CourseRegistrationController (4 methods: index/unsubmit/
 * resubmit/export), ExamTimetableController (6 methods),
 * TimetableController (6 methods), ResultController
 * (14 methods: index/show/upload/downloadTemplate/approve/
 * reject/release/hide/lock/publish/withdraw/recompute/compute/
 * bulkApprove).
 *
 * Slice 8i-admin-fees (sub-slice 5 of 8i-admin):
 * adds 3 finance-admin controllers — FeeController (6 methods:
 * index/create/store/edit/update/destroy), PaymentTypeController
 * (7 methods: index/create/store/edit/update/destroy/toggle —
 * manages the catalogue of payment types used by the applicant
 * dashboard + admission flow), PaymentFlowController (2 methods:
 * edit/update — the combined admin screen for the admission
 * payment flow, driven from the PaymentType catalogue).
 *
 * Future sub-slices (8i-admin-facilities, 8i-admin-misc) will
 * add their slugs here.
 *
 * Per-controller slug shape (one slug covers all CRUD verbs on a
 * single resource) — mirrors Laravel's ResourceController
 * convention. A future per-verb split is a one-line change per
 * controller.
 *
 * super_admin / admin / cmd pass every gate via wildcard. The
 * `ict_admin` and `staff` roles (currently the route's
 * `role:super_admin,admin,ict_admin,staff` allowlist) also pass
 * every gate via an explicit grant list — these slices do NOT
 * shrink their access; the catalogue rows mirror their current
 * route-level behaviour to avoid a 403 regression.
 *
 * ## Dual-use note (UserUnlockController)
 *
 * `UserUnlockController::showUnlockCode` and `::unlockUser` are
 * reachable from BOTH the public POST-`/unlock/{email}/{code}` flow
 * (guest password-reset via emailed code, see routes/web.php lines
 * 160-161) AND the auth-gated `admin/users/unlock` flow. Those two
 * methods therefore do NOT call `requirePermission()` — gating them
 * would 403 guests at the public endpoints and break the reset
 * flow. The other 4 UserUnlockController methods (showUnlockForm,
 * generateUnlockCode, resetUserPassword, quickUnlock) are
 * auth-admin-only and use the trait gate.
 */
class AdminPermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        // Existing route-allowlist roles — full academic-structure
        // AND user-management access (preserves current behaviour).
        'ict_admin' => [
            // Sub-slice 1: academic structure.
            'admin.schools.manage',
            'admin.departments.manage',
            'admin.programmes.manage',
            'admin.sessions.manage',
            'admin.grading.manage',
            'admin.grades.manage',
            // Sub-slice 2: user management.
            'admin.users.manage',
            'admin.user-roles.manage',
            'admin.user-unlocks.manage',
            // Sub-slice 3: student management.
            'admin.students.manage',
            'admin.student-imports.manage',
            'admin.student-id-cards.manage',
            'admin.admission-centres.manage',
            // Sub-slice 4: academic operations.
            'admin.courses.manage',
            'admin.course-assignments.manage',
            'admin.course-registrations.manage',
            'admin.exam-timetables.manage',
            'admin.timetables.manage',
            'admin.results.manage',
            // Sub-slice 5: fees / payment configuration.
            'admin.fees.manage',
            'admin.payment-types.manage',
            'admin.payment-flows.manage',
        ],
        'staff' => [
            // Sub-slice 1.
            'admin.schools.manage',
            'admin.departments.manage',
            'admin.programmes.manage',
            'admin.sessions.manage',
            'admin.grading.manage',
            'admin.grades.manage',
            // Sub-slice 2.
            'admin.users.manage',
            'admin.user-roles.manage',
            'admin.user-unlocks.manage',
            // Sub-slice 3.
            'admin.students.manage',
            'admin.student-imports.manage',
            'admin.student-id-cards.manage',
            'admin.admission-centres.manage',
            // Sub-slice 4.
            'admin.courses.manage',
            'admin.course-assignments.manage',
            'admin.course-registrations.manage',
            'admin.exam-timetables.manage',
            'admin.timetables.manage',
            'admin.results.manage',
            // Sub-slice 5.
            'admin.fees.manage',
            'admin.payment-types.manage',
            'admin.payment-flows.manage',
        ],
    ];
}