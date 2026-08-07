<?php

namespace Tests\Feature\Applicant;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\PaymentType;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the "Payment Progress" widget on the applicant dashboard so the
 * three badges (Application / Acceptance / Compulsory) flip from
 * Pending / Locked / Locked to Paid as each fee clears.
 *
 * Regression: the Compulsory Fee badge was reading
 * `$applicant->hasPaid(PURPOSE_SCHOOL_FEE)` even though the
 * applicant→student migration trigger is PURPOSE_COMPULSORY — so an
 * applicant who paid compulsory still saw "Locked" instead of "Paid",
 * which made it look like the migration never happened. This test
 * asserts the badge source is PURPOSE_COMPULSORY.
 *
 * Uses a hand-rolled schema instead of RefreshDatabase to keep
 * independent of the (numerous) pre-existing migration issues.
 */
class ApplicantDashboardPaymentProgressTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('local_governments');
        Schema::dropIfExists('states');
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

    public function test_compulsory_paid_badge_shows_paid_after_paying_compulsory(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
            'compulsory_paid_at'   => now(),
        ]);

        $response = $this->actingAs($applicant->user)->get('/applicant/dashboard');
        $response->assertOk();

        // The Compulsory Fee badge must show "Paid" when the applicant
        // has paid compulsory. The dashboard's hasPaid() check used to
        // read PURPOSE_SCHOOL_FEE — which the applicant never paid, so
        // the badge stayed on "Locked" forever.
        $body = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/Compulsory Fee[\s\S]{0,2000}?Paid/i',
            $body,
            'Compulsory Fee badge did not flip to Paid after compulsory_paid_at was set.'
        );
    }

    public function test_compulsory_badge_shows_locked_before_paying(): void
    {
        $applicant = $this->makeAdmittedApplicant();
        $applicant->update([
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
            // compulsory_paid_at NOT set
        ]);

        $response = $this->actingAs($applicant->user)->get('/applicant/dashboard');
        $response->assertOk();

        $body = $response->getContent();
        // The Compulsory Fee block must still render "Locked" — the
        // applicant hasn't paid it yet. This pins that the badge logic
        // actually checks compulsory_paid_at, not some other column.
        $this->assertMatchesRegularExpression(
            '/Compulsory Fee[\s\S]{0,2000}?Locked/i',
            $body,
            'Compulsory Fee badge should still show Locked before payment.'
        );
    }

    /* --- helpers --- */

    private function makeAdmittedApplicant(): Applicant
    {
        $user = User::create([
            'name' => 'Dashboard Test Applicant',
            'email' => 'dash_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', 'applicant')->value('id'),
            'is_active' => true,
        ]);

        return Applicant::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'application_number' => Applicant::generateApplicationNumber(),
            'status' => 'admitted',
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
            $t->string('reference')->nullable();
            $t->string('transaction_id')->nullable();
            $t->string('gateway')->nullable();
            $t->string('payment_method')->nullable();
            $t->string('status', 20)->default('pending');
            $t->boolean('is_verified')->default(false);
            $t->string('student_type')->nullable();
            $t->string('payment_purpose')->nullable();
            $t->string('fee_type')->nullable();
            $t->foreignId('payer_id')->nullable();
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

        // Tables the dashboard controller touches. Kept minimal —
        // ExternalPayment for the dashboard's "external payment?" check,
        // states/lgas for the application form. The form's State dropdown
        // triggers a 500 if the table is missing.
        Schema::create('external_payments', function ($t) {
            $t->id();
            $t->foreignId('applicant_id')->nullable();
            $t->foreignId('payment_type_id')->nullable();
            $t->string('reference')->nullable();
            $t->string('status', 20)->default('pending');
            $t->decimal('amount', 12, 2)->default(0);
            $t->string('gateway')->nullable();
            $t->string('payment_method')->nullable();
            $t->string('transaction_id')->nullable();
            $t->text('payment_details')->nullable();
            $t->dateTime('paid_at')->nullable();
            $t->timestamps();
        });
        Schema::create('states', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->timestamps();
        });
        Schema::create('local_governments', function ($t) {
            $t->id();
            $t->string('name');
            $t->foreignId('state_id')->constrained();
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Applicant', 'slug' => 'applicant']);
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
            'name' => 'Compulsory Fee',
            'code' => 'COMP_FEE',
            'purpose' => PaymentType::PURPOSE_COMPULSORY,
            'amount' => 30000,
            'audience' => PaymentType::AUDIENCE_APPLICANT,
        ]);
    }
}
