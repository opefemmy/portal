<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            // audience: who this payment type is for.
            //   applicant — only ever rendered on the applicant portal
        //   student   — only ever rendered on the student portal
        //   both      — visible to either (default, back-compat for existing rows)
            $table->enum('audience', ['applicant', 'student', 'both'])
                ->default('both')
                ->after('purpose');
        });
    }

    public function down(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            $table->dropColumn('audience');
        });
    }
};
