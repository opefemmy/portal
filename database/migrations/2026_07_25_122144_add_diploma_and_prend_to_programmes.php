<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original migration altered a MySQL ENUM column in place. ENUMs are
     * not portable across PostgreSQL, so this migration now replaces the column
     * with a plain string wide enough to hold every allowed value. The
     * application validates the value at the model layer.
     */
    public function up(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('programmes', function (Blueprint $table) {
            $table->string('type', 30)
                ->default('ND')
                ->comment('ND, HND, Degree, PGD, Masters, PhD, Diploma, Pre-ND');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
