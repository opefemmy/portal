<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === APPLICANTS TABLE ===
        if (Schema::hasTable('applicants')) {
            $columnsToAdd = [
                'email' => 'string',
                'phone' => 'string',
                'state_id' => 'foreignId',
                'lga_id' => 'foreignId',
                'nationality_id' => 'foreignId',
            ];

            if (!Schema::hasColumn('applicants', 'email')) {
                Schema::table('applicants', function (Blueprint $table) {
                    $table->string('email')->nullable()->after('phone');
                });
            }
            if (!Schema::hasColumn('applicants', 'phone')) {
                Schema::table('applicants', function (Blueprint $table) {
                    $table->string('phone')->nullable()->after('gender');
                });
            }
        }

        // === USERS TABLE ===
        if (Schema::hasTable('users')) {
            if (!Schema::hasColumn('users', 'state')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('state')->nullable()->after('phone');
                    $table->string('lga')->nullable()->after('state');
                });
            }
        }

        // === STUDENTS TABLE ===
        if (Schema::hasTable('students')) {
            if (!Schema::hasColumn('students', 'state_id')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
                    $table->foreignId('lga_id')->nullable()->constrained('local_governments')->nullOnDelete();
                    $table->foreignId('nationality_id')->nullable()->constrained('nationalities')->nullOnDelete();
                });
            }
        }

        // === PAYMENTS TABLE ===
        if (Schema::hasTable('payments')) {
            if (!Schema::hasColumn('payments', 'student_id')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                });
            }
        }

        // === COURSES TABLE ===
        if (Schema::hasTable('courses')) {
            if (!Schema::hasColumn('courses', 'programme_id')) {
                Schema::table('courses', function (Blueprint $table) {
                    $table->foreignId('programme_id')->nullable()->constrained('programmes')->nullOnDelete();
                });
            }
        }

        // === STUDENT_COURSES TABLE ===
        if (Schema::hasTable('student_courses')) {
            if (!Schema::hasColumn('student_courses', 'session_id')) {
                Schema::table('student_courses', function (Blueprint $table) {
                    $table->foreignId('session_id')->nullable()->constrained('sessions')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        // No down - we don't remove columns
    }
};
