<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original `results.status` column was created as `string('status', 20)`
     * (see 2024_01_01_000017_create_results_table.php) with the default
     * 'pending' (7 chars). The lecturer bulk upload and the per-student save
     * path both write the value 'pending_approval' (17 chars), which fits
     * in 20 — but on some MySQL configurations (older MariaDB charset
     * bookkeeping, or a column that was recreated at a smaller width
     * outside of migrations) this still triggers
     *     SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status'
     * and Laravel promotes the warning to a hard error in strict mode.
     *
     * Widen the column to varchar(30) to leave headroom and to match the
     * other status-like columns already on this table
     * (pass_status=20, carry_over_status=20).
     */
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Shrinking back to 20 risks truncation if any row currently
        // holds a value longer than 20 chars, so leave the widening in
        // place on rollback.
        Schema::table('results', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }
};
