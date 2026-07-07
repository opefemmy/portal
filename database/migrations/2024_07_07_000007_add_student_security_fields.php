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
                $table->timestamp('password_changed_at')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'security_question')) {
                $table->string('security_question')->nullable()->after('password_changed_at');
            }
            if (!Schema::hasColumn('users', 'security_answer')) {
                $table->string('security_answer')->nullable()->after('security_question');
            }
            if (!Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('security_answer');
            }
            if (!Schema::hasColumn('users', 'guidance_name')) {
                $table->string('guidance_name')->nullable()->after('must_change_password');
            }
            if (!Schema::hasColumn('users', 'guidance_phone')) {
                $table->string('guidance_phone')->nullable()->after('guidance_name');
            }
            if (!Schema::hasColumn('users', 'guidance_address')) {
                $table->text('guidance_address')->nullable()->after('guidance_phone');
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