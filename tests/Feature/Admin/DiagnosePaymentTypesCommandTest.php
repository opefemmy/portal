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
        $this->seedCanonicalRows();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('payment_types');
        parent::tearDown();
    }

    /**
     * Seed the three canonical-purpose rows so the applicant-flow
     * resolver section of the diagnostic has something to inspect.
     */
    private function seedCanonicalRows(): void
    {
        PaymentType::create([
            'name' => 'Application Form Fee',
            'code' => 'APP_FORM',
            'purpose' => PaymentType::PURPOSE_APPLICATION,
            'amount' => 5000,
            'audience' => PaymentType::AUDIENCE_BOTH,
            'is_active' => true,
            'requires_payment' => true,
            'payment_channel' => 'both',
        ]);
        PaymentType::create([
            'name' => 'Acceptance Fee',
            'code' => 'ACCEPT_FEE',
            'purpose' => PaymentType::PURPOSE_ACCEPTANCE,
            'amount' => 25000,
            'audience' => PaymentType::AUDIENCE_BOTH,
            'is_active' => true,
            'requires_payment' => true,
            'payment_channel' => 'both',
        ]);
        PaymentType::create([
            'name' => 'School Fees',
            'code' => 'SCHOOL_FEE',
            'purpose' => PaymentType::PURPOSE_SCHOOL_FEE,
            'amount' => 50000,
            'audience' => PaymentType::AUDIENCE_BOTH,
            'is_active' => true,
            'requires_payment' => true,
            'payment_channel' => 'both',
        ]);
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

    /**
     * The applicant sees "Payment type not configured. Please contact the
     * admissions office." when resolvePaymentType() returns null. The
     * diagnostic must surface this so the operator can fix the audience
     * / activation state without having to reproduce the applicant flow.
     */
    public function test_diagnostic_flags_payment_type_with_wrong_audience(): void
    {
        // Mark SCHOOL_FEE as audience=student. The applicant-flow audience
        // filter rejects anything that isn't 'both' or 'applicant', so the
        // applicant sees "Payment type not configured" when they try to
        // pay school fees.
        \App\Models\PaymentType::where('code', 'SCHOOL_FEE')
            ->update(['audience' => 'student']);

        $exit = $this->artisan('payment-types:diagnose')->expectsOutputToContain('NOT RESOLVED')
            ->expectsOutputToContain('audience=student')
            ->run();

        $this->assertSame(0, $exit);
    }

    /**
     * When a canonical-code row is INACTIVE, the applicant can't pay for
     * that purpose either. Diagnostic must flag it.
     */
    public function test_diagnostic_flags_inactive_canonical_payment_type(): void
    {
        \App\Models\PaymentType::where('code', 'SCHOOL_FEE')
            ->update(['is_active' => false]);

        // The diagnostic prints "active=NO" in the resolver row and
        // "INACTIVE" in the issues list — assert both to pin the
        // contract.
        $exit = $this->artisan('payment-types:diagnose')
            ->expectsOutputToContain('active=NO')
            ->expectsOutputToContain('INACTIVE')
            ->run();

        $this->assertSame(0, $exit);
    }

    /**
     * When the canonical-code row is missing entirely (e.g. someone
     * deleted it), the diagnostic must report it as missing.
     */
    public function test_diagnostic_flags_missing_canonical_payment_type(): void
    {
        // Wipe every row whose purpose OR code would match the resolver
        // candidate set, so the diagnostic must print "no PaymentType
        // row exists".
        \App\Models\PaymentType::whereIn('code', ['APP_FORM', 'ACCEPT_FEE', 'SCHOOL_FEE'])
            ->orWhereIn('purpose', [
                PaymentType::PURPOSE_APPLICATION,
                PaymentType::PURPOSE_ACCEPTANCE,
                PaymentType::PURPOSE_SCHOOL_FEE,
            ])
            ->delete();

        $exit = $this->artisan('payment-types:diagnose')
            ->expectsOutputToContain('NOT RESOLVED')
            ->expectsOutputToContain('no PaymentType row exists')
            ->run();

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