<?php

namespace App\Services\Applicant;

/**
 * Centralised role → permission map for the applicant module.
 *
 * Covers the applicant-only audience: dashboard, application form +
 * submission, payment gateway + retries, payment validation,
 * auto-login issue, payment receipt reprint.
 *
 * Mirrors the shape of `StudentPermissions::ROLE_PERMISSIONS` and
 * `LibrarianPermissions::ROLE_PERMISSIONS`.
 *
 * Slice 8i-applicant: one slug per controller — the applicant
 * audience is single-tenant (every applicant has access to every
 * applicant endpoint). The per-controller slug gives future
 * flexibility (e.g. a "registrar assistant" role could be granted
 * only `applicant.payments.receipt` without exposing the rest of
 * the portal) without ballooning the catalogue beyond 5 rows.
 *
 * The applicant routes use `auth` middleware (not `role:applicant`),
 * so the trait gate is the slug-level check that protects against
 * non-applicant authenticated users reaching applicant endpoints.
 *
 * Public endpoints (no auth): the 4 dropdown APIs
 * (`getDepartments`, `getProgrammes`, `getLGAs`) and the
 * `checkStatus` / `showValidatePayment` / `validatePayment` (the
 * validatePayment route is public but the controller body calls
 * Auth::user() — gating it would 403 unauthenticated callers, so
 * the gate is left to the auth-required usage path). Public
 * methods on these controllers do NOT call requirePermission().
 */
class ApplicantPermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'applicant' => [
            'applicant.application.manage',
            'applicant.payments.manage',
            'applicant.payments.validate',
            'applicant.payments.receipt',
            'applicant.auto-login.issue',
        ],
    ];
}
