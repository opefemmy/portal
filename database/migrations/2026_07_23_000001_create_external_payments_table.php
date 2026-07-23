<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->unsignedBigInteger('applicant_id')->nullable()->unique(); // Links to applicant after validation
            $table->boolean('is_used')->default(false);
            $table->unsignedBigInteger('imported_by')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('applicant_id')->references('id')->on('applicants')->onDelete('set null');
            $table->foreign('imported_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['transaction_id', 'is_used']);
            $table->index(['email', 'is_used']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_payments');
    }
};
