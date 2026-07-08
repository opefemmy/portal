<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            // Update category enum to include indigene, non_indigene, portal_charge
            $table->enum('category', [
                'indigene',
                'non_indigene',
                'portal_charge',
                'both'
            ])->default('both')->change();
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->enum('category', ['both'])->change();
        });
    }
};