<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'completed', 'failed'])
                ->default('pending')
                ->after('status');
            $table->string('payment_ref', 100)->nullable()->after('payment_status');
            $table->string('payment_transaction_id', 100)->nullable()->after('payment_ref');
            $table->decimal('payment_amount', 10, 2)->nullable()->after('payment_transaction_id');
            $table->datetime('payment_date')->nullable()->after('payment_amount');
            $table->string('application_fee_id')->nullable()->after('payment_date');
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