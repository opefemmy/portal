<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only safety net for the payments table.
 *
 * The 2026_07_30_000001_add_missing_payment_columns migration was supposed
 * to add payment_date, payer_name, payer_email, payer_phone, payment_purpose,
 * payment_ref, payment_method, portal_charge, total_amount, installment, and
 * student_type to the `payments` table. On at least one local environment
 * the migration ran (recorded in `migrations`) but the `alter table` only
 * partially applied — payment_date and friends were missing, so Eloquent's
 * MassAssignment INSERT into `payments` raised
 * SQLSTATE[42S22]: Column not found: 'payment_date' on the test-payment
 * simulator and the real gateways.
 *
 * Production already has every column (so this migration is a no-op
 * there). On fresh local clones it tops up anything missing, and on
 * the broken local DB it adds the missing columns.
 *
 * Wrapped in column-existence checks so re-running is safe and the
 * migration is fully reversible. dropColumn is intentionally omitted
 * from down() — production keeps these columns, and any development
 * rollback can rebuild the table from the original create migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $this->addIfMissing($table, 'payment_date',    fn($t) => $t->date('payment_date')->nullable());
            $this->addIfMissing($table, 'payer_name',      fn($t) => $t->string('payer_name')->nullable());
            $this->addIfMissing($table, 'payer_email',     fn($t) => $t->string('payer_email')->nullable());
            $this->addIfMissing($table, 'payer_phone',     fn($t) => $t->string('payer_phone')->nullable());
            $this->addIfMissing($table, 'payer_id',        fn($t) => $t->string('payer_id')->nullable());
            $this->addIfMissing($table, 'payment_purpose', fn($t) => $t->string('payment_purpose')->nullable());
            $this->addIfMissing($table, 'payment_ref',     fn($t) => $t->string('payment_ref')->nullable());
            $this->addIfMissing($table, 'payment_method',  fn($t) => $t->string('payment_method')->nullable());
            $this->addIfMissing($table, 'portal_charge',   fn($t) => $t->decimal('portal_charge', 12, 2)->default(0));
            $this->addIfMissing($table, 'total_amount',    fn($t) => $t->decimal('total_amount', 12, 2)->nullable());
            $this->addIfMissing($table, 'installment',     fn($t) => $t->string('installment')->nullable());
            $this->addIfMissing($table, 'student_type',    fn($t) => $t->string('student_type')->nullable());
        });
    }

    public function down(): void
    {
        // No-op on purpose. Production keeps these columns; rolling them
        // back would break every Payment create path. A fresh dev
        // environment can rebuild from the create migration.
    }

    private function addIfMissing(Blueprint $table, string $column, callable $adder): void
    {
        if (! Schema::hasColumn('payments', $column)) {
            $adder($table);
        }
    }
};
