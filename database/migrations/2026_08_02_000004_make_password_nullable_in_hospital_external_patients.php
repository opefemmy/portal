<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Password is optional for external patients — they authenticate via
        // patient_number + access_code (see ExternalPatient::checkAccessCode).
        DB::statement('ALTER TABLE hospital_external_patients MODIFY password VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE hospital_external_patients MODIFY password VARCHAR(255) NOT NULL');
    }
};