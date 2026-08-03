<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail for every clinical / administrative action
     * in the hospital module.
     *
     * Why this is a NEW table (not extended onto existing logs):
     *  - hospital actions need structured clinical context (patient_id,
     *    action_type) that the generic activity log does not capture.
     *  - keeping it in its own table allows retention/deletion rules
     *    specific to medical-legal requirements without touching the
     *    global audit log.
     */
    public function up(): void
    {
        Schema::create('hospital_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_role', 60)->nullable();
            $table->foreignId('patient_id')->nullable()->constrained('hospital_patients')->nullOnDelete();
            $table->string('action', 80);              // consultation.create, prescription.dispense, ...
            $table->string('subject_type', 120)->nullable(); // model class
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['patient_id', 'action']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_audit_trail');
    }
};