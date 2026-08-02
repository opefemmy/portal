<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_order_items', function (Blueprint $table) {
            $table->id();
            // Polymorphic target: HospitalPrescriptionItem OR HospitalLabRequest
            $table->morphs('orderable');
            $table->foreignId('patient_id')->nullable()
                ->constrained('hospital_patients')->nullOnDelete();
            $table->foreignId('external_patient_id')->nullable()
                ->constrained('hospital_external_patients')->nullOnDelete();
            $table->string('item_name');
            $table->decimal('amount', 12, 2)->default(0);
            // awaiting_payment | paid | cancelled
            $table->string('status', 30)->default('awaiting_payment');
            $table->foreignId('payment_id')->nullable()
                ->constrained('hospital_payments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['patient_id', 'status']);
            $table->index(['external_patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_order_items');
    }
};