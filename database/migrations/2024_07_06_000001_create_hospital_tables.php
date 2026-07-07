<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hospital Wards
        Schema::create('hospital_wards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->comment('general, private, emergency, maternity, etc.');
            $table->integer('total_beds');
            $table->integer('available_beds')->default(0);
            $table->decimal('daily_rate', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Hospital Patients
        Schema::create('hospital_patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('patient_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_name')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->date('date_of_birth');
            $table->string('blood_group')->nullable();
            $table->string('genotype')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('state')->nullable();
            $table->string('lga')->nullable();
            $table->string('nationality')->default('Nigerian');
            $table->string('next_of_kin_name');
            $table->string('next_of_kin_phone');
            $table->string('next_of_kin_relationship');
            $table->text('next_of_kin_address')->nullable();
            $table->enum('patient_type', ['student', 'staff', 'visitor', 'dependent'])->default('student');
            $table->foreignId('registered_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Hospital Staff
        Schema::create('hospital_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('staff_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('staff_type', ['doctor', 'nurse', 'laboratorist', 'pharmacist', 'receptionist', 'store_keeper', 'accountant']);
            $table->string('specialization')->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Hospital Beds
        Schema::create('hospital_beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained('hospital_wards')->onDelete('cascade');
            $table->string('bed_number');
            $table->enum('status', ['available', 'occupied', 'maintenance', 'reserved'])->default('available');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->dateTime('occupied_at')->nullable();
            $table->dateTime('discharged_at')->nullable();
            $table->timestamps();
        });

        // Hospital Appointments
        Schema::create('hospital_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('hospital_staff')->onDelete('cascade');
            $table->foreignId('scheduled_by')->constrained('users')->onDelete('cascade');
            $table->dateTime('appointment_date');
            $table->time('appointment_time');
            $table->enum('status', ['scheduled', 'confirmed', 'checked_in', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->text('complaint')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        // Hospital Vital Signs
        Schema::create('hospital_vital_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $table->foreignId('recorded_by')->constrained('hospital_staff')->onDelete('cascade');
            $table->decimal('temperature', 4, 1)->nullable();
            $table->string('blood_pressure_systolic')->nullable();
            $table->string('blood_pressure_diastolic')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->integer('pulse')->nullable();
            $table->integer('oxygen_level')->nullable();
            $table->decimal('blood_sugar', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Hospital Medical Records
        Schema::create('hospital_medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('hospital_staff')->onDelete('set null');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->text('chief_complaint')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('examination_findings')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->dateTime('consultation_date');
            $table->enum('visit_type', ['new', 'follow_up', 'emergency', 'referral'])->default('new');
            $table->timestamps();
        });

        // Hospital Diagnoses
        Schema::create('hospital_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained('hospital_medical_records')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $table->string('icd_code')->nullable();
            $table->string('diagnosis');
            $table->text('description')->nullable();
            $table->enum('severity', ['mild', 'moderate', 'severe', 'critical'])->nullable();
            $table->enum('type', ['primary', 'secondary', 'complication'])->default('primary');
            $table->timestamps();
        });

        // Hospital Prescriptions
        Schema::create('hospital_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('hospital_staff')->onDelete('cascade');
            $table->unsignedBigInteger('medical_record_id')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'dispensed', 'partially_dispensed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('dispensed_by')->nullable();
            $table->dateTime('dispensed_at')->nullable();
            $table->timestamps();
        });

        // Hospital Prescription Items
        Schema::create('hospital_prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('hospital_prescriptions')->onDelete('cascade');
            $table->unsignedBigInteger('drug_id')->nullable();
            $table->string('drug_name');
            $table->string('dosage');
            $table->string('frequency');
            $table->string('duration');
            $table->string('quantity')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_dispensed')->default(false);
            $table->timestamps();
        });

        // Hospital Laboratory Requests
        Schema::create('hospital_lab_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('hospital_staff')->onDelete('cascade');
            $table->unsignedBigInteger('medical_record_id')->nullable();
            $table->string('test_type');
            $table->text('clinical_notes')->nullable();
            $table->enum('status', ['pending', 'sample_collected', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('requested_at');
            $table->dateTime('completed_at')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });

        // Hospital Lab Results
        Schema::create('hospital_lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_request_id')->constrained('hospital_lab_requests')->onDelete('cascade');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->string('test_name');
            $table->string('parameter')->nullable();
            $table->string('result')->nullable();
            $table->string('unit')->nullable();
            $table->string('reference_range')->nullable();
            $table->string('status')->comment('normal, abnormal, critical');
            $table->text('notes')->nullable();
            $table->dateTime('recorded_at')->nullable();
            $table->timestamps();
        });

        // Hospital Admissions
        Schema::create('hospital_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('hospital_staff')->onDelete('cascade');
            $table->unsignedBigInteger('bed_id')->nullable();
            $table->string('admission_number')->unique();
            $table->dateTime('admission_date');
            $table->dateTime('discharge_date')->nullable();
            $table->enum('status', ['admitted', 'discharged', 'transferred', 'deceased'])->default('admitted');
            $table->text('reason')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('discharge_notes')->nullable();
            $table->decimal('daily_rate', 12, 2)->default(0);
            $table->decimal('total_charges', 12, 2)->default(0);
            $table->timestamps();
        });

        // Hospital Referrals
        Schema::create('hospital_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $table->foreignId('referrer_id')->constrained('hospital_staff')->onDelete('cascade');
            $table->unsignedBigInteger('referred_to_id')->nullable();
            $table->string('external_facility')->nullable();
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'accepted', 'completed', 'declined'])->default('pending');
            $table->dateTime('referred_at');
            $table->dateTime('accepted_at')->nullable();
            $table->timestamps();
        });

        // Hospital Reports
        Schema::create('hospital_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_patients')->onDelete('cascade');
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->string('report_type')->comment('medical_report, medical_certificate, lab_report, discharge_summary');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['draft', 'generated', 'printed', 'released'])->default('draft');
            $table->dateTime('released_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_reports');
        Schema::dropIfExists('hospital_referrals');
        Schema::dropIfExists('hospital_admissions');
        Schema::dropIfExists('hospital_lab_results');
        Schema::dropIfExists('hospital_lab_requests');
        Schema::dropIfExists('hospital_prescription_items');
        Schema::dropIfExists('hospital_prescriptions');
        Schema::dropIfExists('hospital_diagnoses');
        Schema::dropIfExists('hospital_medical_records');
        Schema::dropIfExists('hospital_vital_signs');
        Schema::dropIfExists('hospital_appointments');
        Schema::dropIfExists('hospital_staff');
        Schema::dropIfExists('hospital_patients');
        Schema::dropIfExists('hospital_beds');
        Schema::dropIfExists('hospital_wards');
    }
};