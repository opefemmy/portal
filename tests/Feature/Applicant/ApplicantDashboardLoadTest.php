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
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins that GET /applicant/dashboard returns 200 with the realistic
 * production-ish schema: applicant rows that have completed payments,
 * has a school/department/programme/session, has been admitted, has paid
 * acceptance but not yet compulsory, has student_id set after migration,
 * etc. The production 500 could be a missing column, ENUM mismatch, or a
 * null-deref — this test surfaces it locally with the same error class
 * and message Laravel would log on prod.
 *
 * If a 500 occurs in production but the test passes here, the test is
 * missing a code path; if a 500 occurs here, the test surfaces the
 * exact error in CI for triage.
 */
class ApplicantDashboardLoadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('local_governments');
        Schema::dropIfExists('states');
        Schema::dropIfExists('external_payments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('users');
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_dashboard_loads_for_an_admitted_applicant_with_acceptance_paid(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
        ]);

        $response = $this->actingAs($applicant->user)->get('/applicant/dashboard');
        $response->assertOk();
    }

    public function test_dashboard_loads_for_an_admitted_applicant_with_no_payments(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
            'compulsory_paid_at'  => null,
        ]);

        $response = $this->actingAs($applicant->user)->get('/applicant/dashboard');
        $response->assertOk();
    }

    public function test_dashboard_loads_with_payments_table_containing_completed_rows(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
        ]);

        $type = PaymentType::where('code', 'ACCEPT_FEE')->first();
        Payment::create([
            'payer_id'        => $applicant->id,
            'amount'          => 25000,
            'total_amount'    => 25000,
            'reference'       => 'TEST-REF-' . uniqid(),
            'gateway'         => 'paystack',
            'status'          => 'completed',
            'is_verified'     => true,
            'student_type'    => 'applicant',
            'payment_purpose' => PaymentType::PURPOSE_ACCEPTANCE,
            'fee_type'        => 'acceptance',
            'payment_date'    => now(),
        ]);

        $response = $this->actingAs($applicant->user)->get('/applicant/dashboard');
        $response->assertOk();
    }

    public function test_dashboard_loads_for_applicant_with_no_applicant_row_yet(): void
    {
        // No Applicant row — what happens when a fresh applicant hits dashboard?
        $user = User::create([
            'name' => 'Fresh Applicant',
            'email' => 'fresh_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', 'applicant')->value('id'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/applicant/dashboard');
        $response->assertOk();
    }

    public function test_dashboard_loads_for_pending_applicant(): void
    {
        $user = User::create([
            'name' => 'Pending Applicant',
            'email' => 'pending_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', 'applicant')->value('id'),
            'is_active' => true,
        ]);

        Applicant::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'application_number' => Applicant::generateApplicationNumber(),
            'status' => 'pending',
            'school_id' => School::first()->id,
            'department_id' => Department::first()->id,
            'programme_id' => Programme::first()->id,
            'session_id' => AcademicSession::first()->id,
        ]);

        $response = $this->actingAs($user)->get('/applicant/dashboard');
        $response->assertOk();
    }

    public function test_dashboard_loads_for_draft_applicant(): void
    {
        $user = User::create([
            'name' => 'Draft Applicant',
            'email' => 'draft_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', 'applicant')->value('id'),
            'is_active' => true,
        ]);

        Applicant::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'application_number' => Applicant::generateApplicationNumber(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->get('/applicant/dashboard');
        $response->assertOk();
    }

    public function test_dashboard_loads_for_admitted_applicant_who_has_paid_compulsory(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
            'compulsory_paid_at'  => now(),
        ]);

        $response = $this->actingAs($applicant->user)->get('/applicant/dashboard');
        $response->assertOk();
    }

    public function test_dashboard_loads_for_rejected_applicant(): void
    {
        $user = User::create([
            'name' => 'Rejected Applicant',
            'email' => 'rejected_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', 'applicant')->value('id'),
            'is_active' => true,
        ]);

        Applicant::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'application_number' => Applicant::generateApplicationNumber(),
            'status' => 'rejected',
            'school_id' => School::first()->id,
            'department_id' => Department::first()->id,
            'programme_id' => Programme::first()->id,
            'session_id' => AcademicSession::first()->id,
        ]);

        $response = $this->actingAs($user)->get('/applicant/dashboard');
        $response->assertOk();
    }

    public function test_dashboard_loads_for_migrated_applicant_with_payment_rows(): void
    {
        // The legacy pre-fix state — a completed compulsory payment
        // exists but applicants.compulsory_paid_at is NULL. This is
        // the case that needs the Sync button on the dashboard.
        $applicant = $this->makeAdmittedApplicant();
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
            // compulsory_paid_at NULL on purpose
        ]);

        // Has a completed compulsory payment in payments table.
        Payment::create([
            'payer_id'        => $applicant->id,
            'amount'          => 30000,
            'total_amount'    => 30000,
            'reference'       => 'LEGACY-' . uniqid(),
            'gateway'         => 'paystack',
            'status'          => 'completed',
            'is_verified'     => true,
            'student_type'    => 'applicant',
            'payment_purpose' => PaymentType::PURPOSE_COMPULSORY,
            'fee_type'        => 'school_fees',
            'payment_date'    => now(),
        ]);

        $response = $this->actingAs($applicant->user)->get('/applicant/dashboard');
        $response->assertOk();
        $response->assertSee('Sync Payment Status', false);
    }

    public function test_dashboard_loads_with_payments_containing_null_payment_date(): void
    {
        // Sometimes the legacy payments have NULL payment_date — the
        // dashboard's transactionHistory() iterates them.
        $applicant = $this->makeAdmittedApplicant();
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
        ]);

        Payment::create([
            'payer_id'        => $applicant->id,
            'amount'          => 25000,
            'total_amount'    => 25000,
            'reference'       => 'NULLDATE-' . uniqid(),
            'gateway'         => 'paystack',
            'status'          => 'completed',
            'is_verified'     => true,
            'student_type'    => 'applicant',
            'payment_purpose' => PaymentType::PURPOSE_ACCEPTANCE,
            'fee_type'        => 'acceptance',
            'payment_date'    => null,
        ]);

        $response = $this->actingAs($applicant->user)->get('/applicant/dashboard');
        $response->assertOk();
    }

    /* --- helpers --- */

    private function makeAdmittedApplicant(): Applicant
    {
        $user = User::create([
            'name' => 'Admitted Applicant',
            'email' => 'admitted_' . uniqid() . '@example.com',
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
            $t->string('surname')->nullable();
            $t->string('first_name')->nullable();
            $t->string('middle_name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('status', 20)->default('pending');
            $t->foreignId('school_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable()->constrained();
            $t->foreignId('programme_id')->nullable()->constrained();
            $t->foreignId('session_id')->nullable()->constrained();
            $t->string('mode_of_study')->nullable();
            $t->string('entry_level')->nullable();
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
            $t->integer('level')->nullable();
            $t->string('screening_status', 20)->default('pending');
            $t->text('rejection_reason')->nullable();
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
            $t->enum('audience', ['applicant', 'student', 'both'])->default('both');
            $t->timestamps();
        });
        Schema::create('payments', function ($t) {
            $t->id();
            $t->foreignId('student_id')->nullable();
            $t->foreignId('fee_id')->nullable();
            $t->decimal('amount', 12, 2);
            $t->decimal('total_amount', 12, 2)->nullable();
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
            $t->string('payer_name')->nullable();
            $t->string('payer_email')->nullable();
            $t->string('payer_phone')->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->text('payment_details')->nullable();
            $t->timestamps();
        });
        Schema::create('system_settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('external_payments', function ($t) {
            $t->id();
            $t->foreignId('applicant_id')->nullable();
            $t->foreignId('payment_type_id')->nullable();
            $t->string('reference')->nullable();
            $t->string('status', 20)->default('pending');
            $t->decimal('amount', 12, 2)->default(0);
            $t->string('gateway')->nullable();
            $t->string('payment_method')->nullable();
            $t->string('transaction_id')->nullable();
            $t->text('payment_details')->nullable();
            $t->dateTime('paid_at')->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->dateTime('validated_at')->nullable();
            $t->string('payment_channel')->nullable();
            $t->string('applicant_name')->nullable();
            $t->string('email')->nullable();
            $t->string('payment_status', 20)->default('pending');
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('states', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->timestamps();
        });
        Schema::create('local_governments', function ($t) {
            $t->id();
            $t->string('name');
            $t->foreignId('state_id')->constrained();
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
            'name' => 'Application Form Fee',
            'code' => 'APP_FORM',
            'purpose' => PaymentType::PURPOSE_APPLICATION,
            'amount' => 5000,
            'audience' => PaymentType::AUDIENCE_APPLICANT,
            'is_active' => true,
            'requires_payment' => true,
            'priority' => 1,
        ]);
        PaymentType::create([
            'name' => 'Acceptance Fee',
            'code' => 'ACCEPT_FEE',
            'purpose' => PaymentType::PURPOSE_ACCEPTANCE,
            'amount' => 25000,
            'audience' => PaymentType::AUDIENCE_APPLICANT,
            'is_active' => true,
            'requires_payment' => true,
            'priority' => 2,
        ]);
        PaymentType::create([
            'name' => 'Compulsory Fee',
            'code' => 'COMP_FEE',
            'purpose' => PaymentType::PURPOSE_COMPULSORY,
            'amount' => 30000,
            'audience' => PaymentType::AUDIENCE_APPLICANT,
            'is_active' => true,
            'requires_payment' => true,
            'priority' => 3,
        ]);
        PaymentType::create([
            'name' => 'School Fees',
            'code' => 'SCHOOL_FEE',
            'purpose' => PaymentType::PURPOSE_SCHOOL_FEE_PRODUCTION,
            'amount' => 50000,
            'audience' => PaymentType::AUDIENCE_STUDENT,
            'is_active' => true,
            'requires_payment' => true,
            'priority' => 4,
        ]);
    }
}