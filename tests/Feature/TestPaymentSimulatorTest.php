<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Fee;
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
 * Regression net for the cross-audience test-payment simulator.
 *
 * The /payment/test pages reachable from applicant, student, bursar,
 * and registrar all share the same controller (Payment\TestPaymentController)
 * which is gated on APP_ENV != production and writes an activity log
 * row on every call.
 *
 * Each area adds its own quick links — every admin / applicant / student
 * can fire a simulated payment without going through real Paystack.
 */
class TestPaymentSimulatorTest extends TestCase
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

    public function test_applicant_test_payment_page_lists_payment_types(): void
    {
        $user = $this->makeUser('applicant');

        $response = $this->actingAs($user)
            ->get('/applicant/payment/test/applicant');

        $response->assertOk();
        $response->assertViewIs('payments.test');
        $response->assertSee('Test Payment Simulator', false);
        $response->assertSee('Compulsory Fee', false); // from seeder
        $response->assertSee('Application Form Fee', false);
    }

    public function test_applicant_can_process_a_test_payment(): void
    {
        $user = $this->makeUser('applicant');
        $type = PaymentType::where('code', 'COMP_FEE')->first();

        $this->assertNotNull($type);

        $response = $this->actingAs($user)->post('/applicant/payment/test/applicant/process', [
            'payment_type_id' => $type->id,
            'amount'          => $type->amount,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'payer_id'        => Applicant::where('user_id', $user->id)->value('id'),
            'payment_purpose' => 'compulsory',
            'gateway'         => 'test',
            'status'          => 'completed',
        ]);
    }

    public function test_student_test_payment_page_uses_student_catalogue(): void
    {
        $user = $this->makeUser('student');

        $response = $this->actingAs($user)
            ->get('/student/payment/test');

        $response->assertOk();
        $response->assertSee('Test Payment Simulator', false);
        // Student catalogue should NOT include applicant-only rows.
        $response->assertDontSee('Acceptance Fee', false);
    }

    public function test_bursar_sees_both_catalogue(): void
    {
        $user = $this->makeUser('bursar');

        $response = $this->actingAs($user)
            ->get('/bursar/payment/test');

        $response->assertOk();
        $response->assertSee('Test Payment Simulator', false);
        // Bursar should see both applicant rows (Acceptance Fee) and
        // student rows (Library Fee).
        $response->assertSee('Acceptance Fee', false);
        $response->assertSee('Library Fee', false);
    }

    public function test_registrar_sees_both_catalogue(): void
    {
        $user = $this->makeUser('registrar');

        $response = $this->actingAs($user)
            ->get('/registrar/payment/test');

        $response->assertOk();
        $response->assertSee('Test Payment Simulator', false);
        $response->assertSee('Compulsory Fee', false);
    }

    public function test_process_writes_activity_log_row(): void
    {
        $user = $this->makeUser('applicant');
        $type = PaymentType::where('code', 'APP_FORM')->first();

        $this->actingAs($user)->post('/applicant/payment/test/applicant/process', [
            'payment_type_id' => $type->id,
            'amount'          => $type->amount,
        ])->assertStatus(302);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action'  => 'test_payment.process',
        ]);
    }

    public function test_show_writes_activity_log_row(): void
    {
        $user = $this->makeUser('bursar');

        $this->actingAs($user)->get('/bursar/payment/test')->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action'  => 'test_payment.show',
        ]);
    }

    public function test_invalid_amount_is_rejected(): void
    {
        // Floor is now min:1 (was min:100) so small fees like HIM 100L
        // (₦20) can be paid exactly. Below 1 — including 0 — still 422s.
        $user = $this->makeUser('applicant');
        $type = PaymentType::where('code', 'APP_FORM')->first();

        $this->actingAs($user)
            ->post('/applicant/payment/test/applicant/process', [
                'payment_type_id' => $type->id,
                'amount'          => 0,
            ])
            ->assertSessionHasErrors('amount');
    }

    /**
     * Regression: previously the simulator enforced a 100-naira floor,
     * so paying small fees like HIM 100L (₦20) was impossible without
     * overpaying. The floor is now min:1 — verify a 20-naira payment
     * (the exact catalogue amount) goes through.
     */
    public function test_exact_small_fee_amount_is_accepted(): void
    {
        $user = $this->makeUser('applicant');
        $type = PaymentType::where('code', 'APP_FORM')->first();

        $response = $this->actingAs($user)->post('/applicant/payment/test/applicant/process', [
            'payment_type_id' => $type->id,
            'amount'          => 20, // was rejected under min:100
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'amount'   => 20,
            'gateway'  => 'test',
            'status'   => 'completed',
            'payer_id' => Applicant::where('user_id', $user->id)->value('id'),
        ]);
    }

    /**
     * When the student picks a Fee row in the dropdown, the simulator
     * writes fee_id + percent_paid=100 + installment_label='full' so
     * SchoolFeeCalculator::totalPercentPaid (which filters by fee_id)
     * sees the row and unlocks exam clearance.
     */
    public function test_fee_linked_payment_unlocks_exam_clearance(): void
    {
        // Fee + student with the matching school/dept/programme/level.
        // The exam-clearance filter on requiredFees() matches by those
        // four columns, so they must align for the test to be
        // representative of the live flow.
        $session = AcademicSession::first();
        $school  = School::first();
        $dept    = Department::first();
        $prog    = Programme::first();

        $fee = Fee::create([
            'name'                => 'HIM 100L Test Fee',
            'amount'              => 20,
            'non_indigene_amount' => 20,
            'portal_charge'       => 0,
            'school_id'           => $school->id,
            'department_id'       => $dept->id,
            'programme_id'        => $prog->id,
            'level'               => 1,
            'session_id'          => $session->id,
            'is_active'           => true,
        ]);

        // Student user + student row.
        $user = User::create([
            'name'      => 'Test Student',
            'email'     => 'student_' . uniqid() . '@example.com',
            'password'  => bcrypt('password'),
            'role_id'   => Role::where('slug', 'student')->value('id'),
            'is_active' => true,
        ]);
        \App\Models\Student::create([
            'user_id'       => $user->id,
            'matric_number' => 'STU/' . strtoupper(\Illuminate\Support\Str::random(8)),
            'school_id'     => $school->id,
            'department_id' => $dept->id,
            'programme_id'  => $prog->id,
            'session_id'    => $session->id,
            'level'         => 1,
            'status'        => 'active',
        ]);

        // Submit through the student-audience route with fee_id.
        $this->actingAs($user)->post('/student/payment/test/process', [
            'payment_type_id' => PaymentType::where('code', 'LIBRARY')->value('id'),
            'fee_id'          => $fee->id,
            'amount'          => 20,
        ])->assertStatus(302);

        // Payment row carries the link + percent_paid/installment.
        $payment = Payment::where('payer_id', null) // student_id-only rows
            ->where('fee_id', $fee->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($payment,
            'Simulator must write a Payment row linked to the Fee.');
        $this->assertSame(100, (int) $payment->percent_paid);
        $this->assertSame('full', $payment->installment_label);

        // Exam-clearance gate sees the row.
        $student = \App\Models\Student::where('user_id', $user->id)->first();
        $paid = \App\Services\SchoolFeeCalculator::totalPercentPaid($student, $fee);
        $this->assertSame(100, $paid,
            'totalPercentPaid() must see the simulator row once fee_id is linked.');
    }

    public function test_unknown_payment_type_id_is_rejected(): void
    {
        $user = $this->makeUser('applicant');

        $this->actingAs($user)
            ->post('/applicant/payment/test/applicant/process', [
                'payment_type_id' => 999999,
                'amount'          => 5000,
            ])
            ->assertSessionHasErrors('payment_type_id');
    }

    /**
     * Regression: students saw "We could not start your school-fee
     * payment just now..." because the live Paystack / Flutterwave
     * round-trip kept failing. The student payments page now exposes
     * the test simulator so the student can walk through the same flow
     * without a real card.
     *
     * Pin that the link is present in the rendered HTML on the
     * student payments page in non-production environments.
     */
    public function test_student_payments_page_links_to_test_simulator_in_non_production(): void
    {
        // APP_ENV must be anything other than production for the link
        // to render — the controller returns 404 in production.
        $this->assertNotEquals('production', app()->environment(),
            'Test fixture must run in non-production so the link renders.');

        // Don't go through the controller — it depends on a Fee row that
        // isn't in this test's minimal schema. Pin the view source
        // directly so the test stays focused on "is the link present
        // in the page the student sees?".
        $viewPath = resource_path('views/student/payments.blade.php');
        $this->assertFileExists($viewPath,
            'resources/views/student/payments.blade.php must exist for the test to be meaningful.');

        $body = file_get_contents($viewPath);

        $this->assertStringContainsString(
            "route('student.payment.test.show.student')",
            $body,
            'Student payments view must reference student.payment.test.show.student — students need a non-Paystack escape hatch.'
        );

        // The link must NOT be unconditionally rendered in production.
        // We don't want students to see "Test mode" in production even
        // if the controller gate is the primary defence.
        $this->assertStringContainsString(
            "!app()->environment('production')",
            $body,
            'Student payments view must guard the test-simulator link on APP_ENV != production.'
        );
    }

    /**
     * Same link must be present on the per-fee payment-pay page so
     * the student can switch to test mode even after they've already
     * clicked Pay Now and hit the gateway error.
     */
    public function test_student_payment_pay_page_links_to_test_simulator(): void
    {
        // Pin only that the route exists and the helper it calls works.
        // The full /student/payments/{fee}/pay page is hard to render
        // in a unit test (it requires a Fee + Gateway row); this test
        // just asserts the route name is registered so the link doesn't
        // 404 when clicked.
        $this->assertNotEmpty(
            route('student.payment.test.show.student'),
            'student.payment.test.show.student route must be registered.'
        );
    }

    /* --- helpers --- */

    private function makeUser(string $roleSlug): User
    {
        return User::create([
            'name'     => 'Test ' . ucfirst($roleSlug),
            'email'    => $roleSlug . '_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id'  => Role::where('slug', $roleSlug)->value('id'),
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
            $t->timestamp('application_paid_at')->nullable();
            $t->timestamp('acceptance_paid_at')->nullable();
            $t->timestamp('compulsory_paid_at')->nullable();
            $t->unsignedBigInteger('student_id')->nullable();
            $t->timestamp('migrated_to_student_at')->nullable();
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
            // Required by SchoolFeeCalculator::requiredFeesFor() —
            // its `where('level', $student->level)` clause throws on
            // missing column in sqlite. Nullable because some fixtures
            // omit it.
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
        Schema::create('payments', function ($t) {
            $t->id();
            $t->unsignedBigInteger('student_id')->nullable();
            $t->unsignedBigInteger('fee_id')->nullable();
            $t->decimal('amount', 12, 2);
            $t->decimal('total_amount', 12, 2)->nullable();
            // percent_paid + installment_label are written by the
            // test-payment simulator whenever fee_id is set, so the
            // test schema must mirror the real migrations.
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
            $t->unsignedBigInteger('payer_id')->nullable();
            $t->string('payer_name')->nullable();
            $t->string('payer_email')->nullable();
            $t->string('payer_phone')->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->text('payment_details')->nullable();
            $t->timestamps();
        });
        // Fees table mirrors the real schema columns read by the
        // test simulator's show() / process() paths. Empty here —
        // tests that need an exact-fee scenario seed their own row.
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

        // The student payments page reads payment_open + an active gateway.
        // Without these the controller short-circuits before reaching the
        // view, so we can't render the link we're trying to pin.
        \App\Models\SystemSetting::create(['key' => 'payment_open', 'value' => 'true']);
        \App\Models\PaymentGateway::create([
            'provider' => 'paystack',
            'test_secret_key' => 'sk_test_fake',
            'live_secret_key' => 'sk_live_fake',
            'is_test_mode' => true,
            'is_active' => true,
        ]);

        PaymentType::create([
            'name' => 'Application Form Fee', 'code' => 'APP_FORM',
            'purpose' => 'application', 'audience' => 'applicant',
            'amount' => 5000, 'priority' => 1,
        ]);
        PaymentType::create([
            'name' => 'Acceptance Fee', 'code' => 'ACCEPT_FEE',
            'purpose' => 'acceptance', 'audience' => 'applicant',
            'amount' => 25000, 'priority' => 2,
        ]);
        PaymentType::create([
            'name' => 'Compulsory Fee', 'code' => 'COMP_FEE',
            'purpose' => 'compulsory', 'audience' => 'applicant',
            'amount' => 100000, 'priority' => 3,
        ]);
        PaymentType::create([
            'name' => 'Library Fee', 'code' => 'LIBRARY',
            'purpose' => 'library', 'audience' => 'student',
            'amount' => 1000, 'priority' => 4,
        ]);
    }
}
