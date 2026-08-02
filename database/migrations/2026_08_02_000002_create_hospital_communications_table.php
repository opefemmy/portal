<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('hospital_external_patients')->onDelete('cascade');
            $table->foreignId('visit_id')->nullable()->constrained('hospital_visits')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30)->default('note');
            $table->string('subject');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'is_read']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_communications');
    }
};
