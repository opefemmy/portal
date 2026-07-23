<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('application_number')->unique();
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('religion')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('genotype')->nullable();
            $table->string('disability')->nullable();
            $table->text('disability_details')->nullable();
            $table->text('address')->nullable();

            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('programme_id')->constrained('programmes')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade');

            $table->string('mode_of_study')->nullable();
            $table->string('entry_level')->nullable();

            // O-Level First Sitting
            $table->string('olevel1_subject1')->nullable();
            $table->string('olevel1_grade1')->nullable();
            $table->string('olevel1_subject2')->nullable();
            $table->string('olevel1_grade2')->nullable();
            $table->string('olevel1_subject3')->nullable();
            $table->string('olevel1_grade3')->nullable();
            $table->string('olevel1_subject4')->nullable();
            $table->string('olevel1_grade4')->nullable();
            $table->string('olevel1_subject5')->nullable();
            $table->string('olevel1_grade5')->nullable();
            $table->string('olevel1_exam_year', 4)->nullable();
            $table->string('olevel1_exam_type', 50)->nullable();
            $table->string('olevel1_exam_number', 50)->nullable();

            // O-Level Second Sitting
            $table->string('olevel2_subject1')->nullable();
            $table->string('olevel2_grade1')->nullable();
            $table->string('olevel2_subject2')->nullable();
            $table->string('olevel2_grade2')->nullable();
            $table->string('olevel2_subject3')->nullable();
            $table->string('olevel2_grade3')->nullable();
            $table->string('olevel2_subject4')->nullable();
            $table->string('olevel2_grade4')->nullable();
            $table->string('olevel2_subject5')->nullable();
            $table->string('olevel2_grade5')->nullable();
            $table->string('olevel2_exam_year', 4)->nullable();
            $table->string('olevel2_exam_type', 50)->nullable();
            $table->string('olevel2_exam_number', 50)->nullable();

            $table->text('extra_curricular')->nullable();

            $table->string('payment_status')->nullable();
            $table->string('payment_ref')->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->decimal('payment_amount', 12, 2)->nullable();
            $table->dateTime('payment_date')->nullable();

            $table->enum('status', ['pending', 'reviewing', 'admitted', 'rejected'])->default('pending');
            $table->string('matric_number')->nullable();
            $table->boolean('student_created')->default(false);

            $table->unsignedBigInteger('centre_id')->nullable();
            $table->foreignId('state_id')->nullable();
            $table->foreignId('lga_id')->nullable();
            $table->foreignId('nationality_id')->nullable();

            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
