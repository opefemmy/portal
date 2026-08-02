<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_external_patients')->onDelete('cascade');
            $table->foreignId('service_type_id')->constrained('hospital_service_types')->onDelete('cascade');
            $table->string('request_code')->unique();
            $table->string('service_name');
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->decimal('portal_charge', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->dateTime('appointment_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('payment_id')->nullable()->constrained('hospital_payments')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_service_requests');
    }
};
