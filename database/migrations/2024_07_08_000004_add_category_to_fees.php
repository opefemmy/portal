<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if category column exists
        if (!Schema::hasColumn('fees', 'category')) {
            Schema::table('fees', function (Blueprint $table) {
                $table->enum('category', ['indigene', 'non_indigene', 'portal_charge', 'both'])
                    ->default('both')
                    ->after('is_active');
            });
        } else {
            // If exists, try to update the enum
            try {
                DB::statement("ALTER TABLE fees MODIFY category ENUM('indigene', 'non_indigene', 'portal_charge', 'both') DEFAULT 'both'");
            } catch (\Exception $e) {
                // If fails, just add the column with a different approach
                Schema::table('fees', function (Blueprint $table) {
                    $table->enum('category', ['indigene', 'non_indigene', 'portal_charge', 'both'])->default('both')->change();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};