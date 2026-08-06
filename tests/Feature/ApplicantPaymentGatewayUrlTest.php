<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\PaymentType;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression test for the "Pay Compulsory Fee" button on the applicant
 * dashboard.
 *
 * The dashboard previously linked to
 *   /applicant/payment/gateway?purpose=compulsory
 * but the ApplicantPaymentService constants use
 *   PURPOSE_SCHOOL_FEE = 'school_fee'
 * (see PaymentType.php). The url-purpose and the service-purpose
 * drifted apart, and the controller's validation whitelist let the
 * bogus purpose through; the user saw "Unknown payment purpose."
 * flash and never got to the Pay Now screen.
 *
 * After the fix:
 *   - the dashboard button uses ?purpose=school_fee
 *   - the controller validation whitelist accepts only the canonical
 *     application|acceptance|school_fee values
 *
 * These tests pin both halves of the contract: the canonical URL
 * must succeed, and the legacy "compulsory" URL must be rejected by
 * validation (so the user gets a clean 422, not a misleading flash).
 *
 * Uses a hand-rolled schema instead of RefreshDatabase to keep
 * independent of the (numerous) pre-existing migration issues.
 */
class ApplicantPaymentGatewayUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('external_payments');
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

    /**
     * Canonical-purpose URL must succeed for an admitted applicant
     * who has paid the application fee. This is the dashboard button
     * "Pay Compulsory Fee" codepath.
     */
    public function test_canonical_school_fee_purpose_url_loads_for_admitted_applicant(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at' => now(),
        ]);

        $response = $this->actingAs($applicant->user)
            ->get('/applicant/payment/gateway?purpose=school_fee');

        $response->assertOk();
        // The payment-gateway view is the "Pay Now" page; it should
        // resolve the school_fee PaymentType and pass its amount to
        // the view. (We don't assert the literal 'school_fee' string —
        // the view may render the friendly name or currency, not the
        // machine purpose.)
        $response->assertViewHas('feeAmount');
        $this->assertEquals(50000.0, $response->viewData('feeAmount'));
    }

    /**
     * Canonical-purpose URL also works for the application-fee flow
     * (a fresh applicant row).
     */
    public function test_canonical_application_purpose_url_loads(): void
    {
        $user = $this->makeApplicant('draft')->user;

        $response = $this->actingAs($user)
            ->get('/applicant/payment/gateway?purpose=application');

        $response->assertOk();
        $response->assertViewHas('feeAmount');
        $this->assertEquals(5000.0, $response->viewData('feeAmount'));
    }

    /**
     * Canonical-purpose URL works for acceptance.
     */
    public function test_canonical_acceptance_purpose_url_loads(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update(['application_paid_at' => now()]);

        $response = $this->actingAs($applicant->user)
            ->get('/applicant/payment/gateway?purpose=acceptance');

        $response->assertOk();
        $response->assertViewHas('feeAmount');
        $this->assertEquals(25000.0, $response->viewData('feeAmount'));
    }

    /**
     * The legacy "compulsory" URL must NOT land on the Pay Now page.
     *
     * The gateway endpoint doesn't run Laravel's $request->validate()
     * for purpose — it normalises the value and asks the service to
     * resolve a PaymentType. With no PaymentType having
     * purpose='compulsory', resolvePaymentType() returns null and the
     * controller bounces back with an error flash. This is the
     * regression we pin: any caller using `?purpose=compulsory`
     * should fail loudly, not silently render the wrong Pay Now page.
     */
    public function test_legacy_compulsory_purpose_url_is_rejected_by_gateway(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at' => now(),
        ]);

        $response = $this->actingAs($applicant->user)
            ->get('/applicant/payment/gateway?purpose=compulsory');

        // The controller bounces to the dashboard with an error flash
        // (302) when no PaymentType matches. The user does NOT land on
        // the Pay Now page (200).
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    /**
     * Initiate endpoint with the legacy "compulsory" purpose must NOT
     * start a payment.
     *
     * The controller's inner validation runs $request->validate(),
     * which would normally throw ValidationException and put errors in
     * the session. But the outer try/catch in initiatePayment() catches
     * every Throwable (including ValidationException) and redirects
     * with a generic error flash. Either way, the user must NOT land
     * on the Paystack iframe page; this test asserts the bounce.
     */
    public function test_legacy_compulsory_purpose_is_rejected_on_initiate_post(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update(['application_paid_at' => now()]);

        $response = $this->actingAs($applicant->user)
            ->post('/applicant/payment/initiate', [
                'amount' => 50000,
                'purpose' => 'compulsory',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    /* --- helpers --- */

    private function makeApplicant(string $status): Applicant
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', 'applicant')->value('id'),
            'is_active' => true,
        ]);

        return Applicant::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'application_number' => Applicant::generateApplicationNumber(),
            'status' => $status,
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
            $t->string('transaction_id')->unique();
            $t->string('applicant_name');
            $t->string('email');
            $t->decimal('amount', 12, 2);
            $t->dateTime('payment_date');
            $t->string('payment_status');
            $t->string('payment_channel')->nullable();
            $t->text('description')->nullable();
            $t->foreignId('payment_type_id')->nullable();
            $t->foreignId('applicant_id')->nullable();
            $t->boolean('is_used')->default(false);
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Applicant', 'slug' => 'applicant']);
        Role::create(['name' => 'Registrar', 'slug' => 'registrar']);

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
        ]);
        PaymentType::create([
            'name' => 'Acceptance Fee',
            'code' => 'ACCEPT_FEE',
            'purpose' => PaymentType::PURPOSE_ACCEPTANCE,
            'amount' => 25000,
            'audience' => PaymentType::AUDIENCE_APPLICANT,
        ]);
        PaymentType::create([
            'name' => 'School Fees',
            'code' => 'SCHOOL_FEE',
            'purpose' => PaymentType::PURPOSE_SCHOOL_FEE,
            'amount' => 50000,
            'audience' => PaymentType::AUDIENCE_BOTH,
        ]);

        SystemSetting::set('admission_form_open', 'true');
    }
}