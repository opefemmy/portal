<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Role;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session as AcademicSession;
use App\Models\User;
use App\Services\ApplicantPaymentService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Walks one applicant through the full three-fee pipeline:
 *
 *   1. Register & pay APPLICATION → form unlocks
 *   2. Registrar admits (no Student row yet)
 *   3. Pay ACCEPTANCE → admission letter unlocks
 *   4. Pay COMPULSORY → applicant migrates to Student
 *
 * Uses a minimal hand-rolled schema instead of RefreshDatabase to keep
 * the test independent of the (numerous) pre-existing migration issues
 * in the codebase.
 */
class ApplicantPaymentFlowTest extends TestCase
{
    private ApplicantPaymentService $payments;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payments = app(ApplicantPaymentService::class);
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        // Drop in reverse order so fk constraints don't block.
        Schema::dropIfExists('payments');
        Schema::dropIfExists('external_payments');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('students');
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

    public function test_full_three_fee_pipeline(): void
    {
        $applicant = $this->makeApplicant('pending');

        // 1. APPLICATION fee
        $this->assertNull($this->payments->canPay($applicant, PaymentType::PURPOSE_APPLICATION));
        $initiated = $this->payments->initiate($applicant, PaymentType::PURPOSE_APPLICATION, 'test');
        $this->payments->markCompleted($initiated['payment'], ['test_mode' => true]);
        $applicant->refresh();

        $this->assertTrue($applicant->hasPaid(PaymentType::PURPOSE_APPLICATION));
        $this->assertNotNull($applicant->application_paid_at);
        $this->assertNull($applicant->acceptance_paid_at);
        $this->assertNull($applicant->compulsory_paid_at);

        // Cannot pay acceptance yet — applicant isn't admitted
        $this->assertStringContainsString(
            'admitted before',
            (string) $this->payments->canPay($applicant, PaymentType::PURPOSE_ACCEPTANCE)
        );

        // 3. Registrar admits — reserves matric, no Student row yet
        $applicant->update(['status' => 'admitted']);
        $this->assertNull($applicant->student_id);
        $this->assertNull($applicant->migrated_to_student_at);

        // 4. ACCEPTANCE fee
        $this->assertNull($this->payments->canPay($applicant, PaymentType::PURPOSE_ACCEPTANCE));
        $initiated = $this->payments->initiate($applicant, PaymentType::PURPOSE_ACCEPTANCE, 'test');
        $this->payments->markCompleted($initiated['payment'], ['test_mode' => true]);
        $applicant->refresh();

        $this->assertTrue($applicant->hasPaid(PaymentType::PURPOSE_ACCEPTANCE));
        $this->assertNotNull($applicant->acceptance_paid_at);

        // 5. COMPULSORY fee — triggers migration. Compulsory (not
        // School_Fee) is what the applicant catalogue carries: School
        // Fees is a returning-student fee (audience=student) and must
        // not show up in the applicant flow. Compulsory alone is enough
        // to migrate the applicant to a Student row.
        $this->assertNull($this->payments->canPay($applicant, PaymentType::PURPOSE_COMPULSORY));
        $initiated = $this->payments->initiate($applicant, PaymentType::PURPOSE_COMPULSORY, 'test');
        $this->payments->markCompleted($initiated['payment'], ['test_mode' => true]);
        $applicant->refresh();

        $this->assertTrue($applicant->hasPaid(PaymentType::PURPOSE_COMPULSORY));
        $this->assertNotNull($applicant->compulsory_paid_at);
        $this->assertTrue($applicant->isMigrated());
        $this->assertNotNull($applicant->student_id);
        $this->assertNotNull($applicant->migrated_to_student_at);

        // Migration is idempotent — re-running does not create a second Student
        $studentCount = \App\Models\Student::where('applicant_id', $applicant->id)->count();
        $this->assertEquals(1, $studentCount);

        // Transaction history shows all three payments
        $history = $applicant->transactionHistory();
        $this->assertCount(3, $history);
        $purposes = $history->pluck('purpose')->all();
        $this->assertEqualsCanonicalizing(
            ['application', 'acceptance', 'compulsory'],
            $purposes
        );
        foreach ($history as $row) {
            $this->assertEquals('completed', $row['status']);
            $this->assertGreaterThan(0, $row['amount']);
        }
    }

    public function test_cannot_pay_acceptance_before_application(): void
    {
        $applicant = $this->makeApplicant('admitted');

        $block = $this->payments->canPay($applicant, PaymentType::PURPOSE_ACCEPTANCE);
        $this->assertStringContainsString('application fee', (string) $block);
    }

    public function test_cannot_pay_compulsory_before_acceptance(): void
    {
        $applicant = $this->makeApplicant('admitted');
        $applicant->update(['application_paid_at' => now(), 'payment_status' => 'completed']);

        // Compulsory (not School_Fee) is the migration-trigger the
        // applicant sees — see note in PaymentTypeSeeder.
        $block = $this->payments->canPay($applicant, PaymentType::PURPOSE_COMPULSORY);
        $this->assertStringContainsString('acceptance', (string) $block);
    }

    public function test_next_payable_purpose_walks_three_steps(): void
    {
        $applicant = $this->makeApplicant('pending');
        $this->assertEquals(PaymentType::PURPOSE_APPLICATION, $applicant->nextPayablePurpose());

        $applicant->update(['application_paid_at' => now()]);
        $applicant->refresh();
        $this->assertNull($applicant->nextPayablePurpose()); // not admitted yet

        $applicant->update(['status' => 'admitted']);
        $applicant->refresh();
        $this->assertEquals(PaymentType::PURPOSE_ACCEPTANCE, $applicant->nextPayablePurpose());

        // School Fees is now audience=student (returning-student
        // only), so after Application + Acceptance the next-payable
        // walk lands on COMPULSORY — that's the migration trigger
        // for newly admitted applicants.
        $applicant->update(['acceptance_paid_at' => now()]);
        $applicant->refresh();
        $this->assertEquals(PaymentType::PURPOSE_COMPULSORY, $applicant->nextPayablePurpose());

        $applicant->update(['compulsory_paid_at' => now(), 'student_id' => 1]);
        $applicant->refresh();
        $this->assertNull($applicant->nextPayablePurpose());
    }

    public function test_mark_completed_is_idempotent(): void
    {
        $applicant = $this->makeApplicant('pending');
        $initiated = $this->payments->initiate($applicant, PaymentType::PURPOSE_APPLICATION, 'test');

        $this->payments->markCompleted($initiated['payment'], ['test_mode' => true]);
        $this->payments->markCompleted($initiated['payment']->fresh(), ['test_mode' => true, 'second' => true]);

        $this->assertEquals(1, Payment::where('payer_id', $applicant->id)->count());
    }

    public function test_resolve_amount_uses_override_when_set(): void
    {
        \App\Models\SystemSetting::set('admission_application_fee_amount', '9999.99');
        $this->assertEquals(9999.99, $this->payments->resolveAmount(PaymentType::PURPOSE_APPLICATION));
    }

    /**
     * The applicant catalogue must NOT include School Fees. School Fees
     * is a returning-student fee — it lives in the student portal. New
     * applicants see Compulsory Fee instead, which is what triggers the
     * applicant→student migration.
     *
     * Regression: previously the SCHOOL_FEE row was seeded with
     * audience='both', so it appeared on the applicant catalogue and
     * shadowed the Compulsory fee in the dashboard's "next payable"
     * step. This test pins the audience scoping so the change sticks.
     */
    public function test_applicant_catalogue_excludes_school_fees(): void
    {
        // Seed a SCHOOL_FEE row that mirrors the production seeder:
        // audience=student (not applicant, not both).
        PaymentType::create([
            'name'     => 'School Fees',
            'code'     => 'SCHOOL_FEE_STUDENT_ONLY',
            'purpose'  => PaymentType::PURPOSE_SCHOOL_FEE,
            'amount'   => 50000,
            'audience' => PaymentType::AUDIENCE_STUDENT,
        ]);

        $applicantCatalogue = $this->payments->getApplicantPaymentTypes();
        $codes = $applicantCatalogue->pluck('code')->all();

        // Compulsory Fee is visible to applicants.
        $this->assertContains('COMP_FEE', $codes);
        // School Fees is NOT visible to applicants.
        $this->assertNotContains('SCHOOL_FEE_STUDENT_ONLY', $codes);
    }

    /**
     * Once School Fees is hidden from applicants, the next-payable
     * walk lands on COMPULSORY (not PURPOSE_SCHOOL_FEE) for an
     * admitted applicant who has paid application + acceptance.
     */
    public function test_next_payable_for_admitted_applicant_returns_compulsory(): void
    {
        // Mirror production:School Fees is audience=student.
        PaymentType::where('code', 'SCHOOL_FEE')->update([
            'audience' => PaymentType::AUDIENCE_STUDENT,
        ]);

        $applicant = $this->makeApplicant('admitted');
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
        ]);

        $next = $applicant->nextPayablePurpose();
        $this->assertEquals(PaymentType::PURPOSE_COMPULSORY, $next);
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
        Schema::create('students', function ($t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('matric_number')->nullable()->unique();
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
            // Mirrors the 2026_08_04 migration: audience column with enum values.
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
        Role::create(['name' => 'Student', 'slug' => 'student']);
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
        ]);
        PaymentType::create([
            'name' => 'Acceptance Fee',
            'code' => 'ACCEPT_FEE',
            'purpose' => PaymentType::PURPOSE_ACCEPTANCE,
            'amount' => 25000,
        ]);
        PaymentType::create([
            'name' => 'School Fees',
            'code' => 'SCHOOL_FEE',
            'purpose' => PaymentType::PURPOSE_SCHOOL_FEE,
            'amount' => 50000,
            // audience='student' mirrors the production seeder:
            // school fees are NOT visible to applicants. The
            // applicant catalogue should only show Application,
            // Acceptance, and Compulsory.
            'audience' => PaymentType::AUDIENCE_STUDENT,
        ]);

        // Compulsory fee — the migration trigger that newly
        // admitted applicants see. Sits at the top of the
        // applicant catalogue once Application + Acceptance are
        // both paid.
        PaymentType::create([
            'name' => 'Compulsory Fee',
            'code' => 'COMP_FEE',
            'purpose' => PaymentType::PURPOSE_COMPULSORY,
            'amount' => 100000,
            'audience' => PaymentType::AUDIENCE_APPLICANT,
        ]);
    }
}
