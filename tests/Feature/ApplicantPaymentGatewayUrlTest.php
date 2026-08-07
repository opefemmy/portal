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
 * Regression tests for the applicant payment gateway URL endpoints.
 *
 * History:
 *   The dashboard's "Pay Compulsory Fee" button links to
 *   /applicant/payment/gateway?purpose=compulsory (the migration
 *   trigger). The form on the gateway page re-posts `purpose=compulsory`
 *   to /applicant/payment/initiate when the applicant clicks Pay Now.
 *
 *   The controller's validator on `initiate` used a hardcoded
 *   `in:application,acceptance,school_fee` whitelist (no `compulsory`),
 *   so a Compulsory submit 500'd as a ValidationException and the
 *   top-level Throwable catch surfaced a misleading "Test payment
 *   simulated (handler recovered from an internal error)" success
 *   flash — masking the real reason. The user never saw the Paystack
 *   iframe.
 *
 * After the fix:
 *   - the validator on `initiate` accepts `compulsory` as a valid
 *     purpose (alongside application|acceptance|school_fee).
 *   - the gateway URL `?purpose=compulsory` resolves to the COMP_FEE
 *     PaymentType and renders the Pay Now page.
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
     * Regression: the gateway view's Pay Now form must carry the
     * `purpose` hidden input through to the initiate endpoint.
     *
     * The original form omitted the purpose field, so POSTing
     * /applicant/payment/initiate from the gateway always defaulted
     * to 'application', the canPay() gate returned
     * "You have already paid the application fee." and the user was
     * bounced back to the dashboard before reaching the Paystack
     * iframe. The fix adds a hidden <input name="purpose" value="...">
     * to the form, mirroring the URL ?purpose=.
     *
     * This is the single most important regression: the user's "pay
     * acceptance" button was wired to a form that asked for the
     * application fee.
     */
    public function test_gateway_pay_now_form_carries_purpose_field(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update(['application_paid_at' => now()]);

        $response = $this->actingAs($applicant->user)
            ->get('/applicant/payment/gateway?purpose=acceptance');

        $response->assertOk();
        $response->assertSee('name="purpose"', false);
        $response->assertSee('value="acceptance"', false);
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
     * Central regression: an applicant who has paid the application fee
     * must be able to initiate an acceptance payment without being
     * bounced back to the dashboard.
     *
     * The old form omitted the purpose field, so POSTing initiate
     * defaulted to 'application', canPay() said "already paid", and the
     * user was bounced. The fix: the form passes purpose=acceptance
     * through, canPay(acceptance) returns null (admitted, not yet paid),
     * and we get the Paystack iframe view (200) instead of a 302
     * dashboard bounce.
     */
    public function test_initiate_acceptance_payment_does_not_bounce_after_app_fee_paid(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update(['application_paid_at' => now()]);

        $response = $this->actingAs($applicant->user)
            ->post('/applicant/payment/initiate', [
                'amount' => 25000,
                'purpose' => 'acceptance',
            ]);

        // We expect the Paystack iframe page (200), NOT a redirect
        // to the dashboard.
        $response->assertOk();
        $response->assertSessionHasNoErrors();
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
     * Compulsory purpose URL must land on the Pay Now page when the
     * COMP_FEE row is configured for the applicant audience.
     *
     * The dashboard's "Pay Compulsory Fee" button links to
     * /applicant/payment/gateway?purpose=compulsory. resolvePaymentType
     * looks up the COMP_FEE row by PURPOSE_CODES['compulsory'] = 'COMP_FEE'
     * and the row's audience='applicant', so the gateway renders the
     * Pay Now page (200). The feeAmount must match the seeded amount.
     */
    public function test_compulsory_purpose_url_loads_for_admitted_applicant(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at' => now(),
        ]);

        $response = $this->actingAs($applicant->user)
            ->get('/applicant/payment/gateway?purpose=compulsory');

        $response->assertOk();
        $response->assertViewHas('feeAmount');
        $this->assertEquals(30000.0, $response->viewData('feeAmount'));
    }

    /**
     * Initiate endpoint with `purpose=compulsory` must NOT be rejected
     * by the validator. The validator's whitelist now includes
     * `compulsory` (the migration trigger); without this, the applicant
     * who clicks the dashboard "Pay Compulsory Fee" button would 500
     * with ValidationException, masked by the top-level Throwable
     * catch as a misleading "Test payment simulated (handler recovered
     * from an internal error)" success flash.
     *
     * With only `application_paid_at` set, canPay() still blocks with
     * "Pay the acceptance fee before paying the compulsory fee." — so
     * the response is a 302 with an error flash, but the redirect is
     * the dashboard, not the gateway reload. We assert the redirect
     * target to confirm the canPay gate (not the validator) is what
     * bounced us.
     */
    public function test_compulsory_purpose_passes_validator_on_initiate_post(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update(['application_paid_at' => now()]);

        $response = $this->actingAs($applicant->user)
            ->post('/applicant/payment/initiate', [
                'amount' => 30000,
                'purpose' => 'compulsory',
            ]);

        $response->assertStatus(302);
        // If the validator rejected `compulsory`, the catch would have
        // bounced us to /applicant/payment/gateway?purpose=compulsory
        // (see initiatePayment() catch block). If the canPay gate
        // bounced us, the redirect target is the applicant dashboard.
        $response->assertRedirect(route('applicant.dashboard'));
        $response->assertSessionHas('error');
    }

    /**
     * Initiate endpoint with `purpose=compulsory` and a fully-paid
     * applicant reaches the Paystack iframe page.
     *
     * This is the happy-path regression: the dashboard button must
     * land on the Paystack inline page, not bounce or 500. Without
     * `compulsory` on the validator's whitelist, the request throws
     * ValidationException and the catch surfaces a misleading success
     * flash. With it on the whitelist, canPay returns null and the
     * controller renders the payment-initiate view (200).
     */
    public function test_initiate_compulsory_payment_reaches_paystack_iframe_when_admitted(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
        ]);

        $response = $this->actingAs($applicant->user)
            ->post('/applicant/payment/initiate', [
                'amount' => 30000,
                'purpose' => 'compulsory',
            ]);

        $response->assertOk();
        $response->assertSessionHasNoErrors();
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
        // Compulsory Fee is the applicant→student migration trigger. The
        // dashboard's "Pay Compulsory Fee" button links to
        // /applicant/payment/gateway?purpose=compulsory and the form on
        // that page re-posts `purpose=compulsory` to /applicant/payment/initiate.
        PaymentType::create([
            'name' => 'Compulsory Fee',
            'code' => 'COMP_FEE',
            'purpose' => PaymentType::PURPOSE_COMPULSORY,
            'amount' => 30000,
            'audience' => PaymentType::AUDIENCE_APPLICANT,
        ]);

        SystemSetting::set('admission_form_open', 'true');
    }
}