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
            $table->string('uniform_shirt_size')->nullable()->after('nationality_id');
            $table->string('uniform_pant_size')->nullable()->after('uniform_shirt_size');
            $table->string('uniform_shoe_size')->nullable()->after('uniform_pant_size');

            // Scrub measurements
            $table->string('scrub_size')->nullable()->after('uniform_shoe_size');
            $table->string('scrub_color')->nullable()->after('scrub_size');

            // Lab coat measurements
            $table->string('lab_coat_size')->nullable()->after('scrub_color');
            $table->string('lab_coat_length')->nullable()->after('lab_coat_size');

            // Measurement timestamps
            $table->timestamp('measurements_taken_at')->nullable()->after('lab_coat_length');
            $table->unsignedBigInteger('measured_by')->nullable()->after('measurements_taken_at');
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
