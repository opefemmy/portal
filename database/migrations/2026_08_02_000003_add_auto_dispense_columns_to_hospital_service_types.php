<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_service_types', function (Blueprint $table) {
            // When a payment is completed, the linked service type can have an
            // optional drug auto-dispensed to the patient.
            $table->foreignId('auto_dispense_drug_id')
                ->nullable()
                ->after('requires_appointment')
                ->constrained('hospital_drugs')
                ->nullOnDelete();

            $table->unsignedInteger('auto_dispense_quantity')
                ->nullable()
                ->after('auto_dispense_drug_id')
                ->comment('How many units of the drug to dispense on payment completion');
        });
    }

    public function down(): void
    {
        Schema::table('hospital_service_types', function (Blueprint $table) {
            $table->dropForeign(['auto_dispense_drug_id']);
            $table->dropColumn(['auto_dispense_drug_id', 'auto_dispense_quantity']);
        });
    }
};
