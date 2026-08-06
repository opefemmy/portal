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
 * Regression test for the "Test Payment" page on the applicant flow.
 *
 * The test page used to be hardcoded to the application-fee flow:
 *   - the view always labelled itself "Test Payment Gateway" with
 *     "Payment Type: Application Fee" regardless of what the user
 *     actually wanted to test
 *   - the form POSTed to /applicant/payment/test/process WITHOUT a
 *     purpose field, so the controller defaulted to 'application',
 *     canPay() said "You have already paid the application fee." and
 *     the user was bounced back to the dashboard before they could
 *     simulate an acceptance or school-fee payment
 *
 * The fix:
 *   - testPayment() controller method now reads ?purpose= and passes
 *     it (plus the resolved amount) to the view
 *   - payment-test.blade.php renders the right label, amount, and
 *     hidden purpose, and exposes a 3-button switcher for application
 *     / acceptance / school_fee
 *   - processTestPaymentInner() was already purpose-aware (the
 *     controller side) — only the view and the test-page entry were
 *     broken
 */
class ApplicantPaymentTestPageTest extends TestCase
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
     * The test page (without ?purpose=) must default to the application
     * flow and render successfully for a brand-new applicant.
     */
    public function test_test_page_defaults_to_application_flow(): void
    {
        $applicant = $this->makeApplicant('draft');

        $response = $this->actingAs($applicant->user)
            ->get('/applicant/payment/test');

        $response->assertOk();
        $response->assertSee('Test Mode', false);
        $response->assertSee('Application Fee', false);
    }

    /**
     * ?purpose=acceptance renders the acceptance-fee label and pre-fills
     * the acceptance-fee amount. This is the change that lets the user
     * simulate an acceptance payment without going through Paystack.
     */
    public function test_test_page_renders_acceptance_purpose(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update(['application_paid_at' => now()]);

        $response = $this->actingAs($applicant->user)
            ->get('/applicant/payment/test?purpose=acceptance');

        $response->assertOk();
        $response->assertSee('Acceptance Fee', false);
        $response->assertViewHas('purpose', 'acceptance');
        $response->assertViewHas('feeAmount');
        $this->assertEquals(25000.0, $response->viewData('feeAmount'));
    }

    /**
     * ?purpose=school_fee renders the compulsory-fee label and pre-fills
     * the school-fee amount.
     */
    public function test_test_page_renders_school_fee_purpose(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at' => now(),
        ]);

        $response = $this->actingAs($applicant->user)
            ->get('/applicant/payment/test?purpose=school_fee');

        $response->assertOk();
        $response->assertSee('Compulsory Fee', false);
        $response->assertViewHas('purpose', 'school_fee');
        $response->assertViewHas('feeAmount');
        $this->assertEquals(50000.0, $response->viewData('feeAmount'));
    }

    /**
     * The form action must include a hidden purpose input so the
     * process endpoint receives the right purpose. This is the
     * regression that closed the "test page bounces to dashboard"
     * complaint.
     */
    public function test_test_page_form_carries_purpose_field(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update(['application_paid_at' => now()]);

        $response = $this->actingAs($applicant->user)
            ->get('/applicant/payment/test?purpose=acceptance');

        $response->assertOk();
        // The view renders a hidden <input name="purpose" value="...">.
        $response->assertSee('name="purpose"', false);
        $response->assertSee('value="acceptance"', false);
    }

    /**
     * Test payment for acceptance actually completes and stamps the
     * applicant. This is the full end-to-end test: open test page for
     * acceptance, submit, verify the applicant has acceptance_paid_at
     * set and a completed payments row exists.
     */
    public function test_test_payment_completes_acceptance_flow(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update(['application_paid_at' => now()]);

        $response = $this->actingAs($applicant->user)
            ->post('/applicant/payment/test/process', [
                'amount' => 25000,
                'purpose' => 'acceptance',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $applicant->refresh();
        $this->assertNotNull($applicant->acceptance_paid_at, 'acceptance_paid_at should be set after test payment');

        $this->assertDatabaseHas('payments', [
            'payer_id' => $applicant->id,
            'payment_purpose' => 'acceptance',
            'status' => 'completed',
        ]);
    }

    /**
     * Legacy behaviour (purpose missing) still works for the
     * application-fee flow — old test pages / external links don't
     * break.
     */
    public function test_test_payment_without_purpose_defaults_to_application(): void
    {
        $applicant = $this->makeApplicant('draft');

        $response = $this->actingAs($applicant->user)
            ->post('/applicant/payment/test/process', [
                'amount' => 5000,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $applicant->refresh();
        $this->assertNotNull($applicant->application_paid_at);
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
