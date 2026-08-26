<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patient-file staff notes.
 *
 * A free-text, time-stamped note any clinical or administrative
 * staff (doctor, nurse, records officer, lab scientist,
 * pharmacist, radiographer, …) drops on a patient's chart. The
 * note records who wrote it, when, and which patient it's about;
 * downstream staff read it as a running handover log ("Dr. Smith
 * told Nurse Mary to do X before lunch").
 *
 * The notes are NOT prescriptions, NOT lab orders, NOT vital signs
 * — those have their own tables. This is the free-form commentary
 * layer that the user wants on top of the structured clinical
 * record.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hospital_staff_notes')) {
            return;
        }

        Schema::create('hospital_staff_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
            $t->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('appointment_id')
                ->nullable()
                ->constrained('hospital_appointments')
                ->nullOnDelete();
            // The intended audience ("nurse", "doctor", "pharmacy", "lab",
            // "records", or "all"). Used by the patient-file timeline
            // to group related notes together.
            $t->string('audience', 30)->default('all');
            // Note type — 'handover' (default), 'instruction', 'commentary',
            // 'alert'. Helps the patient timeline sort by severity.
            $t->string('note_type', 30)->default('handover');
            $t->text('body');
            $t->boolean('is_pinned')->default(false);
            $t->timestamps();
            $t->softDeletes();

            $t->index(['patient_id', 'created_at']);
            $t->index('appointment_id');
            $t->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_staff_notes');
    }
};
