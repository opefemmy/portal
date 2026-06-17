<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Personal Information
            $table->string('surname')->nullable()->after('user_id');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->nullable();
            $table->string('nationality')->nullable();
            $table->string('state_of_origin')->nullable();
            $table->string('lga')->nullable();
            $table->text('permanent_address')->nullable();
            $table->text('contact_address')->nullable();
            $table->string('phone')->nullable();
            $table->string('passport')->nullable();

            // Guardian Information
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relationship')->nullable();
            $table->string('guardian_phone')->nullable();
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
            $table->string('mode_of_study')->nullable()->default('Full Time');
            $table->string('entry_level')->nullable()->default('UTME');

            // JAMB Details
            $table->string('jamb_registration_number')->nullable();
            $table->string('jamb_year')->nullable();
            $table->integer('jamb_score')->nullable();
            $table->string('jamb_subject1')->nullable();
            $table->string('jamb_subject2')->nullable();
            $table->string('jamb_subject3')->nullable();
            $table->string('jamb_subject4')->nullable();

            // Documents
            $table->string('olevel_certificate')->nullable();
            $table->string('tertiary_certificate')->nullable();
            $table->string('birth_certificate')->nullable();
            $table->string('lga_id')->nullable();
            $table->string('jamb_result')->nullable();

            // Review
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn([
                'surname', 'first_name', 'middle_name', 'date_of_birth', 'place_of_birth',
                'gender', 'marital_status', 'nationality', 'state_of_origin', 'lga',
                'permanent_address', 'contact_address', 'phone', 'passport',
                'guardian_name', 'guardian_relationship', 'guardian_phone', 'guardian_email',
                'guardian_occupation', 'guardian_address',
                'primary_school', 'primary_school_start', 'primary_school_end',
                'secondary_school', 'secondary_school_start', 'secondary_school_end',
                'tertiary_institution', 'tertiary_qualification', 'tertiary_start', 'tertiary_end',
                'mode_of_study', 'entry_level',
                'jamb_registration_number', 'jamb_year', 'jamb_score',
                'jamb_subject1', 'jamb_subject2', 'jamb_subject3', 'jamb_subject4',
                'olevel_certificate', 'tertiary_certificate', 'birth_certificate',
                'lga_id', 'jamb_result',
                'reviewed_by', 'reviewed_at', 'rejection_reason'
            ]);
        });
    }
};