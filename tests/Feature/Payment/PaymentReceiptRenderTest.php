<?php

namespace Tests\Feature\Payment;

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
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the rendering contract for the shared receipt partial
 * (`resources/views/payments/_receipt.blade.php`) — institution name +
 * address in the brand header, watermarks stamped with payer name /
 * matric / payment purpose, and the same partial powering all three
 * audience-level receipt views.
 */
class PaymentReceiptRenderTest extends TestCase
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

    public function test_student_receipt_renders_institution_name_and_address(): void
    {
        SystemSetting::set('institution_name', 'Ekiti State Polytechnic');
        SystemSetting::set('institution_address', 'Ado-Ekiti, Ekiti State');

        $payment = $this->makeStudentPayment();

        $user = $payment->student->user;
        $response = $this->actingAs($user)
            ->get('/student/payments/' . $payment->id . '/receipt');

        $response->assertOk();
        $response->assertSee('Ekiti State Polytechnic', false);
        $response->assertSee('Ado-Ekiti, Ekiti State', false);
        $response->assertSee('OFFICIAL PAYMENT RECEIPT', false);
    }

    public function test_student_receipt_includes_watermark_with_payer_details(): void
    {
        SystemSetting::set('institution_name', 'EKSCOTECH');

        $payment = $this->makeStudentPayment();
        $user = $payment->student->user;

        $response = $this->actingAs($user)
            ->get('/student/payments/' . $payment->id . '/receipt');

        $response->assertOk();
        // The watermark text block stamps the payer name, the matric,
        // and the fee/payment purpose so a printed copy is traceable
        // even when the data row is detached.
        $response->assertSee('receipt-watermark-text', false);
        $response->assertSee($payment->student->user->name, false);
        $response->assertSee($payment->student->matric_number, false);
        // The fee type label is rendered in both the watermark and the
        // detail table — assertSee matches all occurrences.
        $response->assertSee($payment->fee->name, false);
    }

    public function test_bursar_receipt_renders_same_brand_header(): void
    {
        SystemSetting::set('institution_name', 'Ekiti State Polytechnic');
        SystemSetting::set('institution_address', 'Ado-Ekiti');

        $payment = $this->makeStudentPayment();

        $bursar = $this->makeUser('bursar');
        // Bursars see the receipt when the payment's student belongs
        // to the same school — match the controller's school_id guard.
        $bursar->update(['school_id' => $payment->student->school_id]);

        $response = $this->actingAs($bursar)
            ->get('/bursar/payments/' . $payment->id . '/receipt');

        $response->assertOk();
        $response->assertSee('Ekiti State Polytechnic', false);
        $response->assertSee('Ado-Ekiti', false);
        $response->assertSee('OFFICIAL PAYMENT RECEIPT', false);
    }

    public function test_receipt_view_includes_pdf_download_button(): void
    {
        SystemSetting::set('institution_name', 'EKSCOTECH');

        $payment = $this->makeStudentPayment();
        $user = $payment->student->user;

        $response = $this->actingAs($user)
            ->get('/student/payments/' . $payment->id . '/receipt');

        $response->assertOk();
        $response->assertSee('Download PDF', false);
        $response->assertSee('Print Receipt', false);
        // The download link points at the global PDF endpoint, not a
        // hard-coded student/bursar URL.
        $response->assertSee(route('payments.receipt.pdf', $payment), false);
    }

    public function test_pending_payment_renders_invoice_title_not_receipt(): void
    {
        // When the gateway hasn't settled yet, the same partial must
        // read "PAYMENT INVOICE" instead of "OFFICIAL PAYMENT RECEIPT"
        // — the user is looking at what they're about to pay, not a
        // receipt for money already received. The "Total Paid" line
        // must also flip to a Pending indicator.
        $payment = $this->makeStudentPayment();
        $payment->update(['status' => 'pending', 'is_verified' => false]);
        $user = $payment->student->user;

        $response = $this->actingAs($user)
            ->get('/student/payments/' . $payment->id . '/receipt');

        $response->assertOk();
        $response->assertSee('PAYMENT INVOICE', false);
        $response->assertDontSee('OFFICIAL PAYMENT RECEIPT', false);
        $response->assertSee('Pending', false);
    }

    /* --- helpers --- */

    private function makeStudentPayment(): Payment
    {
        $school  = School::first();
        $dept    = Department::first();
        $prog    = Programme::first();
        $session = AcademicSession::first();

        // Student + user with matching school/dept/programme so the
        // requiredFees() scope (in ExamClearanceController) would
        // match — and so the bursar's school_id guard passes too.
        $user = User::create([
            'name'      => 'Receipt Tester',
            'email'     => 'rt_' . uniqid() . '@example.com',
            'password'  => bcrypt('password'),
            'role_id'   => Role::where('slug', 'student')->value('id'),
            'is_active' => true,
        ]);
        $student = Student::create([
            'user_id'       => $user->id,
            'matric_number' => 'EKSCOTECH/RCP/24/001',
            'school_id'     => $school->id,
            'department_id' => $dept->id,
            'programme_id'  => $prog->id,
            'session_id'    => $session->id,
            'level'         => 1,
            'status'        => 'active',
        ]);

        // Fee with both per-category columns populated so priceFor()
        // resolves cleanly for any student.
        $fee = Fee::create([
            'name'                => 'HIM 100L Receipt Test',
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
            'student_id'   => $student->id,
            'fee_id'       => $fee->id,
            'amount'       => 5000,
            'total_amount' => 5000,
            'reference'    => 'RCP-' . strtoupper(\Illuminate\Support\Str::random(10)),
            'gateway'      => 'paystack',
            'status'       => 'completed',
            'is_verified'  => true,
            'student_type' => 'student',
            'payment_purpose' => 'school_fees',
            'fee_type'     => 'school_fees',
            'payer_name'   => $user->name,
            'payer_email'  => $user->email,
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
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->unsignedBigInteger('session_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
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
            $t->integer('percent_paid')->default(100);
            $t->string('installment_label', 20)->nullable();
            $t->string('reference')->nullable();
            $t->string('payment_ref')->nullable();
            $t->string('transaction_id')->nullable();
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
        \App\Models\PaymentGateway::create([
            'provider' => 'paystack',
            'test_secret_key' => 'sk_test_fake',
            'live_secret_key' => 'sk_live_fake',
            'is_test_mode' => true,
            'is_active' => true,
        ]);

        PaymentType::create([
            'name' => 'Library Fee', 'code' => 'LIBRARY',
            'purpose' => 'library', 'audience' => 'student',
            'amount' => 1000, 'priority' => 4,
        ]);
    }
}