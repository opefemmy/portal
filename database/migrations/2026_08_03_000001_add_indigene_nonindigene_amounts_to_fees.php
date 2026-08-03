<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds per-category amounts + portal charge to the `fees` table so the
     * admin can set different prices for indigene vs non-indigene students,
     * and apply a portal-processing charge that is added to every payment.
     *
     * All three columns are additive — existing rows keep their `amount` and
     * the controller falls back to it when these are NULL.
     */
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->decimal('indigene_amount', 12, 2)
                ->nullable()
                ->after('amount');

            $table->decimal('non_indigene_amount', 12, 2)
                ->nullable()
                ->after('indigene_amount');

            $table->decimal('portal_charge', 12, 2)
                ->default(0)
                ->after('non_indigene_amount');
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropColumn(['indigene_amount', 'non_indigene_amount', 'portal_charge']);
        });
    }
};