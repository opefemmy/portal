<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_ref')->unique();
            $table->string('patient_name');
            $table->string('patient_email')->nullable();
            $table->string('patient_phone');
            $table->string('patient_gender', 20)->nullable();
            $table->unsignedInteger('patient_age')->nullable();
            $table->foreignId('service_type_id')->nullable()->constrained('hospital_service_types')->nullOnDelete();
            $table->string('service_name')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('portal_charge', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('payment_method', 30)->nullable();
            $table->string('status', 30)->default('pending');
            $table->date('payment_date')->nullable();
            $table->dateTime('appointment_date')->nullable();
            $table->string('doctor_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_phone', 'status']);
            $table->index(['patient_email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_payments');
    }
};
