<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure hospital_appointments has deleted_at column
        if (Schema::hasTable('hospital_appointments') && !Schema::hasColumn('hospital_appointments', 'deleted_at')) {
            Schema::table('hospital_appointments', function ($table) {
                $table->softDeletes();
            });
        }

        // Also check other hospital tables that might be missing
        $tables = [
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

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function ($t) use ($table) {
                    $t->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        //
    }
};