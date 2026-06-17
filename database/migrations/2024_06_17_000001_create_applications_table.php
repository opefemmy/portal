<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            // Personal Information
            $table->string('surname');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->date('date_of_birth');
            $table->string('place_of_birth');
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->default('Single');
            $table->string('nationality');
            $table->string('state_of_origin')->nullable();
            $table->string('lga')->nullable();
            $table->text('permanent_address');
            $table->text('contact_address')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->string('passport')->nullable();

            // Guardian Information
            $table->string('guardian_name');
            $table->string('guardian_relationship');
            $table->string('guardian_phone');
            $table->string('guardian_email')->nullable();
            $table->string('guardian_occupation')->nullable();
            $table->text('guardian_address')->nullable();

            // Educational Background
            $table->string('primary_school')->nullable();
            $table->string('primary_school_start')->nullable();
            $table->string('primary_school_end')->nullable();
            $table->string('secondary_school')->nullable();
            $table->string('secondary_school_start')->nullable();
            $table->string('secondary_school_end')->nullable();
            $table->string('tertiary_institution')->nullable();
            $table->string('tertiary_qualification')->nullable();
            $table->string('tertiary_start')->nullable();
            $table->string('tertiary_end')->nullable();

            // Programme Selection
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mode_of_study')->default('Full Time');
            $table->string('entry_level')->default('UTME');

            // JAMB Details
            $table->string('jamb_registration_number')->nullable();
            $table->string('jamb_year')->nullable();
            $table->integer('jamb_score')->nullable();
            $table->string('jamb_subject1')->nullable();
            $table->string('jamb_subject2')->nullable();
            $table->string('jamb_subject3')->nullable();
            $table->string('jamb_subject4')->nullable();

            // Document Uploads
            $table->string('olevel_certificate')->nullable();
            $table->string('tertiary_certificate')->nullable();
            $table->string('birth_certificate')->nullable();
            $table->string('lga_id')->nullable();
            $table->string('jamb_result')->nullable();

            // Application Status
            $table->enum('status', ['pending', 'screening', 'approved', 'rejected', 'admitted'])
                  ->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            // User reference (if user creates account)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};