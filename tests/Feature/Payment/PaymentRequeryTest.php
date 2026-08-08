<?php

namespace Tests\Feature\Payment;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentType;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the contract for `payments.requery`:
 *   - Registered as a named POST route.
 *   - Owner (student OR applicant) can requery their own pending row.
 *   - Foreign user gets 403 (same ownership rule as the on-screen
 *     receipt).
 *   - Bursar / super_admin can requery in their school scope.
 *   - Already-verified rows short-circuit with an info flash — no
 *     gateway call — so accidental double-clicks don't hammer Paystack.
 *
 * Uses Http::fake() to stub the Paystack verify endpoint — no real
 * network calls. The XpressPayments branch is intentionally out of
 * scope for the test (it requires a configured gateway + service
 * constructor that hits the DB); the trait's dispatch logic for it
 * is exercised by the OnlinePaymentController tests.
 */
class PaymentRequeryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('fees');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('students');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_requery_route_is_registered(): void
    {
        $payment = $this->makeStudentPayment(status: 'pending');

        $url = route('payments.requery', $payment);
        $this->assertNotEmpty($url);
        $this->assertStringContainsString(
            '/payments/' . $payment->id . '/requery',
            $url
        );
    }

    public function test_student_can_requery_their_own_pending_payment(): void
    {
        $payment = $this->makeStudentPayment(status: 'pending');
        $user = $payment->student->user;

        Http::fake([
            'api.paystack.co/*' => Http::response(json_encode([
                'status' => true,
                'data' => [
                    'status'         => 'success',
                    'transaction_id' => 'TXN-REQ-001',
                    'amount'         => (int) ($payment->amount * 100),
                    'currency'       => 'NGN',
                ],
            ]), 200),
        ]);

        $response = $this->actingAs($user)
            ->post(route('payments.requery', $payment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Row should have flipped to 'verified' with the gateway's
        // transaction_id and paid_at stamped.
        $fresh = $payment->fresh();
        $this->assertSame('verified', $fresh->status);
        $this->assertSame('TXN-REQ-001', $fresh->transaction_id);
        $this->assertNotNull($fresh->paid_at);

        // The Paystack verify endpoint was actually called.
        Http::assertSent(function ($request) use ($payment) {
            return str_contains($request->url(), 'api.paystack.co/transaction/verify/')
                && str_contains($request->url(), urlencode($payment->reference));
        });
    }

    public function test_student_cannot_requery_someone_elses_payment(): void
    {
        $payment = $this->makeStudentPayment(status: 'pending');

        // Different student who owns their own Student row but not the
        // payment's student row.
        $otherUser = $this->makeUser('student');
        Student::create([
            'user_id'       => $otherUser->id,
            'matric_number' => 'OTHER/' . strtoupper(\Illuminate\Support\Str::random(6)),
            'school_id'     => School::first()->id,
            'department_id' => Department::first()->id,
            'programme_id'  => Programme::first()->id,
            'session_id'    => AcademicSession::first()->id,
            'level'         => 1,
            'status'        => 'active',
        ]);

        $response = $this->actingAs($otherUser)
            ->post(route('payments.requery', $payment));

        $response->assertStatus(403);

        // Row state untouched.
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_applicant_can_requery_their_own_pending_payment(): void
    {
        $payment = $this->makeApplicantPayment(status: 'pending');
        $user = $payment->applicant->user;

        Http::fake([
            'api.paystack.co/*' => Http::response(json_encode([
                'status' => true,
                'data' => [
                    'status'         => 'success',
                    'transaction_id' => 'TXN-APP-REQ-001',
                    'amount'         => (int) ($payment->amount * 100),
                    'currency'       => 'NGN',
                ],
            ]), 200),
        ]);

        $response = $this->actingAs($user)
            ->post(route('payments.requery', $payment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $payment->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertSame('TXN-APP-REQ-001', $fresh->transaction_id);
    }

    public function test_applicant_history_includes_pending_rows(): void
    {
        // The model previously filtered out non-completed rows at the
        // query level. Pin the relaxed contract so a regression that
        // re-adds the filter gets caught.
        $applicant = Applicant::create([
            'user_id'            => $this->makeUser('applicant')->id,
            'application_number' => 'APP-HIST-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'email'              => 'history_applicant@example.com',
            'status'             => 'pending',
        ]);
        Payment::create([
            'student_id'      => null,
            'fee_id'          => null,
            'amount'          => 5000,
            'total_amount'    => 5000,
            'reference'       => 'HIST-' . strtoupper(\Illuminate\Support\Str::random(10)),
            'gateway'         => 'paystack',
            'status'          => 'pending',
            'student_type'    => 'applicant',
            'payment_purpose' => 'application',
            'fee_type'        => 'application',
            'payer_id'        => $applicant->id,
            'payer_name'      => $applicant->full_name,
            'payer_email'     => $applicant->email,
            'payer_phone'     => $applicant->phone,
            'payment_date'    => now(),
        ]);

        $history = $applicant->fresh()->transactionHistory();
        $this->assertCount(1, $history);
        $this->assertSame('pending', $history->first()['status']);
        $this->assertNotNull($history->first()['payment_id']);
    }

    public function test_requery_short_circuits_when_already_verified(): void
    {
        $payment = $this->makeStudentPayment(status: 'verified');
        $user = $payment->student->user;

        // Stub Paystack with an empty response so we can detect whether
        // the trait was called at all — if the short-circuit fires,
        // Http::assertNothingSent() should pass.
        Http::fake();

        $response = $this->actingAs($user)
            ->post(route('payments.requery', $payment));

        $response->assertRedirect();
        $response->assertSessionHas('info');

        Http::assertNothingSent();
        $this->assertSame('verified', $payment->fresh()->status);
    }

    public function test_bursar_can_requery_a_payment_in_their_school(): void
    {
        $payment = $this->makeStudentPayment(status: 'pending');

        $bursar = $this->makeUser('bursar');
        $bursar->update(['school_id' => $payment->student->school_id]);

        Http::fake([
            'api.paystack.co/*' => Http::response(json_encode([
                'status' => true,
                'data' => [
                    'status'         => 'success',
                    'transaction_id' => 'TXN-BURSAR-001',
                    'amount'         => (int) ($payment->amount * 100),
                    'currency'       => 'NGN',
                ],
            ]), 200),
        ]);

        $response = $this->actingAs($bursar)
            ->post(route('payments.requery', $payment));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('verified', $payment->fresh()->status);
    }

    public function test_bursar_from_different_school_cannot_requery(): void
    {
        $payment = $this->makeStudentPayment(status: 'pending');

        $bursar = $this->makeUser('bursar');
        $bursar->update(['school_id' => $payment->student->school_id + 999]);

        $response = $this->actingAs($bursar)
            ->post(route('payments.requery', $payment));

        $response->assertStatus(403);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    /* --- helpers --- */

    private function makeStudentPayment(string $status = 'pending'): Payment
    {
        $school  = School::first();
        $dept    = Department::first();
        $prog    = Programme::first();
        $session = AcademicSession::first();

        $user = $this->makeUser('student');
        $student = Student::create([
            'user_id'       => $user->id,
            'matric_number' => 'EKSCOTECH/REQ/' . strtoupper(\Illuminate\Support\Str::random(4)),
            'school_id'     => $school->id,
            'department_id' => $dept->id,
            'programme_id'  => $prog->id,
            'session_id'    => $session->id,
            'level'         => 1,
            'status'        => 'active',
        ]);

        $fee = Fee::create([
            'name'                => 'HIM 100L Requery Test',
            'amount'              => 5000,
            'indigene_amount'     => 5000,
            'non_indigene_amount' => 5000,
            'portal_charge'       => 0,
            'school_id'           => $school->id,
            'department_id'       => $dept->id,
            'programme_id'        => $prog->id,
            'level'               => 1,
            'session_id'          => $session->id,
            'is_active'           => true,
        ]);

        return Payment::create([
            'student_id'      => $student->id,
            'fee_id'          => $fee->id,
            'amount'          => 5000,
            'total_amount'    => 5000,
            'reference'       => 'REQ-' . strtoupper(\Illuminate\Support\Str::random(10)),
            'gateway'         => 'paystack',
            'status'          => $status,
            'is_verified'     => $status === 'verified',
            'student_type'    => 'student',
            'payment_purpose' => 'school_fees',
            'fee_type'        => 'school_fees',
            'payer_name'      => $user->name,
            'payer_email'     => $user->email,
        ]);
    }

    private function makeApplicantPayment(string $status = 'pending'): Payment
    {
        $applicant = Applicant::create([
            'user_id'            => $this->makeUser('applicant')->id,
            'application_number' => 'APP-REQ-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'email'              => 'applicant_requery@example.com',
            'status'             => 'pending',
        ]);

        $fee = Fee::create([
            'name'           => 'Acceptance Requery Test',
            'amount'         => 10000,
            'indigene_amount'      => 10000,
            'non_indigene_amount'  => 10000,
            'portal_charge'        => 0,
            'school_id'            => School::first()->id,
            'department_id'        => Department::first()->id,
            'programme_id'         => Programme::first()->id,
            'level'                => 1,
            'session_id'           => AcademicSession::first()->id,
            'is_active'            => true,
        ]);

        return Payment::create([
            'student_id'      => null,
            'fee_id'          => $fee->id,
            'amount'          => 10000,
            'total_amount'    => 10000,
            'reference'       => 'APP-REQ-' . strtoupper(\Illuminate\Support\Str::random(10)),
            'gateway'         => 'paystack',
            'status'          => $status,
            'student_type'    => 'applicant',
            'payment_purpose' => 'acceptance',
            'fee_type'        => 'acceptance',
            'payer_id'        => $applicant->id,
            'payer_name'      => $applicant->full_name,
            'payer_email'     => $applicant->email,
            'payer_phone'     => $applicant->phone,
            'payment_date'    => now(),
        ]);
    }

    private function makeUser(string $roleSlug): User
    {
        return User::create([
            'name'      => 'Test ' . ucfirst($roleSlug),
            'email'     => $roleSlug . '_' . uniqid() . '@example.com',
            'password'  => bcrypt('password'),
            'role_id'   => Role::where('slug', $roleSlug)->value('id'),
            'is_active' => true,
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
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->foreignId('role_id')->nullable()->constrained();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('school_id')->nullable();
            $t->timestamps();
        });
        Schema::create('sessions', function ($t) {
            $t->id();
            $t->string('name');
            $t->boolean('is_current')->default(false);
            $t->timestamps();
        });
        Schema::create('schools', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('departments', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('programmes', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('applicants', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('application_number');
            $t->string('status')->default('pending');
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->unsignedBigInteger('session_id')->nullable();
            $t->string('surname')->nullable();
            $t->string('first_name')->nullable();
            $t->string('middle_name')->nullable();
            $t->string('gender')->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('marital_status')->nullable();
            $t->string('nationality')->nullable();
            $t->string('state_of_origin')->nullable();
            $t->string('lga')->nullable();
            $t->string('permanent_address')->nullable();
            $t->string('contact_address')->nullable();
            $t->string('passport')->nullable();
            $t->string('religion')->nullable();
            $t->string('blood_group')->nullable();
            $t->string('genotype')->nullable();
            $t->string('disability')->nullable();
            $t->text('disability_details')->nullable();
            $t->string('address')->nullable();
            $t->unsignedBigInteger('state_id')->nullable();
            $t->unsignedBigInteger('nationality_id')->nullable();
            $t->string('guardian_name')->nullable();
            $t->string('guardian_relationship')->nullable();
            $t->string('guardian_phone')->nullable();
            $t->string('guardian_email')->nullable();
            $t->string('guardian_occupation')->nullable();
            $t->string('guardian_address')->nullable();
            $t->string('primary_school')->nullable();
            $t->unsignedBigInteger('primary_school_start')->nullable();
            $t->unsignedBigInteger('primary_school_end')->nullable();
            $t->string('secondary_school')->nullable();
            $t->unsignedBigInteger('secondary_school_start')->nullable();
            $t->unsignedBigInteger('secondary_school_end')->nullable();
            $t->string('tertiary_institution')->nullable();
            $t->string('tertiary_qualification')->nullable();
            $t->unsignedBigInteger('tertiary_start')->nullable();
            $t->unsignedBigInteger('tertiary_end')->nullable();
            $t->string('application_paid_at')->nullable();
            $t->string('acceptance_paid_at')->nullable();
            $t->string('compulsory_paid_at')->nullable();
            // Columns touched by ApplicantPaymentService::applyApplicantSideEffects().
            $t->string('payment_status')->nullable();
            $t->string('payment_ref')->nullable();
            $t->string('payment_transaction_id')->nullable();
            $t->decimal('payment_amount', 12, 2)->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->timestamps();
        });
        Schema::create('students', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('matric_number');
            $t->string('status')->default('active');
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->unsignedBigInteger('session_id')->nullable();
            $t->unsignedBigInteger('level')->nullable();
            $t->timestamps();
        });
        Schema::create('payment_types', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->decimal('amount', 12, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('requires_payment')->default(true);
            $t->string('payment_channel')->nullable();
            $t->integer('priority')->default(0);
            $t->string('purpose')->nullable();
            $t->enum('audience', ['applicant', 'student', 'both'])->default('both');
            $t->timestamps();
        });
        Schema::create('system_settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
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
        Schema::create('fees', function ($t) {
            $t->id();
            $t->string('name');
            $t->decimal('amount', 12, 2)->default(0);
            $t->decimal('indigene_amount', 12, 2)->nullable();
            $t->decimal('non_indigene_amount', 12, 2)->nullable();
            $t->decimal('portal_charge', 12, 2)->default(0);
            $t->decimal('portal_charge_percentage', 5, 2)->nullable();
            $t->boolean('is_editable_amount')->default(false);
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->unsignedBigInteger('level')->nullable();
            $t->unsignedBigInteger('session_id')->nullable();
            $t->string('category')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('payments', function ($t) {
            $t->id();
            $t->unsignedBigInteger('student_id')->nullable();
            $t->unsignedBigInteger('fee_id')->nullable();
            $t->unsignedBigInteger('payer_id')->nullable();
            $t->decimal('amount', 12, 2);
            $t->decimal('total_amount', 12, 2)->nullable();
            $t->decimal('portal_charge', 12, 2)->default(0);
            $t->integer('percent_paid')->default(100);
            $t->string('installment_label', 20)->nullable();
            $t->string('reference')->nullable();
            $t->string('payment_ref')->nullable();
            $t->string('transaction_id')->nullable();
            $t->string('transaction_ref')->nullable();
            $t->string('gateway')->nullable();
            $t->string('payment_method')->nullable();
            $t->string('status')->default('pending');
            $t->boolean('is_verified')->default(false);
            $t->string('student_type')->nullable();
            $t->string('payment_purpose')->nullable();
            $t->string('fee_type')->nullable();
            $t->string('payer_name')->nullable();
            $t->string('payer_email')->nullable();
            $t->string('payer_phone')->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->dateTime('paid_at')->nullable();
            $t->text('payment_details')->nullable();
            $t->timestamps();
        });
        Schema::create('activity_logs', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action');
            $t->string('description')->nullable();
            $t->text('metadata')->nullable();
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Admin',     'slug' => 'admin']);
        Role::create(['name' => 'SuperAdmin','slug' => 'super_admin']);
        Role::create(['name' => 'Applicant', 'slug' => 'applicant']);
        Role::create(['name' => 'Student',   'slug' => 'student']);
        Role::create(['name' => 'Bursar',    'slug' => 'bursar']);
        Role::create(['name' => 'Registrar', 'slug' => 'registrar']);

        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        School::create(['name' => 'Test School']);
        Department::create(['name' => 'Test Department']);
        Programme::create(['name' => 'Test Programme']);

        \App\Models\SystemSetting::create(['key' => 'payment_open', 'value' => 'true']);
        PaymentGateway::create([
            'provider'        => 'paystack',
            'test_secret_key' => 'sk_test_fake',
            'live_secret_key' => 'sk_live_fake',
            'is_test_mode'    => true,
            'is_active'       => true,
        ]);

        PaymentType::create([
            'name' => 'Application Fee', 'code' => 'APP',
            'purpose' => 'application', 'audience' => 'applicant',
            'amount' => 5000, 'priority' => 1,
        ]);
    }
}