<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if column exists
        $columns = DB::select('SHOW COLUMNS FROM fees WHERE Field = "category"');

        if (!empty($columns)) {
            // Column exists - check if it needs modification
            $columnInfo = $columns[0];
            $currentType = $columnInfo->Type;

            // Check if portal_charge is missing from enum
            if (strpos($currentType, 'portal_charge') === false) {
                // Drop the column first (cannot modify enum directly)
                DB::statement('ALTER TABLE fees DROP COLUMN category');
            } else {
                // Column exists with correct enum - nothing to do
                return;
            }
        }

        // Create the column fresh
        Schema::table('fees', function (Blueprint $table) {
            $table->enum('category', ['indigene', 'non_indigene', 'portal_charge', 'both'])
                ->default('both')
                ->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};