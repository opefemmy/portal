<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if email column doesn't exist and add it
        if (!Schema::hasColumn('applicants', 'email')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('email')->nullable()->after('phone');
            });
        }

        // Also add missing columns that might be needed
        if (!Schema::hasColumn('applicants', 'lga_id')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->foreignId('lga_id')->nullable()->constrained('local_governments')->onDelete('set null');
            });
        }

        if (!Schema::hasColumn('applicants', 'state_id')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');
            });
        }

        if (!Schema::hasColumn('applicants', 'nationality_id')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->foreignId('nationality_id')->nullable()->constrained('nationalities')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        // No down - we don't want to remove columns
    }
};
