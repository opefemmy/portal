<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('payments');

        if (!in_array('installment', $columns)) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('installment', 20)->nullable();
            });
        }

        if (!in_array('student_type', $columns)) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('student_type', 30)->nullable();
            });
        }

        if (!in_array('is_verified', $columns)) {
            Schema::table('payments', function (Blueprint $table) {
                $table->boolean('is_verified')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['installment', 'student_type', 'is_verified']);
        });
    }
};