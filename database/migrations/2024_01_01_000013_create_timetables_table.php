<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_assignment_id')->constrained('course_assignments')->onDelete('cascade');
            $table->string('venue');
            $table->string('day', 15);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('week')->nullable(); // e.g., "1-8" or "odd" or "even"
            $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade');
            $table->string('status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};