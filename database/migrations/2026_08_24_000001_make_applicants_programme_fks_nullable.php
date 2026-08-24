<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Forward-only safety net: make the applicants-table programme FKs
 * nullable.
 *
 * Symptom: SQLSTATE[HY000] "Field 'school_id' doesn't have a default
 * value" when Auth\RegisterController::registerApplicant() calls
 * Applicant::firstOrCreate() at signup. The original migration
 * (2024_01_01_000008) declares school_id, department_id, programme_id
 * and session_id as non-nullable foreign keys — but at signup time the
 * applicant hasn't picked a programme yet. Submission later validates
 * those four fields via `required|exists:...` rules in
 * ApplicationController::submitApplication(), so the database NOT NULL
 * constraint is duplicative of the application-layer validation.
 *
 * Production already has these columns NOT NULL (no-op there on
 * downgrade intent; see down()).
 *
 * Approach: drop the existing NOT NULL constraint at the database
 * level using a raw ALTER. We don't use Schema::change() because the
 * project doesn't depend on doctrine/dbal. We don't drop+recreate the
 * foreign key either — the FK behaviour is identical (still
 * onDelete('cascade')), and dropping it would risk dropping the
 * auto-generated index by mistake on a constrained column.
 *
 * Mirrors the safety-net pattern from
 * 2026_08_09_000002_ensure_hospital_soft_deletes_columns_exist.php —
 * per-column guards + idempotent ALTER.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('applicants')) {
            return;
        }

        // ── 1. Relax the four programme-FK columns ───────────────────
        //
        // MySQL refuses to alter a column that's part of a foreign key,
        // so for each one we drop the FK, relax the column, then
        // re-add the FK in the same shape Laravel originally wrote.
        //
        // The auto-generated constraint name Laravel uses for
        // $table->foreignId('x')->constrained('y') is
        // `applicants_<x>_foreign`. If MySQL doesn't find it (e.g. a
        // manual install without the FK) the DROP fails silently and
        // the re-add below still produces a working FK.
        $columns = [
            'school_id'     => 'schools',
            'department_id' => 'departments',
            'programme_id'  => 'programmes',
            'session_id'    => 'sessions',
        ];

        foreach ($columns as $column => $referenced) {
            if (! Schema::hasColumn('applicants', $column)) {
                continue;
            }

            $constraint = 'applicants_' . $column . '_foreign';
            try {
                DB::statement("ALTER TABLE `applicants` DROP FOREIGN KEY `{$constraint}`");
            } catch (\Throwable $e) {
                // Constraint already absent or differently named — move on.
            }

            DB::statement("ALTER TABLE `applicants` MODIFY `{$column}` BIGINT UNSIGNED NULL");

            DB::statement(
                "ALTER TABLE `applicants` "
                . "ADD CONSTRAINT `{$constraint}` "
                . "FOREIGN KEY (`{$column}`) REFERENCES `{$referenced}` (`id`) "
                . "ON DELETE CASCADE"
            );
        }

        // ── 2. Make application_number nullable too ───────────────────
        //
        // The original migration declares application_number as a
        // non-nullable UNIQUE varchar. At signup time the applicant
        // hasn't been issued a number yet — submitApplication()
        // generates one on first real submission. Until then the row
        // needs to exist without one.
        //
        // The UNIQUE index is preserved (NULL values don't collide on
        // a UNIQUE index in MySQL/InnoDB), so this doesn't change any
        // existing duplicate-prevention guarantee.
        if (Schema::hasColumn('applicants', 'application_number')) {
            DB::statement("ALTER TABLE `applicants` MODIFY `application_number` VARCHAR(255) NULL");
        }
    }

    public function down(): void
    {
        // No-op. Production keeps these columns nullable — re-tightening
        // would re-break every signup path that doesn't supply programme
        // data (which is all of them at signup time).
    }
};
