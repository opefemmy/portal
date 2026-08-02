<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade');
            $table->string('semester', 10);
            $table->string('status', 20)->default('registered');
            $table->timestamps();
            $table->unique(['student_id', 'course_id', 'session_id'], 'student_course_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_courses');
    }
};