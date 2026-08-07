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
use App\Models\Student;
use App\Models\User;
use App\Services\ApplicantPaymentService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the applicant-side "payment already recorded but dashboard still
 * shows Locked" regression and the explicit Transfer-to-Student-Portal
 * button contract.
 *
 * User complaint: "You have already paid the Compulsory fee. but
 * compulsory fee is still on lock as it should show paid, and also give
 * me the right to transfer to the student portal".
 *
 * Root cause: a payment that landed before commit 8ad089b1 has
 * status='completed' but applicants.compulsory_paid_at was never stamped,
 * so the dashboard still shows Locked and the applicant→student migration
 * never fired. This test:
 *
 *   1. Pre-creates a completed compulsory payment row directly (bypassing
 *      markCompleted) to simulate a legacy pre-fix state.
 *   2. Asserts the dashboard shows Locked (the buggy state).
 *   3. Posts to /applicant/payment/sync and asserts the stamp lands.
 *   4. Posts to /applicant/payment/transfer and asserts the user lands on
 *      student.dashboard with a fresh Student row.
 */
class SyncApplicantPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('external_payments');
        Schema::dropIfExists('students');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        parent::tearDown();
    }

    public function test_dashboard_shows_locked_for_legacy_pre_fix_payment(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        $this->createCompletedCompulsoryPayment($applicant);

        // The Payment row exists with status='completed' (the buggy
        // pre-fix state) but applicants.compulsory_paid_at is still NULL.
        $this->assertNull(
            $applicant->fresh()->compulsory_paid_at,
            'Sanity: applicant.compulsory_paid_at is NULL — legacy pre-fix state.'
        );

        // Render the dashboard view directly to see the actual error
        // (Laravel's exception handler masks it with "View [errors.500]
        // not found" because no errors.500.blade.php exists).
        $externalPayment = null;
        try {
            $body = view('applicant.dashboard', compact('applicant', 'externalPayment'))->render();
        } catch (\Throwable $e) {
            $this->fail('Dashboard render threw: ' . get_class($e) . ' — ' . $e->getMessage()
                . ' at ' . $e->getFile() . ':' . $e->getLine()
                . "\ntrace:\n" . $e->getTraceAsString());
        }

        $this->assertStringContainsString('Locked', $body);
        // The Sync button must be visible — this is the new affordance.
        $this->assertStringContainsString('Sync Payment Status', $body);
    }

    public function test_sync_payment_route_backfills_compulsory_paid_at(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        $payment = $this->createCompletedCompulsoryPayment($applicant);

        $this->assertNull($applicant->fresh()->compulsory_paid_at);

        $response = $this->actingAs($applicant->user)
            ->post('/applicant/payment/sync');

        // Redirect back to dashboard.
        $this->assertContains($response->getStatusCode(), [200, 302]);

        $fresh = $applicant->fresh();
        $this->assertNotNull(
            $fresh->compulsory_paid_at,
            'syncPaymentSideEffects must stamp applicants.compulsory_paid_at'
        );

        // The markCompleted path also runs migrateApplicantToStudent for
        // the compulsory purpose — verify a Student row was created.
        $student = Student::where('user_id', $applicant->user_id)->first();
        $this->assertNotNull(
            $student,
            'syncPaymentSideEffects must trigger migrateApplicantToStudent — the Student row should exist.'
        );
    }

    public function test_sync_payment_route_is_idempotent_on_second_call(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        $this->createCompletedCompulsoryPayment($applicant);

        $this->actingAs($applicant->user)->post('/applicant/payment/sync');
        $firstStamp = $applicant->fresh()->compulsory_paid_at;
        $this->assertNotNull($firstStamp);

        // Second sync must NOT move the stamp.
        $this->actingAs($applicant->user)->post('/applicant/payment/sync');
        $secondStamp = $applicant->fresh()->compulsory_paid_at;

        $this->assertEquals(
            $firstStamp->toIso8601String(),
            $secondStamp->toIso8601String(),
            'Second sync must not move compulsory_paid_at — the per-purpose OR-existing guard is broken.'
        );
    }

    public function test_transfer_to_student_portal_route_promotes_user_and_redirects(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        $this->createCompletedCompulsoryPayment($applicant);

        // Before transfer, user has role=applicant.
        $this->assertEquals(
            Role::where('slug', 'applicant')->value('id'),
            $applicant->user->role_id
        );

        $response = $this->actingAs($applicant->user)
            ->post('/applicant/payment/transfer');

        $this->assertContains($response->getStatusCode(), [200, 302]);

        // After transfer, user must have role=student.
        $freshUser = $applicant->user->fresh();
        $this->assertEquals(
            Role::where('slug', 'student')->value('id'),
            $freshUser->role_id,
            'transferToStudentPortal must promote the user to role=student.'
        );

        // A Student row must exist for them.
        $student = Student::where('user_id', $applicant->user_id)->first();
        $this->assertNotNull($student);
        $this->assertEquals($applicant->user_id, $student->user_id);
    }

    public function test_transfer_blocked_when_no_completed_compulsory_payment(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        // No compulsory payment — just an applicant row.

        $response = $this->actingAs($applicant->user)
            ->post('/applicant/payment/transfer');

        // Should bounce back, not 500 / not 403.
        $this->assertContains($response->getStatusCode(), [200, 302]);

        // User must still be applicant (no promotion).
        $this->assertEquals(
            Role::where('slug', 'applicant')->value('id'),
            $applicant->user->fresh()->role_id,
            'User must NOT be promoted to role=student when there is no completed compulsory payment.'
        );
    }

    public function test_dashboard_renders_transfer_button_when_migrated(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        $this->createCompletedCompulsoryPayment($applicant);

        // Sync to drive the migration.
        $this->actingAs($applicant->user)->post('/applicant/payment/sync');

        $applicant = $applicant->fresh();
        $this->assertTrue(
            $applicant->isMigrated(),
            'Sanity: applicant should be migrated after sync.'
        );

        // Re-authenticate so the auth provider sees the role change.
        $this->actingAs($applicant->user->fresh());

        $response = $this->actingAs($applicant->user->fresh())
            ->get('/applicant/dashboard');

        $response->assertOk();
        $this->assertStringContainsString(
            'Go to Student Portal',
            $response->getContent(),
            'Dashboard must show a "Go to Student Portal" button once the applicant is migrated.'
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
            'state_id' => 1,
            'lga_id' => 1,
            'nationality_id' => 1,
            'entry_level' => 1,
        ]);
    }

    /**
     * Simulate the legacy pre-fix state: a Payment row exists with
     * status='completed' but applicants.compulsory_paid_at is NULL
     * because applyApplicantSideEffects never ran on it.
     */
    private function createCompletedCompulsoryPayment(Applicant $applicant): Payment
    {
        return Payment::create([
            'amount' => 30000,
            'reference' => 'LEGACY-' . uniqid(),
            'payment_ref' => 'LEGACY-' . uniqid(),
            'transaction_id' => 'LEGACY-' . uniqid(),
            'gateway' => 'paystack',
            'payment_method' => 'card',
            'status' => 'completed',
            'is_verified' => true,
            'student_type' => 'applicant',
            'payment_purpose' => PaymentType::PURPOSE_COMPULSORY,
            'fee_type' => app(ApplicantPaymentService::class)->feeTypeFor(PaymentType::PURPOSE_COMPULSORY),
            'payer_id' => $applicant->id,
            'payment_date' => now(),
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
        // Minimal external_payments table — Applicant::transactionHistory()
        // queries this. The view's "Payment History" panel calls it
        // directly so we need the table to exist, even if empty.
        Schema::create('external_payments', function ($t) {
            $t->id();
            $t->string('transaction_id')->nullable()->unique();
            $t->string('applicant_name')->nullable();
            $t->string('email')->nullable();
            $t->decimal('amount', 12, 2)->default(0);
            $t->dateTime('payment_date')->nullable();
            $t->string('payment_status', 20)->default('pending');
            $t->string('payment_channel')->nullable();
            $t->string('description')->nullable();
            $t->unsignedBigInteger('applicant_id')->nullable();
            $t->boolean('is_used')->default(false);
            $t->unsignedBigInteger('imported_by')->nullable();
            $t->unsignedBigInteger('validated_by')->nullable();
            $t->dateTime('validated_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Applicant', 'slug' => 'applicant']);
        Role::create(['name' => 'Student',   'slug' => 'student']);
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