<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if category column already exists using SHOW COLUMNS
        $columns = DB::select('SHOW COLUMNS FROM fees WHERE Field = "category"');

        if (empty($columns)) {
            // Column doesn't exist, add it
            Schema::table('fees', function (Blueprint $table) {
                $table->enum('category', ['indigene', 'non_indigene', 'portal_charge', 'both'])
                    ->default('both')
                    ->after('is_active');
            });
        }
        // If column already exists, do nothing
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};