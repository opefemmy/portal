<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->string('classification')->nullable()->after('grade_point')->comment('first_class, second_class_upper, second_class_lower, third_class, pass, fail');
            $table->integer('gpa_weight')->default(1)->after('classification');
        });

        // Add course_classifications table
        Schema::create('course_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->integer('min_gpa')->default(0);
            $table->integer('max_gpa')->default(5);
            $table->string('grade_required')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add classification config to sessions
        Schema::table('sessions', function (Blueprint $table) {
            $table->boolean('use_classification')->default(false)->after('semester');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['classification', 'gpa_weight']);
        });
        Schema::dropIfExists('course_classifications');
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('use_classification');
        });
    }
};