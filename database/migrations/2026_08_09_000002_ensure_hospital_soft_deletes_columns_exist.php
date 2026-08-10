<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only safety net for soft-delete columns on hospital tables.
 *
 * Same root cause as the 2026_08_09_000001 / 2026_08_07_000004 safety
 * nets: when the local DB was restored from `database_backup_20260724.sql`
 * the 2024_07_07_000001 + 2024_07_07_000003 migrations were marked as
 * already-applied (so the ALTER was skipped) but the backup itself
 * predates those migrations, so the `deleted_at` columns never landed.
 *
 * Symptom: `HospitalPatient` model uses the SoftDeletes trait and emits
 * `where deleted_at is null` on every read. Without the column the SELECT
 * 500s with `Unknown column 'hospital_patients.deleted_at' in 'where
 * clause'`.
 *
 * Production already has every column below (no-op there).
 *
 * Mirrors the existing defensive patterns: per-column Schema::hasColumn
 * guards, down() left as a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 2024_07_07_000001_add_soft_deletes_to_hospital_patients
        if (Schema::hasTable('hospital_patients')
            && ! Schema::hasColumn('hospital_patients', 'deleted_at')) {
            Schema::table('hospital_patients', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        // 2024_07_07_000003_add_soft_deletes_to_hospital_tables — same
        // gap on the rest of the hospital module. Apply the same
        // guarded ALTER per table.
        $hospitalTables = [
            'hospital_wards',
            'hospital_staff',
            'hospital_beds',
            'hospital_appointments',
            'hospital_vital_signs',
            'hospital_medical_records',
            'hospital_diagnoses',
            'hospital_prescriptions',
            'hospital_prescription_items',
            'hospital_lab_requests',
            'hospital_lab_results',
            'hospital_admissions',
            'hospital_referrals',
            'hospital_reports',
            'hospital_drugs',
            'hospital_drug_categories',
            'hospital_drug_batches',
            'hospital_suppliers',
            'hospital_store_items',
            'hospital_store_batches',
            'hospital_purchases',
        ];

        foreach ($hospitalTables as $table) {
            if (Schema::hasTable($table)
                && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        // No-op. Production keeps these columns; rolling them back
        // would break every SoftDeletes-using model read.
    }
};
