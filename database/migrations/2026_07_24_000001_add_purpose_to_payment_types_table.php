<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            $table->enum('purpose', [
                'application',
                'school_fee',
                'acceptance',
                'hostel',
                'registration',
                'library',
                'other'
            ])->default('other')->after('priority')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });
    }
};
