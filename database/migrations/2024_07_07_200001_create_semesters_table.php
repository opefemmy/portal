<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10);
            $table->integer('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add semester_id to student_courses
        Schema::table('student_courses', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
        });

        // Add semester_id to results
        Schema::table('results', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('student_courses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });

        Schema::dropIfExists('semesters');
    }
};