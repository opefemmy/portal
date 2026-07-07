<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "First Class", "Second Class Upper"
            $table->string('slug')->unique(); // e.g., "first_class", "second_class_upper"
            $table->decimal('min_gpa', 3, 2);
            $table->decimal('max_gpa', 3, 2);
            $table->string('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Also create a grading system table for the score ranges
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->string('grade'); // A, B, C, D, E, F
            $table->integer('min_score');
            $table->integer('max_score');
            $table->decimal('grade_point', 3, 2);
            $table->decimal('gpa_weight', 3, 2)->default(1.0);
            $table->string('remark'); // Excellent, Very Good, etc.
            $table->string('classification')->nullable(); // Links to grade_classifications
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default data
        \Illuminate\Support\Facades\DB::table('grade_classifications')->insert([
            ['name' => 'First Class', 'slug' => 'first_class', 'min_gpa' => 4.50, 'max_gpa' => 5.00, 'description' => 'Outstanding academic performance', 'sort_order' => 1],
            ['name' => 'Second Class Upper', 'slug' => 'second_class_upper', 'min_gpa' => 3.50, 'max_gpa' => 4.49, 'description' => 'Very good academic performance', 'sort_order' => 2],
            ['name' => 'Second Class Lower', 'slug' => 'second_class_lower', 'min_gpa' => 2.50, 'max_gpa' => 3.49, 'description' => 'Good academic performance', 'sort_order' => 3],
            ['name' => 'Third Class', 'slug' => 'third_class', 'min_gpa' => 1.50, 'max_gpa' => 2.49, 'description' => 'Satisfactory performance', 'sort_order' => 4],
            ['name' => 'Pass', 'slug' => 'pass', 'min_gpa' => 1.00, 'max_gpa' => 1.49, 'description' => 'Minimum passing grade', 'sort_order' => 5],
            ['name' => 'Fail', 'slug' => 'fail', 'min_gpa' => 0.00, 'max_gpa' => 0.99, 'description' => 'Did not meet requirements', 'sort_order' => 6],
        ]);

        \Illuminate\Support\Facades\DB::table('grading_scales')->insert([
            ['grade' => 'A', 'min_score' => 70, 'max_score' => 100, 'grade_point' => 4.00, 'gpa_weight' => 4.0, 'remark' => 'Excellent', 'classification' => 'first_class', 'sort_order' => 1],
            ['grade' => 'B', 'min_score' => 60, 'max_score' => 69, 'grade_point' => 3.50, 'gpa_weight' => 3.5, 'remark' => 'Very Good', 'classification' => 'second_class_upper', 'sort_order' => 2],
            ['grade' => 'C', 'min_score' => 50, 'max_score' => 59, 'grade_point' => 3.00, 'gpa_weight' => 3.0, 'remark' => 'Good', 'classification' => 'second_class_lower', 'sort_order' => 3],
            ['grade' => 'D', 'min_score' => 45, 'max_score' => 49, 'grade_point' => 2.50, 'gpa_weight' => 2.5, 'remark' => 'Fair', 'classification' => 'third_class', 'sort_order' => 4],
            ['grade' => 'E', 'min_score' => 40, 'max_score' => 44, 'grade_point' => 2.00, 'gpa_weight' => 2.0, 'remark' => 'Pass', 'classification' => 'pass', 'sort_order' => 5],
            ['grade' => 'F', 'min_score' => 0, 'max_score' => 39, 'grade_point' => 0.00, 'gpa_weight' => 0.0, 'remark' => 'Fail', 'classification' => 'fail', 'sort_order' => 6],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_scales');
        Schema::dropIfExists('grade_classifications');
    }
};