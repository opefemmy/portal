<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert `payment_types.purpose` from a varchar/string into the
 * production ENUM of 8 allowed values.
 *
 * Why this migration exists:
 *
 *   - The repo migration 2026_07_24_000001_add_purpose_to_payment_types_table
 *     declares `purpose` as a plain `string(30)` with default 'other'.
 *   - Production has long since run an ALTER TABLE that converted
 *     that column to ENUM('application','acceptance','school_fees',
 *     'hostel','library','registration','other','compulsory'). Adding
 *     a new payment_type row with `purpose=compulsory` works on
 *     prod but fails on any local DB still running the original
 *     string column with the warning:
 *
 *       SQLSTATE[01000]: Warning: 1265 Data truncated for column
 *       'purpose' at row 1
 *
 *   - Production ENUM has 8 values; the original local column has
 *     no constraint. Some values written on prod (e.g. 'compulsory')
 *     do not exist in any local ENUM list. To pin behaviour, we
 *     normalise local to the same 8-value ENUM production uses.
 *
 * Behaviour:
 *
 *   - Production already has the ENUM → migration detects this via
 *     INFORMATION_SCHEMA and short-circuits. No-op.
 *   - Local with varchar → ALTER COLUMN to the ENUM. Any existing
 *     row whose `purpose` is outside the allowed set is rewritten
 *     to 'other' first so the conversion does not raise
 *     "Data truncated" mid-statement.
 *   - Idempotent on local too: re-running detects the column is
 *     already the ENUM and skips.
 *
 * Reverse (`down()`) is a no-op. Rolling this back on production
 * would lose the ENUM constraint; if a future developer ever needs
 * to do that, they can run it manually. The down() should not be a
 * surprise foot-gun.
 */
return new class extends Migration
{
    /**
     * The 8-value production ENUM. Kept in sync with
     * PaymentType::allowedPurposes().
     */
    private const ALLOWED_PURPOSES = [
        'application',
        'acceptance',
        'school_fees',
        'hostel',
        'library',
        'registration',
        'other',
        'compulsory',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('payment_types')) {
            return;
        }

        $current = $this->describePurposeColumn();

        if ($current === null) {
            return;
        }

        if ($this->isAlreadyProductionEnum($current)) {
            // Production or any DB that's already been converted.
            return;
        }

        // Convert any out-of-range values to 'other' so the upcoming
        // ALTER doesn't trip Data truncated. Belt-and-braces: also
        // catches rows whose value would silently map to '' under
        // strict SQL modes.
        $allowedList = "'" . implode("','", self::ALLOWED_PURPOSES) . "'";
        DB::statement("
            UPDATE payment_types
               SET purpose = 'other'
             WHERE purpose IS NOT NULL
               AND purpose NOT IN ({$allowedList})
        ");
        // Empty / NULL → 'other' so the new column's NOT NULL-ish
        // default holds.
        DB::statement("
            UPDATE payment_types
               SET purpose = 'other'
             WHERE purpose IS NULL OR purpose = ''
        ");

        // ALTER the column. Use raw SQL because Laravel's Blueprint
        // doesn't expose ENUM-with-default neatly on every driver.
        $enumList = "'" . implode("','", self::ALLOWED_PURPOSES) . "'";
        DB::statement("
            ALTER TABLE payment_types
            MODIFY COLUMN purpose ENUM({$enumList}) NOT NULL DEFAULT 'other'
        ");
    }

    public function down(): void
    {
        // Intentionally a no-op — see class doc.
    }

    /**
     * @return array{type:string,null:string,default:string|null}|null
     */
    private function describePurposeColumn(): ?array
    {
        $row = DB::selectOne("
            SELECT COLUMN_TYPE  AS type,
                   IS_NULLABLE  AS nullable,
                   COLUMN_DEFAULT AS default_value
              FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'payment_types'
               AND COLUMN_NAME  = 'purpose'
        ");
        if ($row === null) {
            return null;
        }
        return [
            'type'    => strtolower((string) $row->type),
            'null'    => strtolower((string) $row->nullable),
            'default' => $row->default_value,
        ];
    }

    private function isAlreadyProductionEnum(array $current): bool
    {
        if (! str_starts_with($current['type'], 'enum(')) {
            return false;
        }

        // Pull the ENUM's value list out of "enum('a','b','c')".
        preg_match("/^enum\\((.*)\\)$/", $current['type'], $m);
        if (! isset($m[1])) {
            return false;
        }
        $values = array_map(
            fn ($v) => trim($v, " '"),
            explode(',', $m[1])
        );
        sort($values);
        $expected = self::ALLOWED_PURPOSES;
        sort($expected);

        return $values === $expected;
    }
};
