<?php

namespace Tests\Feature\Applicant;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\PaymentType;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the "Go to Student Portal" auto-login flow exposed to applicants
 * via /applicant/auto-login (Applicant\AutoLoginController@issue).
 *
 * The controller delegates to the existing student-side
 * AutoLoginController::generateForStudent(), which mints a signed URL
 * bound to the user id, flips must_change_password=true, and returns
 * a URL that the student-side consume() endpoint will use to sign the
 * applicant in and bounce them to /student/password/change-required.
 *
 * Pinned behaviour:
 *   - applicant with student_id set → 302 to /student/auto-login/{id}?signature=...
 *   - applicant without student_id → bounced back to dashboard with error flash
 *   - applicant with student_id but no student row → bounced back with error
 *   - GET request flips must_change_password=true on the user before redirect
 *
 * We re-use the same anonymous-table schema shape as
 * ApplicantDashboardLoadTest so these tests stay independent of the
 * production DB and run cleanly in CI.
 */
class ApplicantAutoLoginTest extends TestCase
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
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('students');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('users');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_issue_redirects_to_signed_auto_login_url_for_migrated_applicant(): void
    {
        $applicant = $this->makeMigratedApplicant();

        $response = $this->actingAs($applicant->user)->get('/applicant/auto-login');

        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertNotEmpty($location, 'Auto-login must produce a redirect target.');

        // The signed URL points at the student-side consume endpoint and
        // carries a Laravel signature query param.
        $this->assertMatchesRegularExpression(
            '#/student/auto-login/\d+#',
            $location,
            "Auto-login URL '{$location}' must target the student-side consume endpoint."
        );
        $this->assertStringContainsString(
            'signature=',
            $location,
            'Auto-login URL must be signed (tamper-proof).'
        );
    }

    public function test_issue_flips_must_change_password_on_user(): void
    {
        $applicant = $this->makeMigratedApplicant();

        $userId = $applicant->user_id;
        $this->assertFalse(
            User::find($userId)->must_change_password,
            'Pre-condition: must_change_password is false before we issue the link.'
        );

        $this->actingAs($applicant->user)->get('/applicant/auto-login');

        $this->assertTrue(
            User::find($userId)->must_change_password,
            'Issuing the auto-login link must flip must_change_password=true so the onboarding middleware gates the user.'
        );
    }

    public function test_issue_rejects_applicant_without_student_id(): void
    {
        // Admitted but not yet migrated to a Student row.
        $applicant = $this->makeAdmittedApplicant();

        $response = $this->actingAs($applicant->user)->get('/applicant/auto-login');

        $response->assertStatus(302);
        $response->assertRedirect(route('applicant.dashboard'));
        $response->assertSessionHas('error');

        $this->assertFalse(
            User::find($applicant->user_id)->must_change_password,
            'must_change_password must NOT be flipped for an applicant who is not yet migrated.'
        );
    }

    public function test_issue_rejects_applicant_with_student_id_but_missing_student_row(): void
    {
        // Edge case: applicants.student_id is set but the corresponding
        // students row has been deleted out from under us. The controller
        // must surface an error rather than crashing.
        $applicant = $this->makeAdmittedApplicant();
        $applicant->update(['student_id' => 999999]);

        $response = $this->actingAs($applicant->user)->get('/applicant/auto-login');

        $response->assertStatus(302);
        $response->assertRedirect(route('applicant.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_issue_requires_authenticated_user(): void
    {
        // No actingAs — anonymous request must be redirected to login.
        $applicant = $this->makeMigratedApplicant();

        $response = $this->get('/applicant/auto-login');

        $response->assertStatus(302);
        $this->assertStringContainsString(
            '/login',
            $response->headers->get('Location') ?? '',
            'Anonymous request must be redirected to the login page.'
        );
    }

    public function test_dashboard_renders_go_to_student_portal_button_for_migrated_applicant(): void
    {
        $applicant = $this->makeMigratedApplicant();

        $response = $this->actingAs($applicant->user)->get('/applicant/dashboard');

        $response->assertOk();
        $response->assertSee('Student Portal Access', false);
        $response->assertSee('Go to Student Portal', false);
        $this->assertStringContainsString(
            route('applicant.auto-login.issue'),
            $response->getContent(),
            'The dashboard must link to the applicant.auto-login.issue route.'
        );
    }

    public function test_dashboard_does_not_render_go_to_student_portal_button_for_unmigrated_applicant(): void
    {
        $applicant = $this->makeAdmittedApplicant();

        $response = $this->actingAs($applicant->user)->get('/applicant/dashboard');

        $response->assertOk();
        $response->assertDontSee('Student Portal Access', false);
        $response->assertDontSee('Go to Student Portal', false);
    }

    /* --- helpers --- */

    private function makeMigratedApplicant(): Applicant
    {
        $applicant = $this->makeAdmittedApplicant();

        // Create the corresponding Student row that the migration would
        // have produced (ApplicantPaymentService::migrateApplicantToStudent).
        $student = Student::create([
            'user_id'       => $applicant->user_id,
            'matric_number' => 'EKSCOTECH/COM/' . substr((string) date('Y'), -2) . '/001',
            'school_id'     => $applicant->school_id,
            'department_id' => $applicant->department_id,
            'programme_id'  => $applicant->programme_id,
            'session_id'    => $applicant->session_id,
            'level'         => 1,
            'status'        => 'active',
        ]);

        // Promote the user's role to student (mirrors the migration step).
        $studentRole = Role::where('slug', 'student')->first();
        if ($studentRole) {
            User::where('id', $applicant->user_id)->update(['role_id' => $studentRole->id]);
        }

        $applicant->update([
            'student_id'           => $student->id,
            'matric_number'        => $student->matric_number,
            'migrated_to_student_at' => now(),
        ]);

        return $applicant->refresh();
    }

    private function makeAdmittedApplicant(): Applicant
    {
        $user = User::create([
            'name'  => 'Admitted Applicant',
            'email' => 'admitted_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id'  => Role::where('slug', 'applicant')->value('id'),
            'is_active' => true,
        ]);

        return Applicant::create([
            'user_id'            => $user->id,
            'email'              => $user->email,
            'application_number' => Applicant::generateApplicationNumber(),
            'status'             => 'admitted',
            'school_id'          => School::first()->id,
            'department_id'      => Department::first()->id,
            'programme_id'       => Programme::first()->id,
            'session_id'         => AcademicSession::first()->id,
        ]);
    }

    private function buildSchema(): void
    {
        Schema::create('roles', function ($t) {
            $t->id(); $t->string('name'); $t->string('slug')->unique(); $t->timestamps();
        });
        Schema::create('schools', function ($t) {
            $t->id(); $t->string('name'); $t->string('code')->unique(); $t->timestamps();
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
            $t->id(); $t->string('name'); $t->boolean('is_current')->default(false); $t->timestamps();
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
        Schema::create('applicants', function ($t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('application_number')->unique();
            $t->string('first_name')->nullable();
            $t->string('surname')->nullable();
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
            $t->dateTime('application_paid_at')->nullable();
            $t->dateTime('acceptance_paid_at')->nullable();
            $t->dateTime('compulsory_paid_at')->nullable();
            $t->dateTime('migrated_to_student_at')->nullable();
            $t->foreignId('student_id')->nullable();
            $t->string('matric_number')->nullable();
            $t->integer('level')->nullable();
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
            $t->dateTime('payment_date')->nullable();
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
            $t->foreignId('applicant_id')->nullable();
            $t->string('reference')->nullable();
            $t->string('status', 20)->default('pending');
            $t->decimal('amount', 12, 2)->default(0);
            $t->string('gateway')->nullable();
            $t->string('transaction_id')->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->dateTime('paid_at')->nullable();
            $t->string('payment_status', 20)->default('pending');
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Applicant', 'slug' => 'applicant']);
        Role::create(['name' => 'Student',   'slug' => 'student']);
        $school = School::create(['name' => 'Test School', 'code' => 'TST']);
        $dept = Department::create([
            'name' => 'Computer Studies',
            'code' => 'COM',
            'school_id' => $school->id,
        ]);
        Programme::create(['name' => 'Computer Science', 'code' => 'CSC', 'department_id' => $dept->id]);
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        PaymentType::create([
            'name' => 'Application Form Fee', 'code' => 'APP_FORM',
            'purpose' => PaymentType::PURPOSE_APPLICATION,
            'amount' => 5000, 'audience' => PaymentType::AUDIENCE_APPLICANT,
            'is_active' => true, 'requires_payment' => true, 'priority' => 1,
        ]);
        PaymentType::create([
            'name' => 'Acceptance Fee', 'code' => 'ACCEPT_FEE',
            'purpose' => PaymentType::PURPOSE_ACCEPTANCE,
            'amount' => 25000, 'audience' => PaymentType::AUDIENCE_APPLICANT,
            'is_active' => true, 'requires_payment' => true, 'priority' => 2,
        ]);
        PaymentType::create([
            'name' => 'Compulsory Fee', 'code' => 'COMP_FEE',
            'purpose' => PaymentType::PURPOSE_COMPULSORY,
            'amount' => 30000, 'audience' => PaymentType::AUDIENCE_APPLICANT,
            'is_active' => true, 'requires_payment' => true, 'priority' => 3,
        ]);
    }
}