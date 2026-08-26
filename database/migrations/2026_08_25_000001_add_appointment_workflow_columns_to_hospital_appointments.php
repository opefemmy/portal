<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patient-flow workflow columns for hospital_appointments.
 *
 * Adds the chain-of-custody fields that drive the records-officer →
 * nurse → doctor-on-call workflow the user wants:
 *
 *   - certified_by / certified_at          — records officer certified
 *                                            the patient's chart is on file
 *                                            before they see a clinician
 *   - assigned_doctor_at / assigned_by    — the records officer (or
 *                                            auto-assigner) chose which
 *                                            doctor on duty sees them
 *   - vitals_recorded_by / vitals_at      — the nurse logged vitals
 *                                            (temperature, BP, pulse…)
 *                                            before the doctor picks
 *                                            them up
 *
 * Per-column guards via Schema::hasColumn make the migration safe
 * to re-run.
 *
 * The existing `status` enum already covers
 * `scheduled|confirmed|checked_in|in_progress|completed|cancelled`.
 * New statuses (`records_certified`, `awaiting_vitals`,
 * `awaiting_doctor`) are introduced at the controller level — no
 * schema change needed for that.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hospital_appointments')) {
            return;
        }

        Schema::table('hospital_appointments', function (Blueprint $t) {
            // Records officer's certification stamp.
            if (! Schema::hasColumn('hospital_appointments', 'certified_by')) {
                $t->foreignId('certified_by')
                    ->nullable()
                    ->after('scheduled_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('hospital_appointments', 'certified_at')) {
                $t->dateTime('certified_at')->nullable()->after('certified_by');
            }

            // Doctor-assignment audit (records officer / system).
            if (! Schema::hasColumn('hospital_appointments', 'assigned_by')) {
                $t->foreignId('assigned_by')
                    ->nullable()
                    ->after('certified_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('hospital_appointments', 'assigned_doctor_at')) {
                $t->dateTime('assigned_doctor_at')->nullable()->after('assigned_by');
            }

            // Nurse vitals stamp.
            if (! Schema::hasColumn('hospital_appointments', 'vitals_recorded_by')) {
                $t->foreignId('vitals_recorded_by')
                    ->nullable()
                    ->after('assigned_doctor_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('hospital_appointments', 'vitals_recorded_at')) {
                $t->dateTime('vitals_recorded_at')->nullable()->after('vitals_recorded_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hospital_appointments')) {
            return;
        }

        Schema::table('hospital_appointments', function (Blueprint $t) {
            foreach ([
                'vitals_recorded_at',
                'vitals_recorded_by',
                'assigned_doctor_at',
                'assigned_by',
                'certified_at',
                'certified_by',
            ] as $col) {
                if (Schema::hasColumn('hospital_appointments', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
