<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original 2024_07_07_000003_add_soft_deletes_to_hospital_tables
     * migration was marked Ran but actually added deleted_at inconsistently.
     * Many hospital_* tables still miss the column while their models use
     * SoftDeletes, causing "Unknown column 'deleted_at' in 'where clause'".
     *
     * This migration idempotently adds deleted_at where missing.
     */
    public function up(): void
    {
        $tables = [
            'hospital_drugs',
            'hospital_drug_categories',
            'hospital_drug_batches',
            'hospital_suppliers',
            'hospital_prescriptions',
            'hospital_prescription_items',
            'hospital_inventory_movements',
            'hospital_store_items',
            'hospital_store_batches',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if (!Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'hospital_drugs',
            'hospital_drug_categories',
            'hospital_drug_batches',
            'hospital_suppliers',
            'hospital_prescriptions',
            'hospital_prescription_items',
            'hospital_inventory_movements',
            'hospital_store_items',
            'hospital_store_batches',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropSoftDeletes();
                });
            }
        }
    }
};
