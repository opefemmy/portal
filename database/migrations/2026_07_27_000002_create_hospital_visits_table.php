<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_external_patients')->onDelete('cascade');
            $table->string('visit_number')->unique();
            $table->dateTime('visit_date');
            $table->string('visit_type')->nullable();
            $table->string('department')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->text('chief_complaint')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment')->nullable();
            $table->string('status')->default('in_progress');
            $table->date('next_visit_date')->nullable();
            $table->text('next_visit_notes')->nullable();
            $table->decimal('vital_signs_temperature', 4, 1)->nullable();
            $table->string('vital_signs_bp')->nullable();
            $table->integer('vital_signs_pulse')->nullable();
            $table->integer('vital_signs_respiration')->nullable();
            $table->integer('vital_signs_oxygen')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_visits');
    }
};
