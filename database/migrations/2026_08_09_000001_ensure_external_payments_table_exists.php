<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only safety net: re-create the `external_payments` table
 * when the local DB restore from `database_backup_20260724.sql` skipped
 * the original 2026_07_23_000001 migration. Same drift pattern as
 * 2026_08_07_000005 / 2026_08_07_000006: the restore marked every
 * migration from later batches as already-applied, so their
 * `CREATE TABLE` statements never ran against the restored DB.
 * Production has the table; local does not.
 *
 * The schema mirrors 2026_07_23_000001_create_external_payments_table.php
 * plus the payment_type_id column added by
 * 2026_07_23_000003_create_payment_types_table.php.
 *
 * Schema::hasTable is used so re-running this migration against a DB
 * that already has the table is a no-op. down() is intentionally a
 * no-op — production keeps this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('external_payments')) {
            return;
        }

        Schema::create('external_payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('applicant_name');
            $table->string('email');
            $table->decimal('amount', 12, 2);
            $table->dateTime('payment_date');
            $table->string('payment_status'); // pending, completed, failed
            $table->string('payment_channel'); // card, bank, USSD, etc.
            $table->string('description')->nullable();
            $table->unsignedBigInteger('applicant_id')->nullable()->unique();
            $table->boolean('is_used')->default(false);
            $table->unsignedBigInteger('imported_by')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->text('notes')->nullable();
            // Column added by 2026_07_23_000003 — kept here so the schema
            // is complete even on a freshly restored DB.
            $table->unsignedBigInteger('payment_type_id')->nullable();
            $table->timestamps();

            $table->foreign('applicant_id')->references('id')->on('applicants')->onDelete('set null');
            $table->foreign('imported_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
            // payment_types may not exist yet on a freshly restored DB —
            // wrap the FK in a try/catch so this migration runs even when
            // payment_types lands later via another safety-net migration.
            try {
                $table->foreign('payment_type_id')->references('id')->on('payment_types')->onDelete('set null');
            } catch (\Throwable $e) {
                // payment_types table is missing locally; leave the column
                // unconstrained. A later migration will add the FK once
                // payment_types lands.
            }

            $table->index(['transaction_id', 'is_used']);
            $table->index(['email', 'is_used']);
            $table->index('payment_status');
            $table->index('payment_type_id');
        });
    }

    public function down(): void
    {
        // No-op. Production keeps this table.
    }
};
