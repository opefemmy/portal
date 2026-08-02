<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('payment_status', 20)
                ->default('pending')
                ->comment('pending, completed, failed');
            $table->string('payment_ref', 100)->nullable();
            $table->string('payment_transaction_id', 100)->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->datetime('payment_date')->nullable();
            $table->string('application_fee_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_ref',
                'payment_transaction_id',
                'payment_amount',
                'payment_date',
                'application_fee_id',
            ]);
        });
    }
};