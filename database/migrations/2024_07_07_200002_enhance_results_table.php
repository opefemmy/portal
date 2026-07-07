<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            // Add computed fields for result computation
            if (!Schema::hasColumn('results', 'quality_point')) {
                $table->decimal('quality_point', 10, 2)->nullable()->after('grade_point')
                    ->comment('Course Unit × Grade Point');
            }

            if (!Schema::hasColumn('results', 'pass_status')) {
                $table->string('pass_status', 20)->nullable()->after('grade_point')
                    ->comment('PASS, FAIL, REPEAT, CARRY_OVER, ABSENT, INCOMPLETE, MALPRACTICE');
            }

            if (!Schema::hasColumn('results', 'academic_remark')) {
                $table->string('academic_remark', 50)->nullable()->after('status')
                    ->comment('DISTINCTION, UPPER_CREDIT, LOWER_CREDIT, PASS, PROBATION, WITHDRAWN, FAIL');
            }

            if (!Schema::hasColumn('results', 'carry_over_status')) {
                $table->string('carry_over_status', 20)->nullable()->after('academic_remark')
                    ->comment('pending, cleared, repeat');
            }

            if (!Schema::hasColumn('results', 'is_repeated')) {
                $table->boolean('is_repeated')->default(false)->after('carry_over_status');
            }

            if (!Schema::hasColumn('results', 'attempt_number')) {
                $table->integer('attempt_number')->default(1)->after('is_repeated');
            }

            if (!Schema::hasColumn('results', 'computation_notes')) {
                $table->text('computation_notes')->nullable()->after('attempt_number');
            }

            if (!Schema::hasColumn('results', 'semester_id')) {
                $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null')->after('exam');
            }

            // Add legacy fields if missing
            if (!Schema::hasColumn('results', 'ca1')) {
                $table->decimal('ca1', 5, 2)->nullable()->after('ca');
            }
            if (!Schema::hasColumn('results', 'ca2')) {
                $table->decimal('ca2', 5, 2)->nullable()->after('ca1');
            }
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $columnsToDrop = [
                'quality_point',
                'pass_status',
                'academic_remark',
                'carry_over_status',
                'is_repeated',
                'attempt_number',
                'computation_notes',
                'semester_id',
                'ca1',
                'ca2',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('results', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Drop foreign key for semester_id
            $table->dropForeign(['semester_id']);
        });
    }
};