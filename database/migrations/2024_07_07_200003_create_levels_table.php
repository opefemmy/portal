<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10);
            $table->integer('sort_order')->default(1);
            $table->string('programme_type')->nullable()
                ->comment('ND, HND, etc.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add level_id to students table
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('level_id')->nullable()->constrained('levels')->onDelete('set null');
            $table->string('academic_status', 30)->nullable()->after('status')
                ->comment('active, graduated, withdrawn, expelled, suspended, transferred');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropColumn(['level_id', 'academic_status']);
        });

        Schema::dropIfExists('levels');
    }
};