<?php

namespace Tests\Feature\Student;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Fee;
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
 * Pins the contract that an applicant's pre-migration payment rows
 * (application / acceptance / compulsory fees) appear in the student's
 * payment history after ApplicantPaymentService::migrateApplicantToStudent.
 *
 * User complaint: "move all applicant payments, starting from
 * application fee, acceptance fee, compulsory fee payments to students
 * payment history after migration".
 *
 * Pre-fix: the student-side history view ran
 * `Payment::where('student_id', $student->id)` and missed every applicant-
 * side payment row (created with `student_id = null` and `payer_id =
 * applicant.id`). The fix back-fills `student_id` on those rows inside
 * `migrateApplicantToStudent()`, both for fresh migrations and for
 * pre-existing migrated students (the early-return path).
 *
 * The view renders those rows via a polymorphic
 * `fee → paymentType → payment_purpose` fallback — see
 * `resources/views/student/payments.blade.php`.
 */
class ApplicantPaymentsAfterMigrationTest extends TestCase
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
        Schema::dropIfExists('fees');
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('users');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('payment_gateways');
        parent::tearDown();
    }

    public function test_migration_relinks_applicant_payments_to_student(): void
    {
        $applicant = $this->makeAdmittedApplicant();

        // Pre-create three completed applicant-side payment rows with
        // student_id=NULL (the shape the production flow produces before
        // migration).
        $appType = PaymentType::where('purpose', PaymentType::PURPOSE_APPLICATION)->first();
        $accType = PaymentType::where('purpose', PaymentType::PURPOSE_ACCEPTANCE)->first();
        $compType = PaymentType::where('purpose', PaymentType::PURPOSE_COMPULSORY)->first();

        $appPayment  = $this->makeApplicantPayment($applicant, $appType,  5000,  'APP-1');
        $accPayment  = $this->makeApplicantPayment($applicant, $accType, 25000, 'ACC-1');
        $compPayment = $this->makeApplicantPayment($applicant, $compType, 30000, 'CMP-1');

        // Sanity: student_id is NULL on each — the pre-migration state.
        $this->assertNull($appPayment->fresh()->student_id);
        $this->assertNull($accPayment->fresh()->student_id);
        $this->assertNull($compPayment->fresh()->student_id);

        // Stamp the applicant-side timestamps the migration expects and
        // run the migration directly.
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
            'compulsory_paid_at'  => now(),
        ]);
        app(ApplicantPaymentService::class)->migrateApplicantToStudent($applicant->fresh());

        $student = Student::where('user_id', $applicant->user_id)->first();
        $this->assertNotNull($student, 'Migration must create a Student row.');

        $this->assertEquals(
            $student->id,
            $appPayment->fresh()->student_id,
            'Application payment must be relinked to the new student on migration.'
        );
        $this->assertEquals(
            $student->id,
            $accPayment->fresh()->student_id,
            'Acceptance payment must be relinked to the new student on migration.'
        );
        $this->assertEquals(
            $student->id,
            $compPayment->fresh()->student_id,
            'Compulsory payment must be relinked to the new student on migration.'
        );

        $this->assertEquals(
            3,
            Payment::where('student_id', $student->id)->count(),
            'The student-side history query must find all 3 applicant-side rows after back-fill.'
        );
    }

    public function test_migration_backfill_is_idempotent_on_existing_student(): void
    {
        $applicant = $this->makeAdmittedApplicant();

        $appType  = PaymentType::where('purpose', PaymentType::PURPOSE_APPLICATION)->first();
        $payment  = $this->makeApplicantPayment($applicant, $appType, 5000, 'APP-IDEMPOTENT');

        // Pre-existing Student row (e.g. legacy migration from before
        // this fix shipped). The early-return path should still run the
        // back-fill.
        $student = Student::create([
            'user_id'        => $applicant->user_id,
            'matric_number'  => 'PRE/MIG/001',
            'school_id'      => $applicant->school_id,
            'department_id'  => $applicant->department_id,
            'programme_id'   => $applicant->programme_id,
            'session_id'     => $applicant->session_id,
            'level'          => 1,
            'status'         => 'active',
            'from_application' => true,
            'applicant_id'   => $applicant->id,
        ]);
        $applicant->update(['student_id' => $student->id]);

        app(ApplicantPaymentService::class)->migrateApplicantToStudent($applicant->fresh());

        $this->assertEquals(
            $student->id,
            $payment->fresh()->student_id,
            'Existing-student migration path must still back-fill applicant-side payments.'
        );

        // A second call must NOT touch the row again (idempotency).
        app(ApplicantPaymentService::class)->migrateApplicantToStudent($applicant->fresh());
        $this->assertEquals(
            $student->id,
            $payment->fresh()->student_id,
            'Second migration call must not change student_id.'
        );
    }

    public function test_student_history_renders_applicant_payment_via_payment_type_label(): void
    {
        // The view must render the applicant-side Payment row using the
        // PaymentType.display_label (school-fee catalogue link), not
        // crash when the Fee relation is null.
        $applicant = $this->makeAdmittedApplicant();
        $compType  = PaymentType::where('purpose', PaymentType::PURPOSE_COMPULSORY)->first();

        $payment = $this->makeApplicantPayment($applicant, $compType, 30000, 'CMP-RENDER');
        // Back-link directly so we don't depend on the migration flow
        // (which is covered by the previous test).
        $student = Student::create([
            'user_id'        => $applicant->user_id,
            'matric_number'  => 'STU/RENDER/001',
            'school_id'      => $applicant->school_id,
            'department_id'  => $applicant->department_id,
            'programme_id'   => $applicant->programme_id,
            'session_id'     => $applicant->session_id,
            'level'          => 1,
            'status'         => 'active',
            'from_application' => true,
            'applicant_id'   => $applicant->id,
        ]);
        $payment->update(['student_id' => $student->id]);

        $response = $this->actingAs($applicant->user)
            ->get('/student/payments');

        $response->assertOk();
        $body = $response->getContent();

        // Dump body for debugging if assertions fail.
        if (!str_contains($body, $compType->display_label ?? 'NEVER')
            && !str_contains($body, 'Compulsory')) {
            file_put_contents(
                sys_get_temp_dir() . '/rendered_payments.html',
                $body
            );
        }

        // The view falls back through fee → paymentType→display_label →
        // payment_purpose. For our compulsory test row, the rendered
        // value is the humanised display_label "Compulsory".
        $this->assertStringContainsString(
            'Compulsory',
            $body,
            'Student payment history must render the PaymentType.display_label ("Compulsory") for applicant-side rows.'
        );
        $this->assertStringContainsString(
            'CMP-RENDER',
            $body,
            'The applicant-side payment reference must appear in the student history.'
        );
    }

    public function test_student_history_renders_payment_for_null_fee_id_row(): void
    {
        // Legacy / edge case: a Payment row with fee_id=NULL — both the
        // Fee and PaymentType relations return null. The view must
        // fall back to payment_purpose and not throw.
        $applicant = $this->makeAdmittedApplicant();
        $payment = Payment::create([
            'student_id'      => null,
            'fee_id'          => null,
            'amount'          => 5000,
            'total_amount'    => 5000,
            'reference'       => 'LEGACY-NULL-FEE',
            'payment_ref'     => 'LEGACY-NULL-FEE',
            'transaction_id'  => 'LEGACY-NULL-FEE',
            'gateway'         => 'paystack',
            'payment_method'  => 'card',
            'status'          => 'completed',
            'is_verified'     => true,
            'student_type'    => 'applicant',
            'payment_purpose' => PaymentType::PURPOSE_APPLICATION,
            'fee_type'        => 'application',
            'payer_id'        => $applicant->id,
            'payer_name'      => $applicant->full_name ?? $applicant->user->name,
            'payment_date'    => now(),
        ]);

        $student = Student::create([
            'user_id'        => $applicant->user_id,
            'matric_number'  => 'LEGACY/NULL/001',
            'school_id'      => $applicant->school_id,
            'department_id'  => $applicant->department_id,
            'programme_id'   => $applicant->programme_id,
            'session_id'     => $applicant->session_id,
            'level'          => 1,
            'status'         => 'active',
            'from_application' => true,
            'applicant_id'   => $applicant->id,
        ]);
        $payment->update(['student_id' => $student->id]);

        $response = $this->actingAs($applicant->user)
            ->get('/student/payments');

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringContainsString(
            'LEGACY-NULL-FEE',
            $body,
            'A payment row with fee_id=NULL must still render in the student history.'
        );
        // payment_purpose is the third-tier fallback when both fee and
        // paymentType are null. The view renders the raw payment_purpose
        // string verbatim (e.g. "application") without humanisation in
        // this fallback path — the humanised form ("Application") only
        // kicks in via PaymentType::getDisplayLabelAttribute().
        $this->assertStringContainsString(
            'application',
            $body,
            'When fee_id is null, the Fee Type cell must fall back to the payment_purpose raw value.'
        );
    }

    /* --- helpers --- */

    private function makeAdmittedApplicant(): Applicant
    {
        // Promote to role=student so /student/payments passes the
        // role:student middleware without a separate re-auth flow.
        $studentRole = Role::where('slug', 'student')->first();
        $applicantRole = Role::where('slug', 'applicant')->first();

        $user = User::create([
            'name'     => 'Migration Test',
            'email'    => 'mig_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $studentRole?->id ?? $applicantRole?->id,
            'is_active' => true,
        ]);

        return Applicant::create([
            'user_id'            => $user->id,
            'email'              => $user->email,
            'application_number' => Applicant::generateApplicationNumber(),
            'status'             => 'admitted',
            'school_id'          => School::first()->id,
            'department_id'      => Department::first()->id,
            'programme_id'       => Programme::first()->id,
            'session_id'         => AcademicSession::first()->id,
            'state_id'           => 1,
            'lga_id'             => 1,
            'nationality_id'     => 1,
            'entry_level'        => 1,
        ]);
    }

    private function makeApplicantPayment(Applicant $applicant, PaymentType $type, float $amount, string $ref): Payment
    {
        return Payment::create([
            'student_id'      => null,
            'fee_id'          => $type->id,
            'amount'          => $amount,
            'total_amount'    => $amount,
            'reference'       => $ref,
            'payment_ref'     => $ref,
            'transaction_id'  => $ref,
            'gateway'         => 'paystack',
            'payment_method'  => 'card',
            'status'          => 'completed',
            'is_verified'     => true,
            'student_type'    => 'applicant',
            'payment_purpose' => $type->purpose,
            'fee_type'        => app(ApplicantPaymentService::class)->feeTypeFor($type->purpose),
            'payer_id'        => $applicant->id,
            'payer_name'      => $applicant->full_name ?? $applicant->user->name,
            'payer_email'     => $applicant->user->email,
            'payer_phone'     => $applicant->phone,
            'payment_date'    => now(),
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
        Schema::create('fees', function ($t) {
            $t->id();
            $t->string('name');
            $t->decimal('amount', 12, 2);
            $t->foreignId('session_id')->nullable()->constrained();
            $t->foreignId('school_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable()->constrained();
            $t->integer('level')->nullable();
            $t->date('due_date')->nullable();
            $t->decimal('portal_charge', 12, 2)->default(0);
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
            $t->string('payer_name')->nullable();
            $t->string('payer_email')->nullable();
            $t->string('payer_phone')->nullable();
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
        // The student.payments index() view calls SystemSetting
        // indirectly through the layout. We also need external_payments
        // for any code path that touches Applicant::transactionHistory().
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
            $t->unsignedBigInteger('payment_type_id')->nullable();
            $t->boolean('is_used')->default(false);
            $t->unsignedBigInteger('validated_by')->nullable();
            $t->dateTime('validated_at')->nullable();
            $t->timestamps();
        });
        Schema::create('system_settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        // Student\PaymentController::index() reads the active gateway to
        // decide whether to render the "Test Payment Simulator" button.
        Schema::create('payment_gateways', function ($t) {
            $t->id();
            $t->string('provider');
            $t->string('test_public_key')->nullable();
            $t->string('test_secret_key')->nullable();
            $t->string('live_public_key')->nullable();
            $t->string('live_secret_key')->nullable();
            $t->boolean('is_test_mode')->default(true);
            $t->boolean('is_active')->default(true);
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
            'name'          => 'Application Form Fee',
            'code'          => 'APP_FORM',
            'purpose'       => PaymentType::PURPOSE_APPLICATION,
            'amount'        => 5000,
            'audience'      => PaymentType::AUDIENCE_APPLICANT,
        ]);
        PaymentType::create([
            'name'          => 'Acceptance Fee',
            'code'          => 'ACCEPT_FEE',
            'purpose'       => PaymentType::PURPOSE_ACCEPTANCE,
            'amount'        => 25000,
            'audience'      => PaymentType::AUDIENCE_APPLICANT,
        ]);
        PaymentType::create([
            'name'          => 'Compulsory Fee',
            'code'          => 'COMP_FEE',
            'purpose'       => PaymentType::PURPOSE_COMPULSORY,
            'amount'        => 30000,
            'audience'      => PaymentType::AUDIENCE_APPLICANT,
        ]);

        // System settings the student.payments index() view reads.
        \App\Models\SystemSetting::set('payment_open', 'true');
        \App\Models\SystemSetting::set('institution_name', 'Test Institution');
    }
}