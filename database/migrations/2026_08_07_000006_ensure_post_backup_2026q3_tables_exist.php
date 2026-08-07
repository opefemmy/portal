<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only safety net for tables that the 2026-07-26 … 2026-08-02
 * CREATE-TABLE migrations were supposed to add but were skipped when
 * the local DB was restored from `database_backup_20260724.sql`.
 *
 * Same drift pattern as 2026_08_07_000005: the restore marked every
 * migration from batches 9/10 as already-applied, so their `CREATE TABLE`
 * statements never ran against the restored DB. Production has them; local
 * does not.
 *
 * All twelve missing tables + the auto_dispense columns on
 * hospital_service_types are guarded by Schema::hasTable / hasColumn so
 * re-running is safe. down() is intentionally a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────
        // 2026_07_27_000001_create_hospital_external_patients_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_external_patients')) {
            Schema::create('hospital_external_patients', function (Blueprint $t) {
                $t->id();
                $t->string('patient_number')->unique();
                $t->string('access_code', 8)->nullable();
                $t->timestamp('access_code_expires_at')->nullable();
                $t->string('password');
                $t->timestamp('last_login_at')->nullable();
                $t->string('first_name');
                $t->string('last_name');
                $t->string('full_name');
                $t->string('email')->nullable();
                $t->string('phone');
                $t->string('gender', 10)->nullable();
                $t->date('date_of_birth')->nullable();
                $t->integer('age')->nullable();
                $t->string('blood_group')->nullable();
                $t->string('genotype')->nullable();
                $t->text('address')->nullable();
                $t->string('emergency_contact_name')->nullable();
                $t->string('emergency_contact_phone')->nullable();
                $t->text('allergies')->nullable();
                $t->text('chronic_conditions')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_07_27_000002_create_hospital_visits_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_visits')) {
            Schema::create('hospital_visits', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_external_patients')->cascadeOnDelete();
                $t->string('visit_number')->unique();
                $t->dateTime('visit_date');
                $t->string('visit_type')->nullable();
                $t->string('department')->nullable();
                $t->unsignedBigInteger('doctor_id')->nullable();
                $t->text('chief_complaint')->nullable();
                $t->text('diagnosis')->nullable();
                $t->text('treatment')->nullable();
                $t->string('status')->default('in_progress');
                $t->date('next_visit_date')->nullable();
                $t->text('next_visit_notes')->nullable();
                $t->decimal('vital_signs_temperature', 4, 1)->nullable();
                $t->string('vital_signs_bp')->nullable();
                $t->integer('vital_signs_pulse')->nullable();
                $t->integer('vital_signs_respiration')->nullable();
                $t->integer('vital_signs_oxygen')->nullable();
                $t->decimal('height', 5, 2)->nullable();
                $t->decimal('weight', 5, 2)->nullable();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_07_27_000003_create_hospital_communications_table
        //   (later re-created by 2026_08_02_000002 with extra columns — we
        //    use the v2 schema from the latter since it supersedes.)
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_communications')) {
            Schema::create('hospital_communications', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_external_patients')->cascadeOnDelete();
                $t->foreignId('visit_id')->nullable()->constrained('hospital_visits')->nullOnDelete();
                $t->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('type', 30)->default('note');
                $t->string('subject');
                $t->text('message');
                $t->boolean('is_read')->default(false);
                $t->timestamp('read_at')->nullable();
                $t->timestamps();
                $t->index(['patient_id', 'is_read']);
                $t->index('type');
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_07_27_000004_create_hospital_lab_orders_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_lab_orders')) {
            Schema::create('hospital_lab_orders', function (Blueprint $t) {
                $t->id();
                $t->foreignId('visit_id')->constrained('hospital_visits')->cascadeOnDelete();
                $t->string('test_name');
                $t->string('test_type')->nullable();
                $t->string('urgency')->default('routine');
                $t->text('result')->nullable();
                $t->dateTime('result_date')->nullable();
                $t->string('status')->default('pending');
                $t->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_07_27_000005_create_hospital_service_types_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_service_types')) {
            Schema::create('hospital_service_types', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->text('description')->nullable();
                $t->string('category')->nullable();
                $t->decimal('amount', 12, 2)->default(0);
                $t->boolean('is_active')->default(true);
                $t->boolean('requires_appointment')->default(false);
                $t->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_07_26_000002_create_hospital_service_requests_table
        //   (depends on hospital_external_patients + hospital_service_types)
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_service_requests')) {
            Schema::create('hospital_service_requests', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_external_patients')->cascadeOnDelete();
                $t->foreignId('service_type_id')->constrained('hospital_service_types')->cascadeOnDelete();
                $t->string('request_code')->unique();
                $t->string('service_name');
                $t->string('category');
                $t->decimal('amount', 12, 2);
                $t->decimal('portal_charge', 12, 2)->default(0);
                $t->decimal('total_amount', 12, 2);
                $t->dateTime('appointment_date')->nullable();
                $t->text('notes')->nullable();
                $t->string('status', 30)->default('pending');
                $t->foreignId('payment_id')->nullable()->constrained('hospital_payments')->nullOnDelete();
                $t->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_08_01_000001_create_previous_results_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('previous_results')) {
            Schema::create('previous_results', function (Blueprint $t) {
                $t->id();
                $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $t->string('course_code', 50);
                $t->string('course_title')->nullable();
                $t->unsignedTinyInteger('units')->default(0);
                $t->string('session_name', 50);
                $t->enum('semester', ['first', 'second'])->default('first');
                $t->unsignedTinyInteger('level')->nullable();
                $t->decimal('ca', 5, 2)->nullable();
                $t->decimal('test', 5, 2)->nullable();
                $t->decimal('assignment', 5, 2)->nullable();
                $t->decimal('exam', 5, 2)->nullable();
                $t->decimal('total_score', 5, 2);
                $t->string('grade', 5)->nullable();
                $t->decimal('grade_point', 3, 1)->nullable();
                $t->text('remarks')->nullable();
                $t->string('source_institution', 255)->nullable();
                $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamp('uploaded_at')->useCurrent();
                $t->timestamps();
                $t->index(['student_id', 'session_name', 'semester', 'level'], 'pr_student_session_semester_idx');
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_08_02_000001_create_hospital_payments_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_payments')) {
            Schema::create('hospital_payments', function (Blueprint $t) {
                $t->id();
                $t->string('payment_ref')->unique();
                $t->string('patient_name');
                $t->string('patient_email')->nullable();
                $t->string('patient_phone');
                $t->string('patient_gender', 20)->nullable();
                $t->unsignedInteger('patient_age')->nullable();
                $t->foreignId('service_type_id')->nullable()->constrained('hospital_service_types')->nullOnDelete();
                $t->string('service_name')->nullable();
                $t->decimal('amount', 12, 2)->default(0);
                $t->decimal('portal_charge', 12, 2)->default(0);
                $t->decimal('total_amount', 12, 2)->default(0);
                $t->string('payment_method', 30)->nullable();
                $t->string('status', 30)->default('pending');
                $t->date('payment_date')->nullable();
                $t->dateTime('appointment_date')->nullable();
                $t->string('doctor_name')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->index(['patient_phone', 'status']);
                $t->index(['patient_email', 'status']);
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_08_02_000005_create_hospital_order_items_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_order_items')) {
            Schema::create('hospital_order_items', function (Blueprint $t) {
                $t->id();
                $t->morphs('orderable');
                $t->foreignId('patient_id')->nullable()->constrained('hospital_patients')->nullOnDelete();
                $t->foreignId('external_patient_id')->nullable()->constrained('hospital_external_patients')->nullOnDelete();
                $t->string('item_name');
                $t->decimal('amount', 12, 2)->default(0);
                $t->string('status', 30)->default('awaiting_payment');
                $t->foreignId('payment_id')->nullable()->constrained('hospital_payments')->nullOnDelete();
                $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
                $t->index('status');
                $t->index(['patient_id', 'status']);
                $t->index(['external_patient_id', 'status']);
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_08_02_000006_create_hospital_audit_trail_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_audit_trail')) {
            Schema::create('hospital_audit_trail', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('user_role', 60)->nullable();
                $t->foreignId('patient_id')->nullable()->constrained('hospital_patients')->nullOnDelete();
                $t->string('action', 80);
                $t->string('subject_type', 120)->nullable();
                $t->unsignedBigInteger('subject_id')->nullable();
                $t->string('ip_address', 45)->nullable();
                $t->string('user_agent', 255)->nullable();
                $t->json('before')->nullable();
                $t->json('after')->nullable();
                $t->json('metadata')->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['patient_id', 'action']);
                $t->index(['user_id', 'created_at']);
                $t->index('action');
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_08_02_000007_create_hospital_clinical_notes_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_clinical_notes')) {
            Schema::create('hospital_clinical_notes', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('staff_id')->nullable()->constrained('hospital_staff')->nullOnDelete();
                $t->unsignedBigInteger('appointment_id')->nullable();
                $t->unsignedBigInteger('medical_record_id')->nullable();
                $t->string('note_type', 30)->default('soap');
                $t->text('subjective')->nullable();
                $t->text('objective')->nullable();
                $t->text('assessment')->nullable();
                $t->text('plan')->nullable();
                $t->text('free_text')->nullable();
                $t->string('signed_by_name')->nullable();
                $t->timestamp('signed_at')->nullable();
                $t->string('signature_hash', 128)->nullable();
                $t->boolean('is_amended')->default(false);
                $t->foreignId('amended_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
                $t->index(['patient_id', 'note_type']);
                $t->index('appointment_id');
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_08_02_000008_create_hospital_duty_roster_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_duty_roster')) {
            Schema::create('hospital_duty_roster', function (Blueprint $t) {
                $t->id();
                $t->foreignId('staff_id')->constrained('hospital_staff')->cascadeOnDelete();
                $t->date('duty_date');
                $t->time('start_time');
                $t->time('end_time');
                $t->string('shift', 30)->default('morning');
                $t->string('location', 120)->nullable();
                $t->text('notes')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->unique(['staff_id', 'duty_date', 'shift']);
                $t->index(['duty_date', 'shift']);
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_08_02_000003_add_auto_dispense_columns_to_hospital_service_types
        // (column adds on the table we just created above.)
        // ─────────────────────────────────────────────────────────────────
        if (Schema::hasTable('hospital_service_types')
            && ! Schema::hasColumn('hospital_service_types', 'auto_dispense_drug_id')) {
            Schema::table('hospital_service_types', function (Blueprint $t) {
                $t->foreignId('auto_dispense_drug_id')
                    ->nullable()
                    ->after('requires_appointment')
                    ->constrained('hospital_drugs')
                    ->nullOnDelete();
                $t->unsignedInteger('auto_dispense_quantity')
                    ->nullable()
                    ->after('auto_dispense_drug_id')
                    ->comment('How many units of the drug to dispense on payment completion');
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2026_08_02_000004_make_password_nullable_in_hospital_external_patients
        // ─────────────────────────────────────────────────────────────────
        if (Schema::hasTable('hospital_external_patients')) {
            $col = DB::selectOne("
                SELECT IS_NULLABLE AS nullable
                  FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'hospital_external_patients'
                   AND COLUMN_NAME = 'password'
            ");
            if ($col && strtoupper($col->nullable) === 'NO') {
                DB::statement('ALTER TABLE hospital_external_patients MODIFY password VARCHAR(255) NULL');
            }
        }
    }

    public function down(): void
    {
        // No-op. Production keeps these tables.
    }
};
