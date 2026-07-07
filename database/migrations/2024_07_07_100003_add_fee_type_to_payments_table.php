<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('fee_type', ['application', 'acceptance', 'school_fees', 'hostel', 'library', 'other'])
                ->default('other')
                ->after('fee_id')
                ->comment('Type of fee this payment is for');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('fee_type');
        });
    }
};