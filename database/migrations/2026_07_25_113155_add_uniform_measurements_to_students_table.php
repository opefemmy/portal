<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Uniform measurements
            $table->string('uniform_shirt_size')->nullable();
            $table->string('uniform_pant_size')->nullable();
            $table->string('uniform_shoe_size')->nullable();

            // Scrub measurements
            $table->string('scrub_size')->nullable();
            $table->string('scrub_color')->nullable();

            // Lab coat measurements
            $table->string('lab_coat_size')->nullable();
            $table->string('lab_coat_length')->nullable();

            // Measurement timestamps
            $table->timestamp('measurements_taken_at')->nullable();
            $table->unsignedBigInteger('measured_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'uniform_shirt_size',
                'uniform_pant_size',
                'uniform_shoe_size',
                'scrub_size',
                'scrub_color',
                'lab_coat_size',
                'lab_coat_length',
                'measurements_taken_at',
                'measured_by',
            ]);
        });
    }
};
