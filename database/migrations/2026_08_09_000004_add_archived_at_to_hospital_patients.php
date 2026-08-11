<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds archival columns to `hospital_patients` for the medical records workflow.
 *
 * `archived_at` + `archived_by` let the medical_records_officer mark a
 * patient chart as archived without soft-deleting it (clinical notes must
 * remain readable for legal retention). Both columns are nullable so
 * pre-existing rows stay active.
 *
 * Forward-only: production already has these columns (the migration is a
 * no-op there); local DBs that pre-date the column pickup get it now.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hospital_patients')) {
            return;
        }
        Schema::table('hospital_patients', function (Blueprint $t) {
            if (! Schema::hasColumn('hospital_patients', 'archived_at')) {
                $t->timestamp('archived_at')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('hospital_patients', 'archived_by')) {
                $t->foreignId('archived_by')->nullable()->after('archived_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // No-op. Removing the columns would erase the archive state of
        // every chart the records officer has already locked down.
    }
};