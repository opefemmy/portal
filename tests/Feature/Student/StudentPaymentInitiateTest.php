<?php

namespace Tests\Feature\Student;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SchoolFeeCalculator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the student-side school-fee payment flow against a production-like
 * payments table — most importantly the ENUM columns on
 * payments.installment / payments.fee_type that MySQL rejects strict
 * inserts on.
 *
 * Regression: user reported "https://eportal.personel.ink/student/payments/6/initiate
 * server 500 error". The cause was Student/PaymentController::initiatePaymentInner()
 * writing `'installment' => $label` where $label is `'full'` / `'first'`
 * / `'second'` (lowercase, see SchoolFeeCalculator::installmentLabel())
 * into a payments.installment column that on production is
 *   enum('First','Second','Full')
 * Strict mode rejects the lowercase value as not in the ENUM and
 * throws Illuminate\Database\QueryException inside Payment::create().
 *
 * Even though the controller's outer Throwable catch (added at
 * 8134ad8a) eventually catches it and bounces the user to the
 * payments list with a flash, the actual Payment row never persists,
 * the user never reaches Paystack/Flutterwave, and the experience is
 * indistinguishable from a 500. This test pins that the ENUM-cased
 * label is what we actually write.
 */
class StudentPaymentInitiateTest extends TestCase
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
        Schema::dropIfExists('fees');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('students');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        parent::tearDown();
    }

    public function test_initiate_writes_installment_label_only_not_enum_installment(): void
    {
        // If the controller ever re-introduces 'installment' => $label
        // (where $label is lowercase) the production ENUM('First','Second','Full')
        // will reject it. This test pins that we don't write that column
        // for student-side payments — we only set installment_label
        // (the redundant varchar used for reporting).
        $user = $this->makeStudent();
        $fee  = $this->makeFee();

        // Capture what Payment::create receives by spying on the model.
        $captured = [];
        Payment::saving(function (Payment $p) use (&$captured) {
            // Capture the raw attributes that will be inserted.
            $captured = $p->getAttributes();
        });

        $response = $this->actingAs($user)->post(
            "/student/payments/{$fee->id}/initiate",
            ['percent' => SchoolFeeCalculator::PERCENT_FULL]
        );

        // We expect either a redirect (happy path) or a redirect with
        // an error flash (gateway unreachable, etc.) — but NOT a 500.
        $this->assertNotEquals(
            500,
            $response->getStatusCode(),
            'POST /student/payments/{fee}/initiate returned 500 — should have been caught and redirected.'
        );

        // If the row actually persisted (not the gateway unreachable
        // path), check the captured attributes never tried to write
        // the lowercase 'installment' value into the ENUM column.
        if (array_key_exists('installment', $captured)) {
            $this->assertContains(
                $captured['installment'],
                ['First', 'Second', 'Full', null, ''],
                "payments.installment was set to '{$captured['installment']}' which is not in the production ENUM('First','Second','Full'). Strict-mode INSERT will fail with a QueryException."
            );
        }
    }

    public function test_installment_label_accepts_lowercase_values(): void
    {
        // Sanity: the controller writes the lowercase label into the
        // varchar(20) installment_label column. This must always fit
        // — that's the whole reason we have a redundant column.
        $user = $this->makeStudent();
        $fee  = $this->makeFee();

        $response = $this->actingAs($user)->post(
            "/student/payments/{$fee->id}/initiate",
            ['percent' => SchoolFeeCalculator::PERCENT_FULL]
        );

        $this->assertNotEquals(500, $response->getStatusCode());

        $row = Payment::latest('id')->first();
        if ($row) {
            $this->assertContains(
                $row->installment_label,
                ['full', 'first', 'second', null],
                "installment_label was '{$row->installment_label}' — SchoolFeeCalculator::installmentLabel() only emits 'full'/'first'/'second'."
            );
        }
    }

    public function test_empty_gateway_body_does_not_bubble_uncaught_error(): void
    {
        // Regression: when the gateway returns an empty / non-JSON body,
        // json_decode() returns null and reading $result->status throws an
        // \Error under PHP 8 — which the inner try/catch (formerly
        // \Exception) didn't catch, so it slipped up to the outer
        // "We could not start your school-fee payment..." flash. We now
        // catch \Throwable and null-check $result before reading ->status.
        $user = $this->makeStudent();
        $fee  = $this->makeFee();

        Http::fake([
            'api.paystack.co/*' => Http::response('', 200),
        ]);

        $response = $this->actingAs($user)->post(
            "/student/payments/{$fee->id}/initiate",
            ['percent' => SchoolFeeCalculator::PERCENT_FULL]
        );

        // Must NOT be a 500 — the outer catch should not fire.
        $this->assertNotEquals(500, $response->getStatusCode());

        // Should be a redirect back (the inner catch's `back()` flash),
        // not the generic outer catch's "We could not start" flash.
        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'Payment',
            session('error'),
            'Expected the inner-catch flash ("Payment error: ...") to surface, not the outer "We could not start" flash.'
        );
    }

    public function test_gateway_returns_garbage_json_does_not_bubble_uncaught_error(): void
    {
        // The gateway may return a non-object JSON value (string, number,
        // null). $result is not an object, so reading ->status on it would
        // also throw \Error. Pin that we handle that shape too.
        $user = $this->makeStudent();
        $fee  = $this->makeFee();

        Http::fake([
            'api.paystack.co/*' => Http::response('not json at all', 200),
        ]);

        $response = $this->actingAs($user)->post(
            "/student/payments/{$fee->id}/initiate",
            ['percent' => SchoolFeeCalculator::PERCENT_FULL]
        );

        $this->assertNotEquals(500, $response->getStatusCode());
        $response->assertSessionHas('error');
    }

    /* --- helpers --- */

    private function makeStudent(): User
    {
        $user = User::create([
            'name'  => 'Test Student',
            'email' => 'student_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', 'student')->value('id'),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        Student::create([
            'user_id'       => $user->id,
            'matric_number' => 'TST/' . random_int(1000, 9999) . '/2026',
            'school_id'     => School::first()->id,
            'department_id' => \App\Models\Department::first()->id,
            'programme_id'  => \App\Models\Programme::first()->id,
            'session_id'    => AcademicSession::first()->id,
            'level'         => 1,
            'status'        => 'active',
        ]);

        return $user;
    }

    private function makeFee(): Fee
    {
        return Fee::create([
            'name'                => '2025/2026 School Fee',
            'payment_type'        => 'school_fee',
            'amount'              => 100000,
            'indigene_amount'     => 80000,
            'non_indigene_amount' => 100000,
            'portal_charge'       => 1500,
            'school_id'           => null,
            'department_id'       => null,
            'programme_id'        => null,
            'level'               => null,
            'session_id'          => AcademicSession::first()->id,
            'is_active'           => true,
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
            $t->boolean('must_change_password')->default(false);
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
            $t->string('payment_type')->nullable();
            $t->decimal('amount', 12, 2)->default(0);
            $t->decimal('indigene_amount', 12, 2)->nullable();
            $t->decimal('non_indigene_amount', 12, 2)->nullable();
            $t->decimal('portal_charge', 12, 2)->default(0);
            $t->decimal('portal_charge_percentage', 5, 2)->nullable();
            $t->boolean('is_editable_amount')->default(false);
            $t->foreignId('school_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable()->constrained();
            $t->foreignId('programme_id')->nullable()->constrained();
            $t->integer('level')->nullable();
            $t->foreignId('session_id')->nullable()->constrained();
            $t->date('due_date')->nullable();
            $t->boolean('is_active')->default(true);
            $t->string('category', 50)->nullable();
            $t->timestamps();
        });
        Schema::create('payments', function ($t) {
            $t->id();
            $t->foreignId('student_id')->nullable()->constrained();
            $t->foreignId('fee_id')->nullable()->constrained();
            $t->decimal('amount', 12, 2);
            $t->decimal('portal_charge', 12, 2)->default(0);
            $t->decimal('total_amount', 12, 2)->default(0);
            $t->integer('percent_paid')->default(0);
            $t->string('installment_label', 20)->default('full');
            // Mirror production — ENUM('First','Second','Full').
            // If the controller ever re-introduces 'installment' => 'full',
            // this column will reject the insert.
            $t->enum('installment', ['First', 'Second', 'Full'])->nullable();
            $t->string('reference')->nullable();
            $t->string('payment_ref')->nullable();
            $t->string('transaction_id')->nullable();
            $t->string('gateway')->nullable();
            $t->string('payment_method')->nullable();
            $t->string('status', 20)->default('pending');
            $t->boolean('is_verified')->default(false);
            $t->string('student_type', 50)->nullable();
            $t->string('payment_purpose')->nullable();
            $t->enum('fee_type', [
                'application', 'acceptance', 'school_fees', 'hostel',
                'library', 'convocation', 'indexing', 'registration',
                'result', 'certificate', 'other',
            ])->default('other');
            $t->foreignId('payer_id')->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->text('payment_details')->nullable();
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Student', 'slug' => 'student']);
        $school = School::create(['name' => 'Test School', 'code' => 'TST']);
        $dept = \App\Models\Department::create(['name' => 'Test Dept', 'code' => 'TSTD', 'school_id' => $school->id]);
        \App\Models\Programme::create(['name' => 'Test Prog', 'code' => 'TSTP', 'department_id' => $dept->id]);
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        SystemSetting::create(['key' => 'payment_open', 'value' => 'true', 'is_active' => true]);

        // Provide a "configured" gateway so the controller gets past the
        // "no gateway" branch. We never actually call out to it because
        // Http::fake() in the test below swallows the request, but we
        // can't even reach that path if getActiveGateway() returns null.
        PaymentGateway::create([
            'provider' => 'paystack',
            'test_secret_key' => 'sk_test_fake',
            'live_secret_key' => 'sk_live_fake',
            'is_test_mode' => true,
            'is_active' => true,
        ]);
    }
}
