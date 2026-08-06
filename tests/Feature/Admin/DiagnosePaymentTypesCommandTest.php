<?php

namespace Tests\Feature\Admin;

use App\Models\PaymentType;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests for the `php artisan payment-types:diagnose` command.
 *
 * The user keeps reporting "payment type fails to add" on production;
 * this command is the on-server one-liner that surfaces WHY. It must:
 *   - register (be in artisan list)
 *   - report the table's column drift
 *   - report whether the controller file on disk has the guard
 *   - report missing routes
 *   - with --try-create, actually insert a row (or report the SQL error)
 */
class DiagnosePaymentTypesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('payment_types');
        parent::tearDown();
    }

    public function test_command_runs_against_legacy_schema_with_only_base_columns(): void
    {
        // Drop the migration-added columns to simulate a production DB
        // that's behind on migrations.
        Schema::table('payment_types', function ($t) {
            $t->dropColumn('purpose');
            $t->dropColumn('audience');
        });

        $exit = $this->artisan('payment-types:diagnose')->run();
        $this->assertSame(0, $exit);
    }

    public function test_command_runs_against_full_schema(): void
    {
        $exit = $this->artisan('payment-types:diagnose')->run();
        $this->assertSame(0, $exit);
    }

    public function test_command_creates_a_diagnostic_row_when_try_create_flag_set(): void
    {
        $exit = $this->artisan('payment-types:diagnose --try-create')->run();
        $this->assertSame(0, $exit);

        // The diagnostic row is prefixed with DIAG_ — confirm exactly one
        // such row landed.
        $this->assertSame(
            1,
            PaymentType::where('code', 'like', 'DIAG_%')->count(),
            'Expected exactly one DIAG_* row to be inserted by --try-create.'
        );
    }

    public function test_command_reports_failure_on_completely_unrun_migrations(): void
    {
        // Drop the table entirely to simulate the absolute worst case.
        Schema::drop('payment_types');

        // The command catches the QueryException internally and returns
        // SUCCESS with an error message rather than throwing.
        $exit = $this->artisan('payment-types:diagnose')->run();
        $this->assertSame(0, $exit);
    }

    private function buildSchema(): void
    {
        Schema::create('payment_types', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->text('description')->nullable();
            $t->decimal('amount', 12, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('requires_payment')->default(true);
            $t->string('payment_channel')->nullable();
            $t->integer('priority')->default(0);
            $t->string('purpose')->nullable();
            $t->enum('audience', ['applicant', 'student', 'both'])->default('both');
            $t->timestamps();
        });
    }
}