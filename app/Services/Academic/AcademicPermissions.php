<?php

namespace App\Services\Academic;

/**
 * Centralised role → permission map for the academic module.
 *
 * Covers lecturer/HOD/dean/academic_board responsibilities:
 * courses, results, attendance, timetables, departments and
 * lecturer rosters.
 *
 * Mirrors the shape of `HospitalPermissions::ROLE_PERMISSIONS`.
 *
 * Permission slugs are kept stable so the legacy `roles.permissions`
 * JSON column readers (which seed-pretend these slugs via
 * `ERPRolesSeeder`) don't need to change in this slice. The new
 * `role_permissions` pivot is the authoritative read path; the
 * JSON column continues to mirror the same slugs as a backstop.
 */
class AcademicPermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'lecturer' => [
            'academic.courses.view',
            'academic.courses.assign',
            'academic.courses.teach',
            'academic.results.view',
            'academic.results.enter',
            'academic.results.edit',
            'academic.attendance.view',
            'academic.attendance.mark',
            'academic.attendance.export',
            'academic.timetables.view',
            'academic.dashboard.view',
        ],

        'hod' => [
            'academic.courses.view',
            'academic.courses.assign',
            'academic.courses.create',
            'academic.courses.edit',
            'academic.courses.delete',
            'academic.results.view',
            'academic.results.enter',
            'academic.results.edit',
            'academic.results.approve',
            'academic.attendance.view',
            'academic.attendance.export',
            'academic.timetables.view',
            'academic.timetables.create',
            'academic.timetables.edit',
            'academic.departments.view',
            'academic.lecturers.view',
            'academic.dashboard.view',
            'academic.dashboard.configure',
        ],

        'dean' => [
            'academic.courses.view',
            'academic.results.view',
            'academic.results.approve',
            'academic.results.export',
            'academic.timetables.view',
            'academic.timetables.approve',
            'academic.departments.view',
            'academic.lecturers.view',
            'academic.dashboard.view',
            'academic.dashboard.configure',
        ],

        'academic_board' => [
            'academic.courses.view',
            'academic.results.view',
            'academic.results.export',
            'academic.results.board-approve',
            'academic.timetables.view',
            'academic.departments.view',
            'academic.lecturers.view',
            'academic.dashboard.view',
            'academic.dashboard.configure',
        ],

        // Student — limited to dashboard configuration for the
        // student's own per-user widget configurator. Slice 8i-controller
        // gated StudentDashboardConfigController with
        // `student.dashboard.configure`; without an explicit pivot row
        // the student role has no permissions at all and the gate
        // would always 403. The `admin`/`super_admin`/`cmd` rows above
        // cover `admin.dashboard.configure` via the wildcard — no
        // explicit grant needed for those roles.
        'student' => [
            'student.dashboard.configure',
        ],
    ];
}