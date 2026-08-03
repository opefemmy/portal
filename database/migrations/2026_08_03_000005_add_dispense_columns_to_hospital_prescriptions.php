<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The hospital_prescriptions table is missing columns that the model
     * (HospitalPrescription) declares in $fillable / $casts and that views
     * reference in their WHERE clauses:
     *   - dispensed_at (datetime) — used by pharmacy dashboard "dispensed today"
     *   - dispensed_by (FK users) — pharmacist who dispensed
     *   - patient_id   (FK hospital_patients) — model relations rely on this
     *   - doctor_id    (FK hospital_staff)   — model relations rely on this
     *
     * Idempotent: only adds columns that do not already exist.
     */
    public function up(): void
    {
        if (!Schema::hasTable('hospital_prescriptions')) {
            return;
        }

        Schema::table('hospital_prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('hospital_prescriptions', 'patient_id')) {
                $table->foreignId('patient_id')->nullable()->after('visit_id')
                    ->constrained('hospital_patients')->nullOnDelete();
            }
            if (!Schema::hasColumn('hospital_prescriptions', 'doctor_id')) {
                $table->foreignId('doctor_id')->nullable()->after('patient_id')
                    ->constrained('hospital_staff')->nullOnDelete();
            }
            if (!Schema::hasColumn('hospital_prescriptions', 'medical_record_id')) {
                $table->foreignId('medical_record_id')->nullable()->after('doctor_id');
            }
            if (!Schema::hasColumn('hospital_prescriptions', 'notes')) {
                $table->text('notes')->nullable()->after('instructions');
            }
            if (!Schema::hasColumn('hospital_prescriptions', 'status')) {
                $table->string('status', 20)->default('pending')
                    ->comment('pending, dispensed, cancelled')
                    ->after('notes');
            }
            if (!Schema::hasColumn('hospital_prescriptions', 'dispensed_by')) {
                $table->foreignId('dispensed_by')->nullable()->after('status')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('hospital_prescriptions', 'dispensed_at')) {
                $table->timestamp('dispensed_at')->nullable()->after('dispensed_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hospital_prescriptions')) {
            return;
        }

        Schema::table('hospital_prescriptions', function (Blueprint $table) {
            foreach (['dispensed_at', 'dispensed_by', 'status', 'notes', 'medical_record_id', 'doctor_id', 'patient_id'] as $col) {
                if (Schema::hasColumn('hospital_prescriptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
