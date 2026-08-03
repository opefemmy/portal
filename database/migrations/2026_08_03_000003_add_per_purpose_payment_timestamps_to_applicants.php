<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add per-purpose payment timestamps to applicants.
     *
     * The existing payment_* columns on applicants only record the FIRST
     * (application) payment. The new three-fee flow (application → acceptance
     * → compulsory) needs to know when each fee was paid, and which one
     * triggers the applicant → student migration.
     *
     *   application_paid_at   — application form fee verified
     *   acceptance_paid_at    — acceptance fee verified (unlocks admission letter)
     *   compulsory_paid_at    — compulsory/school fee verified (triggers migration)
     *   migrated_to_student_at — timestamp the Student row was actually created
     */
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dateTime('application_paid_at')->nullable()->after('payment_date');
            $table->dateTime('acceptance_paid_at')->nullable()->after('application_paid_at');
            $table->dateTime('compulsory_paid_at')->nullable()->after('acceptance_paid_at');
            $table->dateTime('migrated_to_student_at')->nullable()->after('compulsory_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn([
                'application_paid_at',
                'acceptance_paid_at',
                'compulsory_paid_at',
                'migrated_to_student_at',
            ]);
        });
    }
};
