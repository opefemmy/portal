<?php

namespace Tests\Feature\Applicant;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\ExternalPayment;
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
 * Pins the authenticated applicant-side payment receipt flow.
 *
 * Background: the existing public `online-payment.receipt` route lets
 * ANYONE with a URL render any payment's receipt — the route has no
 * auth or ownership check. There's also no receipt at all for
 * `external_payments` (validated bank transfers / manual uploads).
 *
 * The new `applicant.payments.receipt` route addresses both gaps:
 * 1. Requires auth (`auth` middleware on the route group).
 * 2. The controller looks up the {payment} segment against Payment.id
 *    first, then ExternalPayment.id, and aborts 403 if the row doesn't
 *    belong to the authenticated applicant.
 * 3. The view branches on `$isExternal` so a single template renders
 *    both row types.
 */
class PaymentReceiptTest extends TestCase
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

    public function test_online_payment_owned_by_applicant_renders_receipt(): void
    {
        $applicant = $this->makeApplicant();
        $payment = $this->makeOnlinePayment($applicant, [
            'reference'      => 'ONL-12345',
            'payment_ref'    => 'ONL-12345',
            'transaction_id' => 'TXN-1',
            'amount'         => 5000,
            'total_amount'   => 5000,
            'status'         => 'completed',
        ]);

        $response = $this->actingAs($applicant->user)
            ->get(route('applicant.payments.receipt', ['payment' => $payment->id]));

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringContainsString('ONL-12345', $body, 'Receipt must show the payment reference.');
        $this->assertStringContainsString('Payment Receipt', $body, 'Receipt view heading must be present.');
        $this->assertStringContainsString(
            '₦5,000.00',
            $body,
            'Receipt must show the formatted amount.'
        );
    }

    public function test_online_payment_owned_by_different_applicant_returns_403(): void
    {
        $applicantA = $this->makeApplicant();
        $applicantB = $this->makeApplicant();

        $payment = $this->makeOnlinePayment($applicantA, [
            'reference'    => 'OTHER-9999',
            'payment_ref'  => 'OTHER-9999',
            'amount'       => 5000,
            'total_amount' => 5000,
            'status'       => 'completed',
        ]);

        // Applicant B tries to view Applicant A's receipt.
        $response = $this->actingAs($applicantB->user)
            ->get(route('applicant.payments.receipt', ['payment' => $payment->id]));

        $response->assertStatus(403);
    }

    public function test_external_payment_owned_by_applicant_renders_receipt(): void
    {
        $applicant = $this->makeApplicant();
        $paymentType = PaymentType::where('purpose', PaymentType::PURPOSE_APPLICATION)->first();
        $external = ExternalPayment::create([
            'transaction_id'    => 'EXT-BANK-001',
            'applicant_name'    => $applicant->full_name,
            'email'             => $applicant->user->email,
            'amount'            => 5000,
            'payment_date'      => now(),
            'payment_status'    => 'completed',
            'payment_channel'   => 'bank_transfer',
            'description'       => 'Bank transfer for application fee',
            'payment_type_id'   => $paymentType->id,
            'applicant_id'      => $applicant->id,
            'is_used'           => true,
            'validated_at'      => now(),
        ]);

        $response = $this->actingAs($applicant->user)
            ->get(route('applicant.payments.receipt', ['payment' => $external->id]));

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringContainsString('EXT-BANK-001', $body, 'Receipt must show the external transaction id.');
        $this->assertStringContainsString(
            'Bank Transfer',
            $body,
            'Receipt must mark the row as a bank transfer / external payment.'
        );
    }

    public function test_external_payment_owned_by_different_applicant_returns_403(): void
    {
        $applicantA = $this->makeApplicant();
        $applicantB = $this->makeApplicant();

        $paymentType = PaymentType::where('purpose', PaymentType::PURPOSE_APPLICATION)->first();
        $external = ExternalPayment::create([
            'transaction_id'    => 'EXT-OTHER-001',
            'applicant_name'    => $applicantA->full_name,
            'email'             => $applicantA->user->email,
            'amount'            => 5000,
            'payment_status'    => 'completed',
            'payment_channel'   => 'bank_transfer',
            'payment_type_id'   => $paymentType->id,
            'applicant_id'      => $applicantA->id,
            'is_used'           => true,
            'validated_at'      => now(),
        ]);

        $response = $this->actingAs($applicantB->user)
            ->get(route('applicant.payments.receipt', ['payment' => $external->id]));

        $response->assertStatus(403);
    }

    public function test_nonexistent_payment_id_returns_404(): void
    {
        $applicant = $this->makeApplicant();

        // Pick an id that won't exist in either payments or external_payments.
        $response = $this->actingAs($applicant->user)
            ->get(route('applicant.payments.receipt', ['payment' => 999999]));

        $response->assertStatus(404);
    }

    public function test_receipt_renders_application_number_and_email(): void
    {
        $applicant = $this->makeApplicant();
        $payment = $this->makeOnlinePayment($applicant, [
            'reference'    => 'REF-APPNUM',
            'payment_ref'  => 'REF-APPNUM',
            'amount'       => 7000,
            'total_amount' => 7000,
            'status'       => 'completed',
        ]);

        $response = $this->actingAs($applicant->user)
            ->get(route('applicant.payments.receipt', ['payment' => $payment->id]));

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringContainsString(
            $applicant->application_number,
            $body,
            'Receipt must render the applicant application number.'
        );
        $this->assertStringContainsString(
            $applicant->user->email,
            $body,
            'Receipt must render the applicant email.'
        );
    }

    public function test_pay_another_fee_button_hidden_when_no_payable_purpose(): void
    {
        // Applicant has paid all required fees — `nextPayablePurpose()`
        // returns null. The "Pay Another Fee" button must NOT appear.
        $applicant = $this->makeApplicant();
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
            'compulsory_paid_at'  => now(),
        ]);

        $payment = $this->makeOnlinePayment($applicant, [
            'reference'    => 'REF-NO-NEXT',
            'payment_ref'  => 'REF-NO-NEXT',
            'amount'       => 5000,
            'total_amount' => 5000,
            'status'       => 'completed',
        ]);

        $response = $this->actingAs($applicant->user)
            ->get(route('applicant.payments.receipt', ['payment' => $payment->id]));

        $response->assertOk();
        $this->assertStringNotContainsString(
            'Pay Another Fee',
            $response->getContent(),
            'Pay Another Fee button must hide when no payable purpose remains.'
        );
    }

    public function test_pay_another_fee_button_never_shown_even_when_purpose_pending(): void
    {
        // Per the ticket: the receipt is strictly read-only. Even when the
        // applicant has more fees due (e.g. application paid but
        // acceptance still pending), the receipt must NOT show any
        // "Pay ..." button. Re-pay must happen on the applicant dashboard.
        $applicant = $this->makeApplicant();
        $applicant->update([
            'application_paid_at' => now(),
            // acceptance_paid_at + compulsory_paid_at NOT set
        ]);

        $payment = $this->makeOnlinePayment($applicant, [
            'reference'    => 'REF-NEXT',
            'payment_ref'  => 'REF-NEXT',
            'amount'       => 5000,
            'total_amount' => 5000,
            'status'       => 'completed',
        ]);

        $response = $this->actingAs($applicant->user)
            ->get(route('applicant.payments.receipt', ['payment' => $payment->id]));

        $response->assertOk();
        $body = $response->getContent();
        $this->assertStringNotContainsString(
            'Pay Another Fee',
            $body,
            'Receipt must never render a Pay Another Fee button.'
        );
        // The applicant payment gateway is the next-pay destination — make
        // sure no link to it exists from the receipt.
        $this->assertDoesNotMatchRegularExpression(
            '/href="[^"]*applicant\/payment\/gateway[^"]*"/',
            $body,
            'Receipt must not link into the applicant payment gateway.'
        );
    }

    public function test_receipt_header_renders_institution_address_when_set(): void
    {
        // Seed an institution_address row and confirm the receipt header
        // renders it below the institution name.
        \App\Models\SystemSetting::set(\App\Models\SystemSetting::INSTITUTION_ADDRESS, 'Ijero-Ekiti, Ekiti State, Nigeria');

        $applicant = $this->makeApplicant();
        $payment = $this->makeOnlinePayment($applicant, [
            'reference'    => 'REF-ADDR',
            'payment_ref'  => 'REF-ADDR',
            'amount'       => 5000,
            'total_amount' => 5000,
            'status'       => 'completed',
        ]);

        $response = $this->actingAs($applicant->user)
            ->get(route('applicant.payments.receipt', ['payment' => $payment->id]));

        $response->assertOk();
        $this->assertStringContainsString(
            'Ijero-Ekiti, Ekiti State, Nigeria',
            $response->getContent(),
            'Receipt header must render the institution address when set.'
        );
    }

    public function test_unauthenticated_request_redirects(): void
    {
        $applicant = $this->makeApplicant();
        $payment = $this->makeOnlinePayment($applicant, [
            'reference'    => 'REF-AUTH',
            'payment_ref'  => 'REF-AUTH',
            'amount'       => 5000,
            'total_amount' => 5000,
            'status'       => 'completed',
        ]);

        // Bypass actingAs — the `auth` middleware on the route should
        // redirect or 403.
        $response = $this->get(route('applicant.payments.receipt', ['payment' => $payment->id]));

        $this->assertContains($response->getStatusCode(), [302, 401, 403]);
    }

    /* --- helpers --- */

    private function makeApplicant(): Applicant
    {
        $user = User::create([
            'name'  => 'Receipt Test Applicant',
            'email' => 'receipt_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', 'applicant')->value('id'),
            'is_active' => true,
        ]);

        return Applicant::create([
            'user_id'            => $user->id,
            'email'              => $user->email,
            'application_number' => Applicant::generateApplicationNumber(),
            'status'             => 'admitted',
            'surname'            => 'Receipt',
            'first_name'         => 'Tester',
            'school_id'          => School::first()->id,
            'department_id'      => Department::first()->id,
            'programme_id'       => Programme::first()->id,
            'session_id'         => AcademicSession::first()->id,
        ]);
    }

    private function makeOnlinePayment(Applicant $applicant, array $overrides): Payment
    {
        return Payment::create(array_merge([
            'student_id'      => null,
            'fee_id'          => null,
            'amount'          => 5000,
            'total_amount'    => 5000,
            'reference'       => 'REF-' . strtoupper(uniqid()),
            'payment_ref'     => 'REF-' . strtoupper(uniqid()),
            'transaction_id'  => 'TXN-' . strtoupper(uniqid()),
            'gateway'         => 'paystack',
            'payment_method'  => 'paystack',
            'status'          => 'completed',
            'is_verified'     => true,
            'student_type'    => 'applicant',
            'payment_purpose' => PaymentType::PURPOSE_APPLICATION,
            'fee_type'        => 'application',
            'payer_id'        => $applicant->id,
            'payer_name'      => $applicant->full_name,
            'payer_email'     => $applicant->user->email,
            'payer_phone'     => $applicant->phone,
            'payment_date'    => now(),
        ], $overrides));
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
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('status', 20)->default('pending');
            $t->foreignId('school_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable()->constrained();
            $t->foreignId('programme_id')->nullable()->constrained();
            $t->foreignId('session_id')->nullable()->constrained();
            $t->dateTime('application_paid_at')->nullable();
            $t->dateTime('acceptance_paid_at')->nullable();
            $t->dateTime('compulsory_paid_at')->nullable();
            $t->dateTime('migrated_to_student_at')->nullable();
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
            $t->decimal('portal_charge', 12, 2)->nullable();
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
        Schema::create('external_payments', function ($t) {
            $t->id();
            $t->foreignId('applicant_id')->nullable();
            $t->foreignId('payment_type_id')->nullable();
            $t->foreignId('fee_id')->nullable();
            $t->string('transaction_id')->nullable();
            $t->string('applicant_name')->nullable();
            $t->string('email')->nullable();
            $t->decimal('amount', 12, 2)->default(0);
            $t->dateTime('payment_date')->nullable();
            $t->string('payment_status', 20)->default('pending');
            $t->string('payment_channel')->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_used')->default(false);
            $t->foreignId('validated_by')->nullable();
            $t->dateTime('validated_at')->nullable();
            $t->timestamps();
        });
        Schema::create('system_settings', function ($t) {
            // The receipt view reads SystemSetting::getInstitutionName()
            // which falls back to a default but the table still needs to
            // exist or the view errors out on PDOException.
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
        $dept = Department::create([
            'name' => 'Computer Studies',
            'code' => 'COM',
            'school_id' => $school->id,
        ]);
        Programme::create([
            'name' => 'Computer Science',
            'code' => 'CSC',
            'department_id' => $dept->id,
        ]);
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
            'name' => 'Compulsory Fee',
            'code' => 'COMP_FEE',
            'purpose' => PaymentType::PURPOSE_COMPULSORY,
            'amount' => 30000,
            'audience' => PaymentType::AUDIENCE_APPLICANT,
        ]);
    }
}