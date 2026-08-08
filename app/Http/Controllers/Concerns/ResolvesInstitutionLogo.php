<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SystemSetting;

/**
 * Single source of truth for resolving the institution logo URL.
 *
 * The institution logo lives in two possible places:
 *   1. The public disk at `public/images/logo.png` — a fallback checked
 *      into git so the receipt always has something to draw even on a
 *      fresh install.
 *   2. The uploaded file at `storage/app/public/branding/logo_<...>.<ext>`
 *      — admins can upload via the system settings page; the relative
 *      path is stored in `system_settings.institution_logo`.
 *
 * This mirrors the resolution logic in
 * `resources/views/applicant/admission-letter.blade.php:40-50` and
 * `resources/views/layouts/app.blade.php:460-462`. Keep all three in
 * sync if the policy ever changes — the receipt partial is a fourth
 * caller.
 */
trait ResolvesInstitutionLogo
{
    /**
     * Resolve the institution logo URL, preferring the public fallback
     * file over the admin-uploaded one so admins can override without
     * losing the default.
     */
    protected function resolveInstitutionLogoUrl(): ?string
    {
        $publicLogo = public_path('images/logo.png');
        if (file_exists($publicLogo)) {
            return asset('images/logo.png') . '?v=' . time();
        }

        $stored = SystemSetting::getInstitutionLogo();
        if ($stored && file_exists(storage_path('app/public/' . $stored))) {
            return asset('storage/' . $stored);
        }

        return null;
    }

    /**
     * Compute the polymorphic "Fee Type" label for a Payment row.
     * Order matches the existing inline logic in
     * `resources/views/student/payment-receipt.blade.php:78-88` so we
     * don't change the user-visible string on the receipt.
     */
    protected function resolveFeeTypeLabel(\App\Models\Payment $payment): string
    {
        return $payment->fee?->name
            ?? $payment->paymentType?->display_label
            ?? $payment->paymentType?->name
            ?? $payment->payment_purpose
            ?? 'N/A';
    }

    /**
     * The matric number on a student-side Payment row (or null when
     * the payment is applicant-side). Centralised so the watermark
     * and the table cell use the same fallback chain.
     */
    protected function resolvePayerMatric(\App\Models\Payment $payment): ?string
    {
        return $payment->student?->matric_number;
    }
}
