<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Student;
use App\Models\SystemSetting;

/**
 * Generates unique matriculation numbers for newly-migrated applicants.
 *
 * Format: `{institution_code}/{dept_prefix}/{2-digit-year}/{3-digit-sequence}`
 *   - `institution_code` : from system_settings.institution_code, e.g. "EKSCOTECH".
 *                          Falls back to uppercase first 3 letters of institution_name,
 *                          or "APP" if neither is set.
 *   - `dept_prefix`      : 3-letter uppercase derived from department code (preferred)
 *                          or department name; falls back to "APP".
 *   - `year`             : 2-digit current calendar year, e.g. "26".
 *   - `sequence`         : per-(department,year) counter that walks forward until the
 *                          candidate doesn't collide with an existing Student row.
 *
 * Examples:
 *   "EKSCOTECH/COM/26/001"
 *   "EKSCOTECH/CSC/26/001"
 *
 * Keep this the only source of truth — every caller (registrar admit, applicant
 * compulsory-fee migration, registrar bulk upload) routes through here so we
 * never mint the same number twice.
 */
class MatricNumberService
{
    public static function generate(Applicant $applicant): string
    {
        $year = (int) date('Y');
        $yearShort = substr((string) $year, -2);

        $institutionCode = strtoupper(trim((string) SystemSetting::get('institution_code', '')));
        if ($institutionCode === '') {
            $name = (string) SystemSetting::get('institution_name', '');
            // Strip non-alphanumerics, take first 3 letters as fallback.
            $institutionCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name) ?? '');
            $institutionCode = substr($institutionCode, 0, 3) ?: 'APP';
        }
        // Keep the code printable.
        $institutionCode = preg_replace('/[^A-Z0-9]/', '', $institutionCode) ?: 'APP';

        $department = $applicant->department;
        $prefix = $department?->code ?: ($department ? substr($department->name, 0, 3) : 'APP');
        $prefix = preg_replace('/[^A-Z0-9]/i', '', (string) $prefix);
        $prefix = strtoupper($prefix ?: 'APP');
        if (strlen($prefix) > 3) {
            $prefix = substr($prefix, 0, 3);
        }
        if ($prefix === '') {
            $prefix = 'APP';
        }

        // Start from the per-(department,year) sequence count, then walk forward.
        // We match the same (department,year) pair by the 2-digit year segment
        // in the matric so existing rows with the previous 4-digit-year format
        // don't accidentally inflate the counter on the new format.
        $existingThisYear = Student::query()
            ->where('department_id', $applicant->department_id)
            ->where('matric_number', 'LIKE', "%/{$yearShort}/%")
            ->count();
        $sequence = $existingThisYear + 1;

        $candidate = sprintf('%s/%s/%s/%03d', $institutionCode, $prefix, $yearShort, $sequence);

        // Uniqueness guard: bump the sequence until we find a free number.
        $attempts = 0;
        while (Student::where('matric_number', $candidate)->exists() && $attempts < 50) {
            $sequence++;
            $candidate = sprintf('%s/%s/%s/%03d', $institutionCode, $prefix, $yearShort, $sequence);
            $attempts++;
        }

        return $candidate;
    }
}