<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Doctor / nurse duty roster. New table — does not extend any
     * existing schedule structure (there is none in the Hospital module).
     */
    public function up(): void
    {
        Schema::create('hospital_duty_roster', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('hospital_staff')->onDelete('cascade');
            $table->date('duty_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('shift', 30)->default('morning');  // morning, evening, night, on_call
            $table->string('location', 120)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['staff_id', 'duty_date', 'shift']);
            $table->index(['duty_date', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_duty_roster');
    }
};