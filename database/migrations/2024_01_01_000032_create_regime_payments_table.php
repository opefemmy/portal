<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regime_payments', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Indigene - First Installment"
            $table->enum('student_type', ['Indigene', 'Non-Indigene']);
            $table->enum('installment', ['First', 'Second', 'Full']);
            $table->decimal('percentage', 5, 2)->default(100);
            $table->decimal('amount', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regime_payments');
    }
};