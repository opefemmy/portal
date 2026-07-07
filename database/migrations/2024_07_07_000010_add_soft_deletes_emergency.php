<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'hospital_wards',
            'hospital_patients',
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

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function ($t) {
                    $t->softDeletes();
                });
                echo "Added softDeletes to $table\n";
            }
        }
    }

    public function down(): void
    {
        //
    }
};