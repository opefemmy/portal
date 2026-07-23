<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_centres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add centre_id to applicants table
        Schema::table('applicants', function (Blueprint $table) {
            $table->unsignedBigInteger('centre_id')->nullable()->after('session_id');
            $table->foreign('centre_id')->references('id')->on('admission_centres')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropForeign(['centre_id']);
            $table->dropColumn('centre_id');
        });

        Schema::dropIfExists('admission_centres');
    }
};
