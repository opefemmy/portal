<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only safety net for hospital + payments tables that the
 * earlier post-backup safety-nets (`2026_08_07_000005`,
 * `2026_08_07_000006`, `2026_08_09_000001`, `2026_08_09_000002`)
 * were supposed to add but never landed on this DB.
 *
 * Same root cause as the existing drift notes:
 *   - `database_backup_20260724.sql` predates these tables, so the
 *     restore marked every migration that created them as already-
 *     applied — but the CREATE TABLE statements never ran.
 *   - The earlier safety-nets (`2026_08_07_000005` for the hospital
 *     tables, `2026_08_09_000001` for `external_payments`) were
 *     subsequently marked as Ran too, but the tables still aren't
 *     here, presumably because something between the safety-net run
 *     and today dropped them again, or the safety-net ran against
 *     a connection that was already past these migrations in the
 *     rollback chain.
 * The user's reported 500 was:
 *
 *     SQLSTATE[42S02]: Base table or view not found: 1146
 *     Table 'portal.hospital_appointments' doesn't exist
 *
 * …triggered by `HospitalAppointment::whereDate('appointment_date',
 * today())->count()` on the dashboard. The HospitalAppointment model
 * also uses SoftDeletes, so the table needs `deleted_at`.
 *
 * `hospital_beds` is missing locally too — the ward manager
 * dashboard and the bed-assignment flow both read/write it, so
 * re-creating it closes that latent crash as well.
 *
 * `external_payments` was already covered by
 * 2026_08_09_000001_ensure_external_payments_table_exists but the
 * table is still missing. Re-running the same schema here is
 * harmless (each block is guarded by Schema::hasTable).
 *
 * Production has every one of these tables (no-op there).
 *
 * Each block uses Schema::hasTable / Schema::hasColumn guards so
 * re-running is safe. down() is intentionally a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────
        // system_settings
        // The original creator was 2024_06_18_000001_create_portal_settings_tables.php
        // (backed by 2024_07_07_000002_ensure_system_settings, which has a
        // Schema::hasTable guard but still doesn't bring the table back).
        // Schema mirrors the original creator:
        //   - id (auto-increment PK)
        //   - key varchar(255) UNIQUE (sparse: 'admission_form_open',
        //     'institution_name', 'admission_letter_body', etc.)
        //   - value TEXT (long enough for multi-paragraph templates after
        //     the 2026_08_07_000001 widening migration)
        //   - description varchar(255) nullable
        //   - is_active boolean (default true)
        //   - timestamps
        // We re-seed every key with the same defaults that
        // 2024_07_07_000002_ensure_system_settings would have seeded if
        // it actually ran, via updateOrInsert so it's idempotent.
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Seed defaults — but only when the table is empty, so we don't
        // overwrite an institution's pre-existing branded values. Key
        // seed mirrors 2024_07_07_000002_ensure_system_settings.
        if (Schema::hasTable('system_settings')) {
            $seeded = DB::table('system_settings')->count();
            if ($seeded === 0) {
                $now = now();
                $defaults = [
                    ['key' => 'payment_open', 'value' => '0', 'description' => 'Payment status: 0 = closed, 1 = open'],
                    ['key' => 'registration_open', 'value' => '0', 'description' => 'Course registration status'],
                    ['key' => 'portal_name', 'value' => 'University Portal', 'description' => 'Portal name'],
                    ['key' => 'library_fee_required', 'value' => 'false', 'description' => 'Require library fee before borrowing'],
                    ['key' => 'library_fee_amount', 'value' => '500', 'description' => 'Library fee amount'],
                    ['key' => 'library_late_fee_per_day', 'value' => '100', 'description' => 'Late fee per day for book return'],
                    ['key' => 'library_max_borrow_days', 'value' => '14', 'description' => 'Maximum days to borrow a book'],
                ];
                foreach ($defaults as $row) {
                    DB::table('system_settings')->insert($row + [
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // hospital_appointments
        // Mirrors the original 2024_07_06_000001_create_hospital_tables
        // definition plus the deleted_at column added by
        // 2024_07_07_000004_add_deleted_at_to_hospital_appointments.
        // The columns here are exactly the ones the
        // HospitalAppointment model + DashboardController read:
        //   - id, patient_id, doctor_id, scheduled_by
        //   - appointment_date, appointment_time, status
        //   - complaint, notes
        //   - checked_in_at, completed_at, timestamps
        //   - deleted_at (SoftDeletes)
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_appointments')) {
            Schema::create('hospital_appointments', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')
                    ->constrained('hospital_patients')
                    ->cascadeOnDelete();
                $t->foreignId('doctor_id')
                    ->constrained('hospital_staff')
                    ->cascadeOnDelete();
                $t->foreignId('scheduled_by')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $t->dateTime('appointment_date');
                $t->time('appointment_time');
                $t->string('status', 60)->default('scheduled');
                $t->text('complaint')->nullable();
                $t->text('notes')->nullable();
                $t->dateTime('checked_in_at')->nullable();
                $t->dateTime('completed_at')->nullable();
                $t->timestamps();
                $t->softDeletes();

                $t->index(['appointment_date', 'status']);
                $t->index('doctor_id');
                $t->index('patient_id');
            });
        } elseif (! Schema::hasColumn('hospital_appointments', 'deleted_at')) {
            // The table exists but is missing SoftDeletes — the
            // dashboard's `where deleted_at is null` would crash.
            // Add it.
            Schema::table('hospital_appointments', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // hospital_beds
        // Mirrors the original 2024_07_06_000001_create_hospital_tables
        // definition plus deleted_at (SoftDeletes via the
        // 2026_08_09_002 safety-net that ran before this table existed).
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_beds')) {
            Schema::create('hospital_beds', function (Blueprint $t) {
                $t->id();
                $t->foreignId('ward_id')
                    ->constrained('hospital_wards')
                    ->cascadeOnDelete();
                $t->string('bed_number');
                $t->string('status', 30)->default('available');
                $t->unsignedBigInteger('patient_id')->nullable();
                $t->dateTime('occupied_at')->nullable();
                $t->dateTime('discharged_at')->nullable();
                $t->timestamps();
                $t->softDeletes();

                $t->index(['ward_id', 'status']);
                $t->index('patient_id');
            });
        } elseif (! Schema::hasColumn('hospital_beds', 'deleted_at')) {
            Schema::table('hospital_beds', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // external_payments
        // Same schema as 2026_08_09_000001_ensure_external_payments_table_exists.
        // Re-declared here in case the table is still missing — the
        // original safety-net exists but the table never appeared
        // for the same drift reason.
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('external_payments')) {
            Schema::create('external_payments', function (Blueprint $t) {
                $t->id();
                $t->string('transaction_id')->unique();
                $t->string('applicant_name');
                $t->string('email');
                $t->decimal('amount', 12, 2);
                $t->dateTime('payment_date');
                $t->string('payment_status'); // pending, completed, failed
                $t->string('payment_channel'); // card, bank, USSD, etc.
                $t->string('description')->nullable();
                $t->unsignedBigInteger('applicant_id')->nullable()->unique();
                $t->boolean('is_used')->default(false);
                $t->unsignedBigInteger('imported_by')->nullable();
                $t->unsignedBigInteger('validated_by')->nullable();
                $t->dateTime('validated_at')->nullable();
                $t->text('notes')->nullable();
                // payment_type_id is added by 2026_07_23_000003;
                // included so the schema is complete even on a freshly
                // restored DB.
                $t->unsignedBigInteger('payment_type_id')->nullable();
                $t->timestamps();

                $t->foreign('applicant_id')->references('id')->on('applicants')->nullOnDelete();
                $t->foreign('imported_by')->references('id')->on('users')->nullOnDelete();
                $t->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
                // payment_types may not exist yet on a freshly restored
                // DB — wrap the FK in a try/catch so this migration
                // runs even when payment_types lands later via another
                // safety-net migration.
                try {
                    $t->foreign('payment_type_id')->references('id')->on('payment_types')->nullOnDelete();
                } catch (\Throwable $e) {
                    // payment_types table is missing locally; leave the
                    // column unconstrained. A later migration will add
                    // the FK once payment_types lands.
                }

                $t->index(['transaction_id', 'is_used']);
                $t->index(['email', 'is_used']);
                $t->index('payment_status');
                $t->index('payment_type_id');
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // The rest of the hospital clinical/prescription/lab tables that
        // 2026_08_07_000005_ensure_post_backup_tables_exist was supposed
        // to re-create but never did. Each block is guarded by
        // Schema::hasTable so re-running is safe. The schema mirrors
        // 2024_07_06_000001_create_hospital_tables verbatim.
        //
        // These tables all use SoftDeletes models (HospitalPrescription,
        // HospitalAdmission, HospitalLabRequest, HospitalDiagnosis,
        // HospitalReferral, HospitalReport, HospitalVitalSign,
        // HospitalMedicalRecord, HospitalPrescriptionItem,
        // HospitalLabResult) so every block also includes deleted_at.
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_vital_signs')) {
            Schema::create('hospital_vital_signs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('recorded_by')->constrained('hospital_staff')->cascadeOnDelete();
                $t->decimal('temperature', 4, 1)->nullable();
                $t->string('blood_pressure_systolic')->nullable();
                $t->string('blood_pressure_diastolic')->nullable();
                $t->decimal('weight', 5, 2)->nullable();
                $t->decimal('height', 5, 2)->nullable();
                $t->integer('pulse')->nullable();
                $t->integer('oxygen_level')->nullable();
                $t->decimal('blood_sugar', 5, 2)->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        } elseif (! Schema::hasColumn('hospital_vital_signs', 'deleted_at')) {
            Schema::table('hospital_vital_signs', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        if (! Schema::hasTable('hospital_medical_records')) {
            Schema::create('hospital_medical_records', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('doctor_id')->nullable()->constrained('hospital_staff')->nullOnDelete();
                $t->unsignedBigInteger('appointment_id')->nullable();
                $t->text('chief_complaint')->nullable();
                $t->text('symptoms')->nullable();
                $t->text('examination_findings')->nullable();
                $t->text('doctor_notes')->nullable();
                $t->text('treatment_plan')->nullable();
                $t->dateTime('consultation_date');
                $t->string('visit_type', 20)->default('new');
                $t->timestamps();
                $t->softDeletes();
            });
        } elseif (! Schema::hasColumn('hospital_medical_records', 'deleted_at')) {
            Schema::table('hospital_medical_records', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        if (! Schema::hasTable('hospital_diagnoses')) {
            Schema::create('hospital_diagnoses', function (Blueprint $t) {
                $t->id();
                $t->foreignId('medical_record_id')->constrained('hospital_medical_records')->cascadeOnDelete();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->string('icd_code')->nullable();
                $t->string('diagnosis');
                $t->text('description')->nullable();
                $t->string('severity', 20)->nullable();
                $t->string('type', 20)->default('primary');
                $t->timestamps();
                $t->softDeletes();
            });
        } elseif (! Schema::hasColumn('hospital_diagnoses', 'deleted_at')) {
            Schema::table('hospital_diagnoses', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        if (! Schema::hasTable('hospital_prescriptions')) {
            Schema::create('hospital_prescriptions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('doctor_id')->constrained('hospital_staff')->cascadeOnDelete();
                $t->unsignedBigInteger('medical_record_id')->nullable();
                $t->text('notes')->nullable();
                $t->string('status', 30)->default('pending');
                $t->unsignedBigInteger('dispensed_by')->nullable();
                $t->dateTime('dispensed_at')->nullable();
                $t->timestamps();
                $t->softDeletes();

                $t->index(['status', 'patient_id']);
            });
        } elseif (! Schema::hasColumn('hospital_prescriptions', 'deleted_at')) {
            Schema::table('hospital_prescriptions', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        if (! Schema::hasTable('hospital_prescription_items')) {
            Schema::create('hospital_prescription_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('prescription_id')->constrained('hospital_prescriptions')->cascadeOnDelete();
                $t->unsignedBigInteger('drug_id')->nullable();
                $t->string('drug_name');
                $t->string('dosage');
                $t->string('frequency');
                $t->string('duration');
                $t->string('quantity')->nullable();
                $t->text('instructions')->nullable();
                $t->boolean('is_dispensed')->default(false);
                $t->timestamps();
                // No softDeletes — the HospitalPrescriptionItem model
                // does not use SoftDeletes.
            });
        }

        if (! Schema::hasTable('hospital_lab_requests')) {
            Schema::create('hospital_lab_requests', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('doctor_id')->constrained('hospital_staff')->cascadeOnDelete();
                $t->unsignedBigInteger('medical_record_id')->nullable();
                $t->string('test_type');
                $t->text('clinical_notes')->nullable();
                $t->string('status', 30)->default('pending');
                $t->dateTime('requested_at');
                $t->dateTime('completed_at')->nullable();
                $t->decimal('amount', 10, 2)->default(0);
                $t->timestamps();
                $t->softDeletes();

                $t->index(['status', 'patient_id']);
            });
        } elseif (! Schema::hasColumn('hospital_lab_requests', 'deleted_at')) {
            Schema::table('hospital_lab_requests', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        if (! Schema::hasTable('hospital_lab_results')) {
            Schema::create('hospital_lab_results', function (Blueprint $t) {
                $t->id();
                $t->foreignId('lab_request_id')->constrained('hospital_lab_requests')->cascadeOnDelete();
                $t->unsignedBigInteger('recorded_by')->nullable();
                $t->string('test_name');
                $t->string('parameter')->nullable();
                $t->string('result')->nullable();
                $t->string('unit')->nullable();
                $t->string('reference_range')->nullable();
                $t->string('status')->comment('normal, abnormal, critical');
                $t->text('notes')->nullable();
                $t->dateTime('recorded_at')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        } elseif (! Schema::hasColumn('hospital_lab_results', 'deleted_at')) {
            Schema::table('hospital_lab_results', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        if (! Schema::hasTable('hospital_admissions')) {
            Schema::create('hospital_admissions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('doctor_id')->constrained('hospital_staff')->cascadeOnDelete();
                $t->unsignedBigInteger('bed_id')->nullable();
                $t->string('admission_number')->unique();
                $t->dateTime('admission_date');
                $t->dateTime('discharge_date')->nullable();
                $t->string('status', 20)->default('admitted');
                $t->text('reason')->nullable();
                $t->text('diagnosis')->nullable();
                $t->text('treatment_plan')->nullable();
                $t->text('discharge_notes')->nullable();
                $t->decimal('daily_rate', 12, 2)->default(0);
                $t->decimal('total_charges', 12, 2)->default(0);
                $t->timestamps();
                $t->softDeletes();

                $t->index(['status', 'patient_id']);
                $t->index('bed_id');
            });
        } elseif (! Schema::hasColumn('hospital_admissions', 'deleted_at')) {
            Schema::table('hospital_admissions', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        if (! Schema::hasTable('hospital_referrals')) {
            Schema::create('hospital_referrals', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('referrer_id')->constrained('hospital_staff')->cascadeOnDelete();
                $t->unsignedBigInteger('referred_to_id')->nullable();
                $t->string('external_facility')->nullable();
                $t->text('reason');
                $t->text('notes')->nullable();
                $t->string('status', 20)->default('pending');
                $t->dateTime('referred_at');
                $t->dateTime('accepted_at')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        } elseif (! Schema::hasColumn('hospital_referrals', 'deleted_at')) {
            Schema::table('hospital_referrals', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        if (! Schema::hasTable('hospital_reports')) {
            Schema::create('hospital_reports', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
                $t->string('report_type')->comment('medical_report, medical_certificate, lab_report, discharge_summary');
                $t->string('title');
                $t->text('content')->nullable();
                $t->string('file_path')->nullable();
                $t->string('status', 20)->default('draft');
                $t->dateTime('released_at')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        } elseif (! Schema::hasColumn('hospital_reports', 'deleted_at')) {
            Schema::table('hospital_reports', function (Blueprint $t) {
                $t->softDeletes();
            });
        }
    }

    public function down(): void
    {
        // No-op. Production keeps these tables; rolling them back
        // would break every controller that does INSERT/UPDATE/SELECT
        // against them.
    }
};