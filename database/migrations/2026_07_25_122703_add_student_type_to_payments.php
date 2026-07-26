<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check current column type and modify if needed
        $connection = config('database.connection');
        $prefix = config('database.connections.mysql.prefix');

        // Get current enum values
        $columns = \DB::select("SHOW COLUMNS FROM payments WHERE Field = 'student_type'");
        if (!empty($columns)) {
            $currentType = $columns[0]->Type;

            // If it's enum, we need to modify it to varchar
            if (str_contains($currentType, 'enum')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('student_type', 50)->change();
                });
            }
        }

        // Also ensure is_verified column exists
        $isVerifiedColumns = \DB::select("SHOW COLUMNS FROM payments WHERE Field = 'is_verified'");
        if (empty($isVerifiedColumns)) {
            Schema::table('payments', function (Blueprint $table) {
                $table->boolean('is_verified')->default(false)->after('student_type');
            });
        }
    }

    public function down(): void
    {
        // No rollback needed
    }
};
