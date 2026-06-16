<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type'); // Male, Female, Mixed
            $table->integer('capacity');
            $table->integer('available_rooms')->default(0);
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('gender'); // Male, Female, Both
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained()->onDelete('cascade');
            $table->string('room_number');
            $table->integer('floor');
            $table->integer('capacity');
            $table->integer('available_beds')->default(0);
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hostel_beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_room_id')->constrained()->onDelete('cascade');
            $table->string('bed_number');
            $table->string('status')->default('available'); // available, occupied, maintenance
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('hostel_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained()->onDelete('cascade');
            $table->foreignId('hostel_room_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('bed_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('session_id')->constrained()->onDelete('cascade');
            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->string('status')->default('pending'); // pending, active, checked_out, change_requested
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_allocations');
        Schema::dropIfExists('hostel_beds');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostels');
    }
};