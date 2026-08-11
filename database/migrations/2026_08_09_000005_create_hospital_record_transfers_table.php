<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `hospital_record_transfers` — log of patient-chart transfers performed
 * by the medical_records_officer (intra-department or to an external
 * facility). Pure audit history; no FK cascade because the chart itself
 * stays in the hospital's records system.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hospital_record_transfers')) {
            return;
        }
        Schema::create('hospital_record_transfers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $t->string('transfer_to', 150);
            $t->string('transfer_reason', 80)->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('transferred_by')->constrained('users');
            $t->timestamp('transferred_at');
            $t->timestamps();

            $t->index(['patient_id', 'transferred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_record_transfers');
    }
};