<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop and recreate the enum column to include new types
        Schema::table('programmes', function (Blueprint $table) {
            $table->enum('type', ['ND', 'HND', 'Degree', 'PGD', 'Masters', 'PhD', 'Diploma', 'Pre-ND'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->enum('type', ['ND', 'HND', 'Degree', 'PGD', 'Masters', 'PhD'])->change();
        });
    }
};
