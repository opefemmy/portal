<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds percent-tracking to the `payments` table.
     *
     *   percent_paid     — 100 / 60 / 40. The slice of the fee this payment covers.
     *   installment_label — 'full' | 'first' | 'second'. Constrained semantic
     *                       that the course-registration and exam-clearance
     *                       gates read. The legacy `installment` column is
     *                       left untouched for back-compat with old receipts.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedTinyInteger('percent_paid')
                ->default(100)
                ->after('amount')
                ->comment('100, 60, or 40 — the slice of the fee this payment covers');

            $table->string('installment_label', 20)
                ->default('full')
                ->after('percent_paid')
                ->comment('full | first | second');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['percent_paid', 'installment_label']);
        });
    }
};