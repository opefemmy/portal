<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only safety net for columns that the post-backup ALTER
 * migrations were supposed to add but were skipped when the local DB
 * was restored from `database_backup_20260724.sql`.
 *
 * During the restore the 28 batch-2 migrations (2024_06_16 … 2024_07_08)
 * were marked as already-applied so re-creating tables that the backup
 * already contained would be skipped. But the backup predates these
 * 2024_07_07_* migrations, so the new columns they add never actually
 * landed — they came in via the migration record, not via an ALTER.
 *
 * Production already has every column below (so this is a no-op there).
 * Locally it tops up anything missing without affecting production.
 *
 * Mirrors the existing 2026_08_07_000002 defensive pattern: per-column
 * Schema::hasColumn guards, down() left as a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 2024_06_17_000003_add_active_columns
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $t) {
                if (! Schema::hasColumn('schools', 'is_active')) $t->boolean('is_active')->default(true);
            });
        }
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $t) {
                if (! Schema::hasColumn('departments', 'is_active')) $t->boolean('is_active')->default(true);
            });
        }

        // 2024_07_06_000005_add_classification_to_grades
        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $t) {
                if (! Schema::hasColumn('grades', 'classification')) $t->string('classification')->nullable();
                if (! Schema::hasColumn('grades', 'gpa_weight')) $t->integer('gpa_weight')->default(1);
            });
        }
        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $t) {
                if (! Schema::hasColumn('sessions', 'use_classification')) $t->boolean('use_classification')->default(false);
            });
        }

        // 2024_07_07_000006_add_year_of_entry_to_students
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $t) {
                if (! Schema::hasColumn('students', 'year_of_entry')) $t->integer('year_of_entry')->nullable();
            });
        }

        // 2024_07_07_000007_add_student_security_fields
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $t) {
                if (! Schema::hasColumn('users', 'password_changed_at')) $t->timestamp('password_changed_at')->nullable();
                if (! Schema::hasColumn('users', 'security_question')) $t->string('security_question')->nullable();
                if (! Schema::hasColumn('users', 'security_answer')) $t->string('security_answer')->nullable();
                if (! Schema::hasColumn('users', 'must_change_password')) $t->boolean('must_change_password')->default(false);
                if (! Schema::hasColumn('users', 'guidance_name')) $t->string('guidance_name')->nullable();
                if (! Schema::hasColumn('users', 'guidance_phone')) $t->string('guidance_phone')->nullable();
                if (! Schema::hasColumn('users', 'guidance_address')) $t->text('guidance_address')->nullable();
            });
        }

        // 2024_07_07_000008_add_library_fee_and_penalty
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $t) {
                if (! Schema::hasColumn('students', 'library_fee_paid')) $t->boolean('library_fee_paid')->default(false);
                if (! Schema::hasColumn('students', 'library_fee_paid_at')) $t->timestamp('library_fee_paid_at')->nullable();
            });
        }
        if (Schema::hasTable('book_loans')) {
            Schema::table('book_loans', function (Blueprint $t) {
                if (! Schema::hasColumn('book_loans', 'late_fee')) $t->decimal('late_fee', 10, 2)->default(0);
                if (! Schema::hasColumn('book_loans', 'late_fee_paid')) $t->boolean('late_fee_paid')->default(false);
                if (! Schema::hasColumn('book_loans', 'late_fee_paid_at')) $t->timestamp('late_fee_paid_at')->nullable();
                if (! Schema::hasColumn('book_loans', 'penalty_days')) $t->integer('penalty_days')->default(0);
            });
        }
        if (Schema::hasTable('books')) {
            Schema::table('books', function (Blueprint $t) {
                if (! Schema::hasColumn('books', 'late_fee_per_day')) $t->decimal('late_fee_per_day', 10, 2)->default(100);
                if (! Schema::hasColumn('books', 'max_borrow_days')) $t->integer('max_borrow_days')->default(14);
            });
        }

        // 2024_07_07_000012_enhance_regime_payments (now that regime_payments
        // already exists post-backup; columns are FKs to populated tables)
        if (Schema::hasTable('regime_payments')) {
            Schema::table('regime_payments', function (Blueprint $t) {
                if (! Schema::hasColumn('regime_payments', 'payment_type')) $t->string('payment_type')->default('school_fee');
                if (! Schema::hasColumn('regime_payments', 'school_id')) $t->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
                if (! Schema::hasColumn('regime_payments', 'department_id')) $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                if (! Schema::hasColumn('regime_payments', 'programme_id')) $t->foreignId('programme_id')->nullable()->constrained('programmes')->nullOnDelete();
                if (! Schema::hasColumn('regime_payments', 'session_id')) $t->foreignId('session_id')->nullable()->constrained('sessions')->nullOnDelete();
                if (! Schema::hasColumn('regime_payments', 'semester')) $t->string('semester')->nullable();
                if (! Schema::hasColumn('regime_payments', 'level')) $t->integer('level')->nullable();
                if (! Schema::hasColumn('regime_payments', 'level_operator')) $t->string('level_operator')->default('exact');
                if (! Schema::hasColumn('regime_payments', 'portal_charge')) $t->decimal('portal_charge', 10, 2)->default(0);
                if (! Schema::hasColumn('regime_payments', 'include_portal_charge')) $t->boolean('include_portal_charge')->default(false);
                if (! Schema::hasColumn('regime_payments', 'payment_config')) $t->string('payment_config')->default('full');
            });
        }

        // 2024_07_07_100003_add_fee_type_to_payments_table
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $t) {
                if (! Schema::hasColumn('payments', 'fee_type')) $t->string('fee_type', 30)->default('other');
            });
        }

        // 2024_07_07_100004_add_payment_fields_to_applicants_table
        // (payment_status, payment_ref, payment_transaction_id, payment_amount,
        //  payment_date already present from backup; only application_fee_id is
        //  missing.)
        if (Schema::hasTable('applicants')) {
            Schema::table('applicants', function (Blueprint $t) {
                if (! Schema::hasColumn('applicants', 'application_fee_id')) $t->string('application_fee_id')->nullable();
            });
        }

        // 2024_07_07_200002_enhance_results_table
        if (Schema::hasTable('results')) {
            Schema::table('results', function (Blueprint $t) {
                if (! Schema::hasColumn('results', 'quality_point')) $t->decimal('quality_point', 10, 2)->nullable();
                if (! Schema::hasColumn('results', 'pass_status')) $t->string('pass_status', 20)->nullable();
                if (! Schema::hasColumn('results', 'academic_remark')) $t->string('academic_remark', 50)->nullable();
                if (! Schema::hasColumn('results', 'carry_over_status')) $t->string('carry_over_status', 20)->nullable();
                if (! Schema::hasColumn('results', 'is_repeated')) $t->boolean('is_repeated')->default(false);
                if (! Schema::hasColumn('results', 'attempt_number')) $t->integer('attempt_number')->default(1);
                if (! Schema::hasColumn('results', 'computation_notes')) $t->text('computation_notes')->nullable();
                if (! Schema::hasColumn('results', 'semester_id')) $t->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            });
        }

        // 2024_07_07_200001_create_semesters_table
        // (semesters table exists from backup; only is_active is missing)
        if (Schema::hasTable('semesters')) {
            Schema::table('semesters', function (Blueprint $t) {
                if (! Schema::hasColumn('semesters', 'is_active')) $t->boolean('is_active')->default(true);
            });
        }
        // Same migration also adds semester_id FK to student_courses.
        if (Schema::hasTable('student_courses')) {
            Schema::table('student_courses', function (Blueprint $t) {
                if (! Schema::hasColumn('student_courses', 'semester_id')) $t->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            });
        }

        // 2024_07_07_200003_create_levels_table
        // (levels table exists from backup; only is_active is missing)
        if (Schema::hasTable('levels')) {
            Schema::table('levels', function (Blueprint $t) {
                if (! Schema::hasColumn('levels', 'is_active')) $t->boolean('is_active')->default(true);
            });
        }
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $t) {
                if (! Schema::hasColumn('students', 'level_id')) $t->foreignId('level_id')->nullable()->constrained('levels')->nullOnDelete();
                if (! Schema::hasColumn('students', 'academic_status')) $t->string('academic_status', 30)->nullable();
            });
        }

        // 2024_07_07_100001_add_department_to_programmes — the original
        // migration was guarded by FK existence; we add the FK here too.
        if (Schema::hasTable('programmes')) {
            Schema::table('programmes', function (Blueprint $t) {
                if (! Schema::hasColumn('programmes', 'department_id')) $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // No-op. Production keeps these columns; rolling them back would
        // break every controller insert that lists them in $fillable.
    }
};
