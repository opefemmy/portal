<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // O-Level First Sitting Subjects
            $olevel1Subjects = ['olevel1_subject1', 'olevel1_grade1', 'olevel1_subject2', 'olevel1_grade2', 'olevel1_subject3', 'olevel1_grade3', 'olevel1_subject4', 'olevel1_grade4', 'olevel1_subject5', 'olevel1_grade5'];
            foreach ($olevel1Subjects as $col) {
                if (!Schema::hasColumn('applicants', $col)) {
                    $table->string($col, 100)->nullable()->after('mode_of_study');
                }
            }

            // O-Level First Sitting Exam Details
            if (!Schema::hasColumn('applicants', 'olevel1_exam_year')) {
                $table->string('olevel1_exam_year', 4)->nullable();
            }
            if (!Schema::hasColumn('applicants', 'olevel1_exam_type')) {
                $table->string('olevel1_exam_type', 50)->nullable();
            }
            if (!Schema::hasColumn('applicants', 'olevel1_exam_number')) {
                $table->string('olevel1_exam_number', 50)->nullable();
            }

            // O-Level Second Sitting Subjects
            $olevel2Subjects = ['olevel2_subject1', 'olevel2_grade1', 'olevel2_subject2', 'olevel2_grade2', 'olevel2_subject3', 'olevel2_grade3', 'olevel2_subject4', 'olevel2_grade4', 'olevel2_subject5', 'olevel2_grade5'];
            foreach ($olevel2Subjects as $col) {
                if (!Schema::hasColumn('applicants', $col)) {
                    $table->string($col, 100)->nullable();
                }
            }

            // O-Level Second Sitting Exam Details
            if (!Schema::hasColumn('applicants', 'olevel2_exam_year')) {
                $table->string('olevel2_exam_year', 4)->nullable();
            }
            if (!Schema::hasColumn('applicants', 'olevel2_exam_type')) {
                $table->string('olevel2_exam_type', 50)->nullable();
            }
            if (!Schema::hasColumn('applicants', 'olevel2_exam_number')) {
                $table->string('olevel2_exam_number', 50)->nullable();
            }

            // Additional Personal Info
            if (!Schema::hasColumn('applicants', 'religion')) {
                $table->string('religion', 100)->nullable();
            }
            if (!Schema::hasColumn('applicants', 'blood_group')) {
                $table->string('blood_group', 5)->nullable();
            }
            if (!Schema::hasColumn('applicants', 'genotype')) {
                $table->string('genotype', 5)->nullable();
            }
            if (!Schema::hasColumn('applicants', 'disability')) {
                $table->string('disability', 50)->nullable();
            }
            if (!Schema::hasColumn('applicants', 'disability_details')) {
                $table->text('disability_details')->nullable();
            }
            if (!Schema::hasColumn('applicants', 'address')) {
                $table->text('address')->nullable();
            }
            if (!Schema::hasColumn('applicants', 'extra_curricular')) {
                $table->text('extra_curricular')->nullable();
            }
            if (!Schema::hasColumn('applicants', 'matric_number')) {
                $table->string('matric_number', 50)->nullable();
            }
        });
    }

    public function down(): void
    {
        // No down - too risky to drop columns
    }
};
