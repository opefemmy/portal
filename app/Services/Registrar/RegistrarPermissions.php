<?php

namespace App\Services\Registrar;

/**
 * Centralised role → permission map for the registrar / admissions
 * module.
 *
 * Covers applicants review, admission-letter generation, settings,
 * and dashboard configuration. Mirrors the shape of
 * `HospitalPermissions::ROLE_PERMISSIONS`.
 */
class RegistrarPermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'registrar' => [
            'registrar.applicants.view',
            'registrar.applicants.create',
            'registrar.applicants.edit',
            'registrar.applicants.review',
            'registrar.applicants.status-update',
            'registrar.applicants.reset-password',
            'registrar.admissions.view',
            'registrar.admissions.generate-letter',
            'registrar.admissions.bulk-upload',
            'registrar.admissions.track',
            'registrar.settings.view',
            'registrar.settings.edit',
            'registrar.reports.view',
            'registrar.reports.export',
            'registrar.dashboard.view',
            'registrar.dashboard.configure',
        ],

        'admission_officer' => [
            'registrar.applicants.view',
            'registrar.applicants.review',
            'registrar.applicants.status-update',
            'registrar.admissions.view',
            'registrar.admissions.generate-letter',
            'registrar.admissions.track',
            'registrar.reports.view',
            'registrar.dashboard.view',
        ],
    ];
}