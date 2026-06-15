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
                $table->enum('installment', ['First', 'Second', 'Full'])->nullable()->after('amount');
            });
        }

        if (!in_array('student_type', $columns)) {
            Schema::table('payments', function (Blueprint $table) {
                $table->enum('student_type', ['Indigene', 'Non-Indigene'])->nullable()->after('installment');
            });
        }

        if (!in_array('is_verified', $columns)) {
            Schema::table('payments', function (Blueprint $table) {
                $table->boolean('is_verified')->default(false)->after('student_type');
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