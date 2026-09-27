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
        Schema::table('hospital_patients', function (Blueprint $table) {
            if (!Schema::hasColumn('hospital_patients', 'blood_group')) {
                $table->string('blood_group', 10)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('hospital_patients', 'allergies')) {
                $table->text('allergies')->nullable()->after('blood_group');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hospital_patients', function (Blueprint $table) {
            if (Schema::hasColumn('hospital_patients', 'blood_group')) {
                $table->dropColumn('blood_group');
            }
            if (Schema::hasColumn('hospital_patients', 'allergies')) {
                $table->dropColumn('allergies');
            }
        });
    }
};
