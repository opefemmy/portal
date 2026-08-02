<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guardian Information columns
        if (!Schema::hasColumn('applicants', 'guardian_name')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('guardian_name')->nullable();
            });
        }

        if (!Schema::hasColumn('applicants', 'guardian_relationship')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('guardian_relationship')->nullable();
            });
        }

        if (!Schema::hasColumn('applicants', 'guardian_phone')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('guardian_phone')->nullable();
            });
        }

        if (!Schema::hasColumn('applicants', 'guardian_email')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('guardian_email')->nullable();
            });
        }

        if (!Schema::hasColumn('applicants', 'guardian_occupation')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('guardian_occupation')->nullable();
            });
        }

        if (!Schema::hasColumn('applicants', 'guardian_address')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->text('guardian_address')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn(['guardian_name', 'guardian_relationship', 'guardian_phone', 'guardian_email', 'guardian_occupation', 'guardian_address']);
        });
    }
};
