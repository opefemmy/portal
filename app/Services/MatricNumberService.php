<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Student;

/**
 * Generates unique matriculation numbers for newly-migrated applicants.
 *
 * Format: `{year}/{dept_prefix}/{4-digit-sequence}`
 *   - `year`         : current calendar year
 *   - `dept_prefix`  : 3-letter uppercase derived from department code (preferred)
 *                      or department name; falls back to "APP"
 *   - `sequence`     : per-(department,year) counter that walks forward until the
 *                      candidate doesn't collide with an existing Student row.
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
        $sequence = Student::query()
            ->where('department_id', $applicant->department_id)
            ->whereYear('created_at', $year)
            ->count() + 1;

        $candidate = sprintf('%d/%s/%04d', $year, $prefix, $sequence);

        // Uniqueness guard: bump the sequence until we find a free number.
        $attempts = 0;
        while (Student::where('matric_number', $candidate)->exists() && $attempts < 50) {
            $sequence++;
            $candidate = sprintf('%d/%s/%04d', $year, $prefix, $sequence);
            $attempts++;
        }

        return $candidate;
    }
}