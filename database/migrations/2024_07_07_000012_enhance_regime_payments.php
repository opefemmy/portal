<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regime_payments', function (Blueprint $table) {
            // Payment type (School Fee, Accommodation, etc.)
            if (!Schema::hasColumn('regime_payments', 'payment_type')) {
                $table->string('payment_type')->default('school_fee')->after('name');
            }

            // Scope - which students this applies to
            if (!Schema::hasColumn('regime_payments', 'school_id')) {
                $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            }
            if (!Schema::hasColumn('regime_payments', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            }
            if (!Schema::hasColumn('regime_payments', 'programme_id')) {
                $table->foreignId('programme_id')->nullable()->constrained('programmes')->onDelete('cascade');
            }

            // Session and Semester
            if (!Schema::hasColumn('regime_payments', 'session_id')) {
                $table->foreignId('session_id')->nullable()->constrained('sessions')->onDelete('cascade');
            }
            if (!Schema::hasColumn('regime_payments', 'semester')) {
                $table->string('semester')->nullable()->after('session_id'); // First, Second, Both
            }

            // Level of entry (which level can pay this)
            if (!Schema::hasColumn('regime_payments', 'level')) {
                $table->integer('level')->nullable()->after('semester'); // 1, 2, 3, 4, etc.
            }
            if (!Schema::hasColumn('regime_payments', 'level_operator')) {
                $table->string('level_operator')->default('exact')->after('level'); // exact, minimum, maximum
            }

            // Portal charges
            if (!Schema::hasColumn('regime_payments', 'portal_charge')) {
                $table->decimal('portal_charge', 10, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('regime_payments', 'include_portal_charge')) {
                $table->boolean('include_portal_charge')->default(false)->after('portal_charge');
            }

            // Payment configuration
            if (!Schema::hasColumn('regime_payments', 'payment_config')) {
                $table->string('payment_config')->default('full')->after('include_portal_charge'); // full, 60_40, 50_50
            }
        });
    }

    public function down(): void
    {
        Schema::table('regime_payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type', 'school_id', 'department_id', 'programme_id',
                'session_id', 'semester', 'level', 'level_operator',
                'portal_charge', 'include_portal_charge', 'payment_config'
            ]);
        });
    }
};