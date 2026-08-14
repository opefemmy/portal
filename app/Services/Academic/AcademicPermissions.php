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

        // The student role's grants were moved to StudentPermissions
        // in slice 8i-student. Keeping the placeholder row here would
        // re-introduce the student role into AcademicPermissions'
        // STUDENT row — the seeder unions per role slug, so this
        // change is additive (no role loses a grant).
    ];
}