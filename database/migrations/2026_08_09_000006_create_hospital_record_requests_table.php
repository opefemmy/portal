<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `hospital_record_requests` — clinicians requesting access to a patient
 * chart that they don't routinely see (records officer's queue).
 *
 * Status flow: pending → approved | rejected | fulfilled. The records
 * officer fulfills by handing over the chart and stamping `fulfilled_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hospital_record_requests')) {
            return;
        }
        Schema::create('hospital_record_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $t->foreignId('requested_by')->constrained('users');
            $t->foreignId('fulfilled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('pending');
            $t->text('reason')->nullable();
            $t->text('notes')->nullable();
            $t->timestamp('requested_at');
            $t->timestamp('fulfilled_at')->nullable();
            $t->timestamps();

            $t->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_record_requests');
    }
};