<?php

namespace App\Services\Student;

/**
 * Centralised role → permission map for the student module.
 *
 * Covers the student-only audience: dashboard, timetable, attendance,
 * course registration, results, profile, payments, complaints, exam
 * clearance, admission letter reprint, hostel, library, password
 * change, security setup.
 *
 * Mirrors the shape of `AcademicPermissions::ROLE_PERMISSIONS` and
 * `LibrarianPermissions::ROLE_PERMISSIONS`.
 *
 * Slice 8i-student: one slug per controller, because the student
 * audience is single-tenant — every student has access to every
 * student endpoint. The per-controller slug gives future flexibility
 * (e.g. a "student ambassador" role could be granted only
 * `student.complaints.manage` without exposing the rest of the
 * portal) without ballooning the catalogue beyond 14 rows.
 *
 * The umbrella doesn't currently grant per-verb granularity (e.g.
 * view vs pay is collapsed into `student.payments.manage`); the
 * trait gate fires at the top of every method, so splitting later
 * is a one-constant change.
 */
class StudentPermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        // Existing slug from slice 8i-controller — kept here so the
        // student role's grants are consolidated in one place. The
        // AcademicPermissions::ROLE_PERMISSIONS still carries the same
        // slug for backwards compatibility with the seeder's union
        // behaviour (idempotent).
        'student' => [
            'student.dashboard.configure',
            'student.dashboard.view',
            'student.timetables.view',
            'student.attendance.view',
            'student.courses.manage',
            'student.results.view',
            'student.profile.manage',
            'student.payments.manage',
            'student.complaints.manage',
            'student.exam-clearance.view',
            'student.admission-letter.view',
            'student.hostel.manage',
            'student.library.manage',
            'student.password.change',
            'student.security.setup',
        ],
    ];
}
