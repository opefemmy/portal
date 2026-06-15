<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('secret_question')->nullable()->after('password');
            $table->string('secret_answer')->nullable()->after('secret_question');
            $table->string('institution_email')->nullable()->after('secret_answer');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['secret_question', 'secret_answer', 'institution_email']);
        });
    }
};