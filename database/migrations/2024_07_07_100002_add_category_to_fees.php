<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->enum('category', ['indigene', 'non_indigene', 'both'])->default('both')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};