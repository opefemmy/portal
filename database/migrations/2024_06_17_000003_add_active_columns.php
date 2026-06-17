<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_active to schools
        Schema::table('schools', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('description');
        });

        // Add is_active to departments
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};