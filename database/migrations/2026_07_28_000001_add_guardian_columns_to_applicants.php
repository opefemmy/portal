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
                $table->string('guardian_name')->nullable()->after('nationality_id');
            });
        }

        if (!Schema::hasColumn('applicants', 'guardian_relationship')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('guardian_relationship')->nullable()->after('guardian_name');
            });
        }

        if (!Schema::hasColumn('applicants', 'guardian_phone')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('guardian_phone')->nullable()->after('guardian_relationship');
            });
        }

        if (!Schema::hasColumn('applicants', 'guardian_email')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('guardian_email')->nullable()->after('guardian_phone');
            });
        }

        if (!Schema::hasColumn('applicants', 'guardian_occupation')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('guardian_occupation')->nullable()->after('guardian_email');
            });
        }

        if (!Schema::hasColumn('applicants', 'guardian_address')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->text('guardian_address')->nullable()->after('guardian_occupation');
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
