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
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('type')->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_communications');
    }
};
