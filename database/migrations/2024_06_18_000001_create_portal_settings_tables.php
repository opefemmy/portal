<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // System Settings - Only create if not exists
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('value')->nullable();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Payment Gateway Settings
        if (!Schema::hasTable('payment_gateways')) {
            Schema::create('payment_gateways', function (Blueprint $table) {
                $table->id();
                $table->string('provider');
                $table->string('test_public_key')->nullable();
                $table->string('test_secret_key')->nullable();
                $table->string('live_public_key')->nullable();
                $table->string('live_secret_key')->nullable();
                $table->boolean('is_test_mode')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Carry Over Courses
        if (!Schema::hasTable('carry_over_courses')) {
            Schema::create('carry_over_courses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->foreignId('session_id')->constrained()->onDelete('cascade');
                $table->string('semester');
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // Student Attendance Records
        if (!Schema::hasTable('student_attendances')) {
            Schema::create('student_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->foreignId('session_id')->constrained()->onDelete('cascade');
                $table->string('semester');
                $table->date('date');
                $table->string('status');
                $table->string('location')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('SET NULL');
                $table->timestamps();
            });
        }

        // Course Classification
        if (!Schema::hasTable('course_classifications')) {
            Schema::create('course_classifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->string('type');
                $table->string('category')->nullable();
                $table->integer('priority')->default(0);
                $table->timestamps();
            });
        }

        // Student Course Registrations
        Schema::table('student_courses', function (Blueprint $table) {
            if (!Schema::hasColumn('student_courses', 'course_type')) {
                $table->string('course_type')->default('main')->after('status');
            }
            if (!Schema::hasColumn('student_courses', 'carry_over_from_id')) {
                $table->foreignId('carry_over_from_id')->nullable()->after('course_type')->constrained('courses')->onDelete('set null');
            }
        });

        // Results table
        Schema::table('results', function (Blueprint $table) {
            if (!Schema::hasColumn('results', 'ca1')) {
                $table->decimal('ca1', 5, 2)->nullable()->after('ca');
            }
            if (!Schema::hasColumn('results', 'ca2')) {
                $table->decimal('ca2', 5, 2)->nullable()->after('ca1');
            }
            if (!Schema::hasColumn('results', 'tlu')) {
                $table->integer('tlu')->nullable()->after('gpa');
            }
            if (!Schema::hasColumn('results', 'previous_cga')) {
                $table->decimal('previous_cga', 5, 2)->nullable()->after('tlu');
            }
            if (!Schema::hasColumn('results', 'previous_tlu')) {
                $table->decimal('previous_tlu', 5, 2)->nullable()->after('previous_cga');
            }
            if (!Schema::hasColumn('results', 'carry_over_status')) {
                $table->string('carry_over_status')->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('results', 'course_id')) {
                $table->foreignId('course_id')->nullable()->after('student_course_id')->constrained()->onDelete('set null');
            }
        });

        // Applications
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('surname');
            }
            if (!Schema::hasColumn('applications', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('applications', 'place_of_birth')) {
                $table->string('place_of_birth')->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('applications', 'religion')) {
                $table->string('religion')->nullable()->after('place_of_birth');
            }
            if (!Schema::hasColumn('applications', 'blood_group')) {
                $table->string('blood_group')->nullable()->after('religion');
            }
            if (!Schema::hasColumn('applications', 'genotype')) {
                $table->string('genotype')->nullable()->after('blood_group');
            }
            if (!Schema::hasColumn('applications', 'disability')) {
                $table->string('disability')->nullable()->after('genotype');
            }
            if (!Schema::hasColumn('applications', 'disability_details')) {
                $table->string('disability_details')->nullable()->after('disability');
            }
            if (!Schema::hasColumn('applications', 'extra_curricular')) {
                $table->text('extra_curricular')->nullable()->after('disability_details');
            }
        });
    }

    public function down()
    {
        // Note: We don't drop tables as they may contain data
    }
};