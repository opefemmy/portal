<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if columns exist before adding
        $columns = DB::getSchemaBuilder()->getColumnListing('students');

        if (!in_array('state_id', $columns)) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');
            });
        }

        if (!in_array('lga_id', $columns)) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('lga_id')->nullable()->constrained('local_governments')->onDelete('set null');
            });
        }

        if (!in_array('nationality_id', $columns)) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('nationality_id')->nullable()->constrained('nationalities')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['lga_id']);
            $table->dropForeign(['nationality_id']);
            $table->dropColumn(['state_id', 'lga_id', 'nationality_id']);
        });
    }
};