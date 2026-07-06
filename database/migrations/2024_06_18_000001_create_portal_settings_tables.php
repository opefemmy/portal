<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // System Settings - for managing admission, course reg, payment toggles
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Payment Gateway Settings
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // flutterwave, paystack, stripe, etc.
            $table->string('test_public_key')->nullable();
            $table->string('test_secret_key')->nullable();
            $table->string('live_public_key')->nullable();
            $table->string('live_secret_key')->nullable();
            $table->boolean('is_test_mode')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Carry Over Courses (for tracking)
        Schema::create('carry_over_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->constrained()->onDelete('cascade');
            $table->string('semester');
            $table->string('status')->default('pending'); // pending, registered, passed, failed
            $table->timestamps();
        });

        // Student Attendance Records
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->constrained()->onDelete('cascade');
            $table->string('semester');
            $table->date('date');
            $table->string('status'); // present, absent, late
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set_null');
            $table->timestamps();
        });

        // Course Classification (Main, Elective, Carry Over)
        Schema::create('course_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('type'); // main, elective, carry_over
            $table->string('category')->nullable(); // for electives grouping
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        // Student Course Registrations with selection type
        Schema::table('student_courses', function (Blueprint $table) {
            $table->string('course_type')->default('main')->after('status'); // main, elective, carry_over
            $table->foreignId('carry_over_from_id')->nullable()->after('course_type')->constrained('courses')->onDelete('set null');
        });

        // Result with additional fields for calculation
        Schema::table('results', function (Blueprint $table) {
            $table->decimal('ca1', 5, 2)->nullable()->after('ca');
            $table->decimal('ca2', 5, 2)->nullable()->after('ca1');
            $table->integer('tlu')->nullable()->after('gpa'); // Total Learning Units
            $table->decimal('previous_cga', 5, 2)->nullable()->after('tlu');
            $table->decimal('previous_tlu', 5, 2)->nullable()->after('previous_cga');
            $table->string('carry_over_status')->nullable()->after('remarks'); // none, pending, cleared
            $table->foreignId('course_id')->nullable()->after('student_course_id')->constrained()->onDelete('set null');
        });

        // Application additional fields
        Schema::table('applications', function (Blueprint $table) {
            $table->string('middle_name')->nullable()->after('surname');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('place_of_birth')->nullable()->after('date_of_birth');
            $table->string('religion')->nullable()->after('place_of_birth');
            $table->string('blood_group')->nullable()->after('religion');
            $table->string('genotype')->nullable()->after('blood_group');
            $table->string('disability')->nullable()->after('genotype');
            $table->string('disability_details')->nullable()->after('disability');
            $table->text('extra_curricular')->nullable()->after('disability_details');
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('carry_over_courses');
        Schema::dropIfExists('student_attendances');
        Schema::dropIfExists('course_classifications');

        Schema::table('student_courses', function (Blueprint $table) {
            $table->dropColumn(['course_type', 'carry_over_from_id']);
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn(['ca1', 'ca2', 'tlu', 'previous_cga', 'previous_tlu', 'carry_over_status', 'course_id']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['middle_name', 'date_of_birth', 'place_of_birth', 'religion', 'blood_group', 'genotype', 'disability', 'disability_details', 'extra_curricular']);
        });
    }
};