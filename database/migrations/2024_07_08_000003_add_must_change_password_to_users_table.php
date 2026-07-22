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
        Schema::table('users', function (Blueprint $table) {
            // Add must_change_password column if it doesn't exist
            if (!Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('is_active');
            }

            // Add email_verified_at if it doesn't exist (for email verification)
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('must_change_password');
            }

            // Add security question fields
            if (!Schema::hasColumn('users', 'security_question')) {
                $table->string('security_question')->nullable()->after('email_verified_at');
            }

            if (!Schema::hasColumn('users', 'security_answer')) {
                $table->string('security_answer')->nullable()->after('security_question');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'must_change_password',
                'email_verified_at',
                'security_question',
                'security_answer',
            ]);
        });
    }
};