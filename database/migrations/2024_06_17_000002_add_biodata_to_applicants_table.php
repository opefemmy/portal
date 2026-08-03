<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Defensive: skip columns that already exist. The original create
        // migration already declares some of these (surname, first_name,
        // etc.) and re-adding them on a fresh DB raises a SQL error.
        $existing = collect(Schema::getColumns('applicants'))->pluck('name')->all();

        Schema::table('applicants', function (Blueprint $table) use ($existing) {
            $addIfMissing = function (string $col, callable $callback) use ($existing) {
                if (! in_array($col, $existing, true)) {
                    $callback();
                }
            };

            // Personal Information
            $addIfMissing('surname', fn () => $table->string('surname')->nullable());
            $addIfMissing('first_name', fn () => $table->string('first_name')->nullable());
            $addIfMissing('middle_name', fn () => $table->string('middle_name')->nullable());
            $addIfMissing('date_of_birth', fn () => $table->date('date_of_birth')->nullable());
            $addIfMissing('place_of_birth', fn () => $table->string('place_of_birth')->nullable());
            $addIfMissing('gender', fn () => $table->string('gender', 20)->nullable());
            $addIfMissing('marital_status', fn () => $table->string('marital_status', 20)->nullable());
            $addIfMissing('nationality', fn () => $table->string('nationality')->nullable());
            $addIfMissing('state_of_origin', fn () => $table->string('state_of_origin')->nullable());
            $addIfMissing('lga', fn () => $table->string('lga')->nullable());
            $addIfMissing('permanent_address', fn () => $table->text('permanent_address')->nullable());
            $addIfMissing('contact_address', fn () => $table->text('contact_address')->nullable());
            $addIfMissing('phone', fn () => $table->string('phone')->nullable());
            $addIfMissing('passport', fn () => $table->string('passport')->nullable());

            // Guardian Information
            $addIfMissing('guardian_name', fn () => $table->string('guardian_name')->nullable());
            $addIfMissing('guardian_relationship', fn () => $table->string('guardian_relationship')->nullable());
            $addIfMissing('guardian_phone', fn () => $table->string('guardian_phone')->nullable());
            $addIfMissing('guardian_email', fn () => $table->string('guardian_email')->nullable());
            $addIfMissing('guardian_occupation', fn () => $table->string('guardian_occupation')->nullable());
            $addIfMissing('guardian_address', fn () => $table->text('guardian_address')->nullable());

            // Educational Background
            $addIfMissing('primary_school', fn () => $table->string('primary_school')->nullable());
            $addIfMissing('primary_school_start', fn () => $table->string('primary_school_start')->nullable());
            $addIfMissing('primary_school_end', fn () => $table->string('primary_school_end')->nullable());
            $addIfMissing('secondary_school', fn () => $table->string('secondary_school')->nullable());
            $addIfMissing('secondary_school_start', fn () => $table->string('secondary_school_start')->nullable());
            $addIfMissing('secondary_school_end', fn () => $table->string('secondary_school_end')->nullable());
            $addIfMissing('tertiary_institution', fn () => $table->string('tertiary_institution')->nullable());
            $addIfMissing('tertiary_qualification', fn () => $table->string('tertiary_qualification')->nullable());
            $addIfMissing('tertiary_start', fn () => $table->string('tertiary_start')->nullable());
            $addIfMissing('tertiary_end', fn () => $table->string('tertiary_end')->nullable());

            // Programme Selection
            $addIfMissing('mode_of_study', fn () => $table->string('mode_of_study')->nullable()->default('Full Time'));
            $addIfMissing('entry_level', fn () => $table->string('entry_level')->nullable()->default('UTME'));

            // JAMB Details
            $addIfMissing('jamb_registration_number', fn () => $table->string('jamb_registration_number')->nullable());
            $addIfMissing('jamb_year', fn () => $table->string('jamb_year')->nullable());
            $addIfMissing('jamb_score', fn () => $table->integer('jamb_score')->nullable());
            $addIfMissing('jamb_subject1', fn () => $table->string('jamb_subject1')->nullable());
            $addIfMissing('jamb_subject2', fn () => $table->string('jamb_subject2')->nullable());
            $addIfMissing('jamb_subject3', fn () => $table->string('jamb_subject3')->nullable());
            $addIfMissing('jamb_subject4', fn () => $table->string('jamb_subject4')->nullable());

            // Documents
            $addIfMissing('olevel_certificate', fn () => $table->string('olevel_certificate')->nullable());
            $addIfMissing('tertiary_certificate', fn () => $table->string('tertiary_certificate')->nullable());
            $addIfMissing('birth_certificate', fn () => $table->string('birth_certificate')->nullable());
            $addIfMissing('lga_id', fn () => $table->string('lga_id')->nullable());
            $addIfMissing('jamb_result', fn () => $table->string('jamb_result')->nullable());

            // Review
            if (! in_array('reviewed_by', $existing, true)) {
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! in_array('reviewed_at', $existing, true)) {
                $table->timestamp('reviewed_at')->nullable();
            }
            if (! in_array('rejection_reason', $existing, true)) {
                $table->text('rejection_reason')->nullable();
            }
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