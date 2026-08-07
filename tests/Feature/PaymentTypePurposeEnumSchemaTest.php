<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pin the production contract on `payment_types.purpose`.
 *
 * The repo migration 2026_07_24_000001_add_purpose_to_payment_types_table
 * declares `purpose` as a plain `string(30)`. Production has long since
 * converted it to an ENUM with 8 specific values. The application
 * code (PaymentType::allowedPurposes) and admin controllers write
 * values like 'compulsory' and 'school_fees' that the production ENUM
 * accepts but a plain string column would either silently truncate
 * (with strict_sql_mode off) or store as-is (with it on).
 *
 * The 2026_08_07_000003 migration aligns local databases to the
 * production ENUM. This test guards that contract on the live MySQL
 * connection only — SQLite (used by the rest of the suite) has no
 * ENUM type so we skip when the driver is sqlite.
 *
 * If a future developer adds a 9th purpose value, this test forces
 * them to update PaymentType::allowedPurposes() AND alter the
 * production ENUM AND update this assertion.
 */
class PaymentTypePurposeEnumSchemaTest extends TestCase
{
    public function test_payment_types_purpose_is_enum_with_production_values(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('ENUM is a MySQL concept — this pin is for the live MySQL schema only.');
        }

        if (! Schema::hasTable('payment_types')) {
            $this->markTestSkipped('payment_types table does not exist on this connection.');
        }

        $row = DB::selectOne("
            SELECT COLUMN_TYPE AS type
              FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'payment_types'
               AND COLUMN_NAME  = 'purpose'
        ");

        $this->assertNotNull($row, 'payment_types.purpose column does not exist.');

        $type = strtolower((string) $row->type);
        $this->assertStringStartsWith(
            'enum(',
            $type,
            'payment_types.purpose must be an ENUM on production — got: ' . $type
        );

        // The 8 production values, kept in sync with
        // PaymentType::allowedPurposes().
        $expected = [
            'application',
            'acceptance',
            'school_fees',
            'hostel',
            'library',
            'registration',
            'other',
            'compulsory',
        ];

        preg_match('/^enum\((.*)\)$/', $type, $m);
        $actual = array_map(
            fn ($v) => trim($v, " '"),
            explode(',', $m[1])
        );
        sort($actual);
        $expectedSorted = $expected;
        sort($expectedSorted);

        $this->assertSame(
            $expectedSorted,
            $actual,
            'payment_types.purpose ENUM must accept the 8 production values. '
            . 'If you added a 9th purpose, update PaymentType::allowedPurposes() '
            . 'AND this assertion AND the production ENUM ALTER.'
        );
    }
}
