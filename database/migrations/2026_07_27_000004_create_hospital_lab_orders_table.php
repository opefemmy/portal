<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_lab_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('hospital_visits')->onDelete('cascade');
            $table->string('test_name');
            $table->string('test_type')->nullable();
            $table->string('urgency')->default('routine');
            $table->text('result')->nullable();
            $table->dateTime('result_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_lab_orders');
    }
};
