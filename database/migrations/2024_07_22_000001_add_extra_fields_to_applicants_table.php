<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // O-Level Exam Type and Number
            if (!Schema::hasColumn('applicants', 'olevel1_exam_type')) {
                $table->string('olevel1_exam_type', 50)->nullable()->after('olevel1_exam_year');
            }
            if (!Schema::hasColumn('applicants', 'olevel1_exam_number')) {
                $table->string('olevel1_exam_number', 50)->nullable()->after('olevel1_exam_type');
            }
            if (!Schema::hasColumn('applicants', 'olevel2_exam_type')) {
                $table->string('olevel2_exam_type', 50)->nullable()->after('olevel2_exam_year');
            }
            if (!Schema::hasColumn('applicants', 'olevel2_exam_number')) {
                $table->string('olevel2_exam_number', 50)->nullable()->after('olevel2_exam_type');
            }

            // Additional fields
            if (!Schema::hasColumn('applicants', 'religion')) {
                $table->string('religion', 100)->nullable()->after('place_of_birth');
            }
            if (!Schema::hasColumn('applicants', 'blood_group')) {
                $table->string('blood_group', 5)->nullable()->after('religion');
            }
            if (!Schema::hasColumn('applicants', 'genotype')) {
                $table->string('genotype', 5)->nullable()->after('blood_group');
            }
            if (!Schema::hasColumn('applicants', 'disability')) {
                $table->string('disability', 50)->nullable()->after('genotype');
            }
            if (!Schema::hasColumn('applicants', 'disability_details')) {
                $table->text('disability_details')->nullable()->after('disability');
            }
            if (!Schema::hasColumn('applicants', 'address')) {
                $table->text('address')->nullable()->after('disability_details');
            }
            if (!Schema::hasColumn('applicants', 'extra_curricular')) {
                $table->text('extra_curricular')->nullable()->after('olevel2_exam_number');
            }
            if (!Schema::hasColumn('applicants', 'matric_number')) {
                $table->string('matric_number', 50)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $columnsToDrop = [
                'olevel1_exam_type',
                'olevel1_exam_number',
                'olevel2_exam_type',
                'olevel2_exam_number',
                'religion',
                'blood_group',
                'genotype',
                'disability',
                'disability_details',
                'address',
                'extra_curricular',
                'matric_number',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('applicants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
