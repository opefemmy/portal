<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * End-of-day sign-out columns on hospital_appointments.
 *
 * Records the moment the records officer closes the day for this
 * patient: who signed out, when, and an optional summary of the
 * day's flow. `sign_out_at` non-null also acts as a soft lock on
 * further clinical edits until the next appointment is scheduled.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hospital_appointments')) {
            return;
        }

        Schema::table('hospital_appointments', function (Blueprint $t) {
            if (! Schema::hasColumn('hospital_appointments', 'sign_out_by')) {
                $t->foreignId('sign_out_by')
                    ->nullable()
                    ->after('vitals_recorded_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('hospital_appointments', 'sign_out_at')) {
                $t->dateTime('sign_out_at')->nullable()->after('sign_out_by');
            }
            if (! Schema::hasColumn('hospital_appointments', 'sign_out_summary')) {
                $t->text('sign_out_summary')->nullable()->after('sign_out_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hospital_appointments')) {
            return;
        }

        Schema::table('hospital_appointments', function (Blueprint $t) {
            foreach (['sign_out_summary', 'sign_out_at', 'sign_out_by'] as $col) {
                if (Schema::hasColumn('hospital_appointments', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
