<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, drop foreign keys if they exist
        Schema::table('payments', function (Blueprint $table) {
            // Drop existing foreign keys
            try {
                $table->dropForeign(['student_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            try {
                $table->dropForeign(['fee_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
        });

        // Modify columns to be nullable
        Schema::table('payments', function (Blueprint $table) {
            // Change student_id to nullable bigInteger (not foreignId)
            $table->bigInteger('student_id')->nullable()->change();
            $table->bigInteger('fee_id')->nullable()->change();

            // Add missing columns that controllers are trying to use
            $table->string('payment_ref')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('portal_charge', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payer_name')->nullable();
            $table->string('payer_email')->nullable();
            $table->string('payer_phone')->nullable();
            $table->string('payer_id')->nullable();
            $table->string('payment_purpose')->nullable();
            $table->string('installment')->nullable();
            $table->string('student_type')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_ref',
                'payment_method',
                'portal_charge',
                'total_amount',
                'payment_date',
                'payer_name',
                'payer_email',
                'payer_phone',
                'payer_id',
                'payment_purpose',
                'installment',
                'student_type',
            ]);
        });
    }
};
