<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SOAP / clinical notes attached to consultations or visits.
     *
     * Stored as a separate table because:
     *  - a single consultation may have multiple notes (addendum, supervisor review)
     *  - electronic-signature fields live alongside the note
     *  - we keep the existing consultations table untouched to preserve
     *    backward compatibility with every controller that reads it.
     */
    public function up(): void
    {
        Schema::create('hospital_clinical_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $table->foreignId('staff_id')->nullable()->constrained('hospital_staff')->nullOnDelete();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('medical_record_id')->nullable();
            $table->string('note_type', 30)->default('soap');   // soap, progress, nursing, discharge
            // SOAP fields
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->text('free_text')->nullable();
            // Electronic signature
            $table->string('signed_by_name')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_hash', 128)->nullable(); // sha256 of (note id + signer + content)
            $table->boolean('is_amended')->default(false);
            $table->foreignId('amended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'note_type']);
            $table->index('appointment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_clinical_notes');
    }
};