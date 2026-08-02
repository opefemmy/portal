<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'security_question')) {
                $table->string('security_question')->nullable();
            }
            if (!Schema::hasColumn('users', 'security_answer')) {
                $table->string('security_answer')->nullable();
            }
            if (!Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false);
            }
            if (!Schema::hasColumn('users', 'guidance_name')) {
                $table->string('guidance_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'guidance_phone')) {
                $table->string('guidance_phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'guidance_address')) {
                $table->text('guidance_address')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'password_changed_at',
                'security_question',
                'security_answer',
                'must_change_password',
                'guidance_name',
                'guidance_phone',
                'guidance_address',
            ]);
        });
    }
};