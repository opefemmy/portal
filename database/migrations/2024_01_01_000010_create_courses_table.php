<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('title');
            $table->integer('units');
            $table->enum('semester', ['first', 'second']);
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('programme_id')->constrained('programmes')->onDelete('cascade');
            $table->integer('level');
            $table->text('description')->nullable();
            $table->json('prerequisites')->nullable();
            $table->timestamps();

            // Unique constraint: same course code allowed in different schools/departments
            $table->unique(['code', 'school_id', 'department_id', 'programme_id', 'level'], 'course_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};