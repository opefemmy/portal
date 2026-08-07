<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original `system_settings.value` column was created as
     * `string('value')->nullable()` (see
     * 2024_06_18_000001_create_portal_settings_tables.php), which MySQL
     * resolves to varchar(255). Several keys now hold free-form text
     * that comfortably exceeds that bound — most notably
     * `admission_letter_body`, which is the multi-paragraph admission
     * letter template editable from /registrar/admission-letter/settings.
     * Saving the default template produces an INSERT like
     *
     *   insert into system_settings (key, value, ...) values
     *   ('admission_letter_body', 'We are pleased to inform you ...
     *     for the {session} academic session. ... look forward to
     *     welcoming you on campus.', ...)
     *
     * which is ~360 characters and triggers
     *
     *   SQLSTATE[22001]: String data, right truncated: 1406
     *   Data too long for column 'value' at row 1
     *
     * Widen the column to TEXT (65,535 byte limit on MySQL). That's
     * well over what a single settings value ever needs to be, and it
     * covers the rest of the multi-paragraph templates on this table
     * (institution_address, institution_tagline, etc.).
     *
     * Going varchar → TEXT is a non-length-changing type change so it
     * doesn't depend on doctrine/dbal's exact-length matching the way
     * `string(20) → string(30)` does; Schema::table handles it fine.
     */
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->text('value')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Shrinking TEXT → varchar(255) risks truncation if any row
        // currently holds a value longer than 255 chars, so leave the
        // widening in place on rollback.
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('value')->nullable()->change();
        });
    }
};
