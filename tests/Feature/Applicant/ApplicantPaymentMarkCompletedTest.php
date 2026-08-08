<?php

namespace Tests\Feature\Applicant;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\User;
use App\Services\ApplicantPaymentService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the applicant-side markCompleted() side-effect contract so a
 * payment row that was already 'completed' (e.g. the test handler's
 * fallback path created it with status='completed' before calling
 * markCompleted) still triggers the per-purpose *_paid_at stamp and
 * the applicant→student migration.
 *
 * Regression: the test handler's fallback path creates a Payment row
 * with status='completed' so the demo simulator returns an "ok"
 * status without calling out to Paystack. It then calls
 * markCompleted($payment, ...) expecting applyApplicantSideEffects
 * to run. Pre-fix, markCompleted had an early-return:
 *
 *   if ($payment->status === 'completed') {
 *       return $payment; // idempotent
 *   }
 *
 * The comment claimed it was idempotent on the side effects, but the
 * code short-circuited the entire body — so the applicant.compulsory_paid_at
 * stamp was never written, the migration never ran, and the dashboard
 * kept showing "Compulsory Fee: Locked" even though the Payment row
 * existed with status='completed'.
 *
 * Apply this test to markCompleted directly: pass in a pre-completed
 * row and assert the applicant side effects land.
 */
class ApplicantPaymentMarkCompletedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('students');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('users');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('system_settings');
        parent::tearDown();
    }

    public function test_mark_completed_on_precompleted_row_stamps_compulsory_paid_at(): void
    {
        $applicant = $this->makeAdmittedApplicant();

        // Pre-create a Payment row exactly like the test handler's
        // fallback path does (status='completed', payer_id set, but
        // applicant.compulsory_paid_at NOT yet stamped).
        $payment = Payment::create([
            'amount' => 30000,
            'reference' => 'TEST-PRECOMPL-' . uniqid(),
            'payment_ref' => 'TEST-PRECOMPL-' . uniqid(),
            'transaction_id' => 'TEST-PRECOMPL-' . uniqid(),
            'gateway' => 'test',
            'payment_method' => 'test',
            'status' => 'completed',
            'is_verified' => true,
            'student_type' => 'applicant',
            'payment_purpose' => PaymentType::PURPOSE_COMPULSORY,
            'fee_type' => app(ApplicantPaymentService::class)->feeTypeFor(PaymentType::PURPOSE_COMPULSORY),
            'payer_id' => $applicant->id,
            'payment_date' => now(),
        ]);

        $this->assertNull(
            $applicant->fresh()->compulsory_paid_at,
            'Sanity: applicant has not paid compulsory yet.'
        );

        // Now call markCompleted on the pre-completed row, the way the
        // test handler's fallback path does.
        app(ApplicantPaymentService::class)->markCompleted($payment, [
            'test_mode' => true,
            'simulated' => true,
            'via' => 'test_fallback',
        ]);

        $fresh = $applicant->fresh();
        $this->assertNotNull(
            $fresh->compulsory_paid_at,
            'markCompleted() on a pre-completed payment row did NOT stamp applicant.compulsory_paid_at. '
            . 'The early-return guard is breaking the test handler\'s fallback path.'
        );
    }

    public function test_mark_completed_on_precompleted_row_is_idempotent_on_second_call(): void
    {
        // Once the side effects have run, calling markCompleted again
        // must not clobber the existing stamp. Pin that the per-purpose
        // *_paid_at column uses the OR-existing pattern (only set if
        // null) so a Paystack callback that arrives after a manual
        // recordManual() doesn't reset the timestamp.
        $applicant = $this->makeAdmittedApplicant();
        $first = now()->subHour();

        $payment = Payment::create([
            'amount' => 30000,
            'reference' => 'TEST-IDEMP-' . uniqid(),
            'payment_ref' => 'TEST-IDEMP-' . uniqid(),
            'transaction_id' => 'TEST-IDEMP-' . uniqid(),
            'gateway' => 'test',
            'payment_method' => 'test',
            'status' => 'completed',
            'is_verified' => true,
            'student_type' => 'applicant',
            'payment_purpose' => PaymentType::PURPOSE_COMPULSORY,
            'fee_type' => app(ApplicantPaymentService::class)->feeTypeFor(PaymentType::PURPOSE_COMPULSORY),
            'payer_id' => $applicant->id,
            'payment_date' => $first,
        ]);

        $svc = app(ApplicantPaymentService::class);
        $svc->markCompleted($payment, ['first_call' => true]);
        $stamp1 = $applicant->fresh()->compulsory_paid_at;

        // Second call. The stamp should still be the original.
        $svc->markCompleted($payment, ['second_call' => true]);
        $stamp2 = $applicant->fresh()->compulsory_paid_at;

        $this->assertNotNull($stamp1);
        $this->assertEquals(
            $stamp1->toIso8601String(),
            $stamp2->toIso8601String(),
            'Second markCompleted() call moved the compulsory_paid_at stamp — the per-purpose "OR existing" guard is broken.'
        );
    }

    /* --- helpers --- */

    private function makeAdmittedApplicant(): Applicant
    {
        $user = User::create([
            'name' => 'Compulsory Test',
            'email' => 'comp_test_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', 'applicant')->value('id'),
            'is_active' => true,
        ]);

        return Applicant::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'application_number' => Applicant::generateApplicationNumber(),
            'status' => 'admitted',
            'school_id' => School::first()->id,
            'department_id' => Department::first()->id,
            'programme_id' => Programme::first()->id,
            'session_id' => AcademicSession::first()->id,
        ]);
    }

    private function buildSchema(): void
    {
        Schema::create('roles', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('schools', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->timestamps();
        });
        Schema::create('departments', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->foreignId('school_id')->constrained();
            $t->timestamps();
        });
        Schema::create('programmes', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->foreignId('department_id')->constrained();
            $t->timestamps();
        });
        Schema::create('sessions', function ($t) {
            $t->id();
            $t->string('name');
            $t->boolean('is_current')->default(false);
            $t->timestamps();
        });
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->foreignId('role_id')->nullable()->constrained();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('applicants', function ($t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('application_number')->unique();
            $t->string('email')->nullable();
            $t->string('status', 20)->default('pending');
            $t->foreignId('school_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable()->constrained();
            $t->foreignId('programme_id')->nullable()->constrained();
            $t->foreignId('session_id')->nullable()->constrained();
            $t->string('entry_level')->nullable();
            $t->unsignedBigInteger('state_id')->nullable();
            $t->unsignedBigInteger('lga_id')->nullable();
            $t->unsignedBigInteger('nationality_id')->nullable();
            $t->string('payment_status', 20)->default('pending');
            $t->string('payment_ref')->nullable();
            $t->string('payment_transaction_id')->nullable();
            $t->decimal('payment_amount', 10, 2)->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->dateTime('application_paid_at')->nullable();
            $t->dateTime('acceptance_paid_at')->nullable();
            $t->dateTime('compulsory_paid_at')->nullable();
            $t->dateTime('migrated_to_student_at')->nullable();
            $t->foreignId('student_id')->nullable();
            $t->string('matric_number')->nullable();
            $t->timestamps();
        });
        Schema::create('payment_types', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->string('purpose')->nullable();
            $t->decimal('amount', 12, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('requires_payment')->default(true);
            $t->string('payment_channel')->nullable();
            $t->integer('priority')->default(0);
            $t->string('audience', 20)->default('both');
            $t->timestamps();
        });
        Schema::create('payments', function ($t) {
            $t->id();
            $t->foreignId('student_id')->nullable();
            $t->foreignId('fee_id')->nullable();
            $t->decimal('amount', 12, 2);
            $t->decimal('total_amount', 12, 2)->default(0);
            $t->decimal('portal_charge', 12, 2)->default(0);
            $t->integer('percent_paid')->default(0);
            $t->string('installment_label', 20)->default('full');
            $t->string('reference')->nullable();
            $t->string('payment_ref')->nullable();
            $t->string('transaction_id')->nullable();
            $t->string('gateway')->nullable();
            $t->string('payment_method')->nullable();
            $t->string('status', 20)->default('pending');
            $t->boolean('is_verified')->default(false);
            $t->string('student_type')->nullable();
            $t->string('payment_purpose')->nullable();
            $t->string('fee_type')->nullable();
            $t->foreignId('payer_id')->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->text('payment_details')->nullable();
            $t->timestamps();
        });

        // Minimal students table for the applicant→student migration
        // triggered by applyApplicantSideEffects on the compulsory path.
        // Mirrors the production columns that migrateApplicantToStudent
        // writes (state_id, lga_id, nationality_id, entry_level etc.).
        Schema::create('students', function ($t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('matric_number')->nullable();
            $t->foreignId('school_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable()->constrained();
            $t->foreignId('programme_id')->nullable()->constrained();
            $t->foreignId('session_id')->nullable()->constrained();
            $t->integer('level')->nullable();
            $t->string('status', 20)->default('active');
            $t->unsignedBigInteger('state_id')->nullable();
            $t->unsignedBigInteger('lga_id')->nullable();
            $t->unsignedBigInteger('nationality_id')->nullable();
            $t->boolean('from_application')->default(false);
            $t->foreignId('applicant_id')->nullable();
            $t->timestamps();
        });
        // migrateApplicantToStudent → MatricNumberService reads the
        // institution prefix from SystemSetting. An empty table is
        // enough; the select just returns null and falls back to the
        // default prefix.
        Schema::create('system_settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Applicant', 'slug' => 'applicant']);
        $school = School::create(['name' => 'Test School', 'code' => 'TST']);
        $dept = Department::create(['name' => 'Test Dept', 'code' => 'TSTD', 'school_id' => $school->id]);
        Programme::create(['name' => 'Test Prog', 'code' => 'TSTP', 'department_id' => $dept->id]);
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        PaymentType::create([
            'name' => 'Compulsory Fee',
            'code' => 'COMP_FEE',
            'purpose' => PaymentType::PURPOSE_COMPULSORY,
            'amount' => 30000,
            'audience' => PaymentType::AUDIENCE_APPLICANT,
        ]);
    }
}
