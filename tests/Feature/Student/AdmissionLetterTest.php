<?php

namespace Tests\Feature\Student;

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
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins that a student who has been migrated from an applicant can
 * still view / print their admission letter from the student portal —
 * the user said "after a applicant have been tranfered to the student
 * should still have access to its application page should in case it
 * still wanted to print the admission letter".
 *
 * The applicant-side printAdmissionLetter() is unreachable for a
 * migrated user because they get signed into the student portal (no
 * separate applicant account exists). So this route + controller are
 * the bridge.
 */
class AdmissionLetterTest extends TestCase
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
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('students');
        Schema::dropIfExists('users');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('system_settings');
        parent::tearDown();
    }

    /**
     * Happy path: migrated student (linked applicant_id, status=admitted,
     * acceptance paid) hits the route and gets the rendered letter.
     */
    public function test_migrated_student_can_view_admission_letter(): void
    {
        $student = $this->makeMigratedStudent([
            'status'              => 'admitted',
            'acceptance_paid_at'  => now(),
        ]);

        $response = $this->actingAs($student->user)
            ->get(route('student.admission-letter'));

        $response->assertOk();
        $this->assertStringContainsString(
            $student->matric_number,
            $response->getContent(),
            'Letter must render the student matric number.'
        );
        $this->assertStringContainsString(
            'Admission Letter',
            $response->getContent(),
            'Letter view heading must be present.'
        );
    }

    /**
     * Gate: applicant status must be 'admitted'. A pending applicant
     * (e.g. paid compulsory but not yet admitted by registrar) should
     * be 403'd, not silently shown a half-baked letter.
     */
    public function test_pending_applicant_blocked_with_403(): void
    {
        $student = $this->makeMigratedStudent([
            'status'              => 'pending',
            'acceptance_paid_at'  => now(),
        ]);

        $response = $this->actingAs($student->user)
            ->get(route('student.admission-letter'));

        $response->assertStatus(403);
    }

    /**
     * Gate: student whose linked applicant has NOT paid the acceptance
     * fee must be blocked, even if status=admitted. The acceptance fee
     * is the registrar's confirmation that the student accepted the
     * offer.
     */
    public function test_unpaid_acceptance_fee_blocks_letter(): void
    {
        $student = $this->makeMigratedStudent([
            'status'              => 'admitted',
            'acceptance_paid_at'  => null,
        ]);

        $response = $this->actingAs($student->user)
            ->get(route('student.admission-letter'));

        $response->assertStatus(403);
    }

    /**
     * A legacy student row (no applicant_id link) must 404 rather than
     * silently loading a different student. The controller's first
     * check is `$student->applicant_id` — if it isn't set, the route
     * cannot resolve an admission letter.
     */
    public function test_student_without_applicant_link_404s(): void
    {
        $user = User::create([
            'name'  => 'Legacy Student',
            'email' => 'legacy_' . uniqid() . '@example.com',
            'password' => bcrypt('whatever'),
            'role_id' => Role::where('slug', 'student')->value('id'),
            'is_active' => true,
        ]);

        Student::create([
            'user_id'       => $user->id,
            'matric_number' => 'LEG/' . random_int(1000, 9999) . '/2026',
            'school_id'     => $this->school->id,
            'department_id' => $this->dept->id,
            'programme_id'  => $this->prog->id,
            'session_id'    => $this->session->id,
            'level'         => 1,
            'status'        => 'active',
            // Note: NO applicant_id.
        ]);

        $response = $this->actingAs($user)
            ->get(route('student.admission-letter'));

        $response->assertStatus(404);
    }

    /**
     * The route must require auth. The role:student middleware on the
     * student prefix group already enforces this; we don't need to
     * repeat, but pinning it catches any future middleware removal.
     */
    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $student = $this->makeMigratedStudent([
            'status' => 'admitted',
            'acceptance_paid_at' => now(),
        ]);

        // Bypass actingAs to test the auth middleware.
        $response = $this->get(route('student.admission-letter'));

        // Either 302 to login OR 403 — both acceptable "blocked" responses.
        $this->assertContains($response->getStatusCode(), [302, 403]);
    }

    /* --- helpers --- */

    /**
     * Build a Student row whose `applicant_id` is set to a synthetic
     * Applicant row. Mirrors what ApplicantPaymentService::migrateApplicantToStudent
     * does in production.
     */
    private function makeMigratedStudent(array $applicantOverrides): Student
    {
        $user = User::create([
            'name'  => 'Migrated Student',
            'email' => 'migrated_' . uniqid() . '@example.com',
            'password' => bcrypt('whatever'),
            'role_id' => Role::where('slug', 'student')->value('id'),
            'is_active' => true,
        ]);

        $applicant = Applicant::create(array_merge([
            'user_id'         => $user->id,
            'application_number' => 'APP-' . strtoupper(uniqid()),
            'surname'         => 'Migrated',
            'first_name'      => 'Student',
            'email'           => $user->email,
            'school_id'       => $this->school->id,
            'department_id'   => $this->dept->id,
            'programme_id'    => $this->prog->id,
            'session_id'      => $this->session->id,
        ], $applicantOverrides));

        return Student::create([
            'user_id'       => $user->id,
            'matric_number' => 'MIG/' . random_int(1000, 9999) . '/2026',
            'school_id'     => $this->school->id,
            'department_id' => $this->dept->id,
            'programme_id'  => $this->prog->id,
            'session_id'    => $this->session->id,
            'level'         => 1,
            'status'        => 'active',
            'applicant_id'  => $applicant->id,
        ]);
    }

    private School $school;
    private Department $dept;
    private Programme $prog;
    private AcademicSession $session;
    private PaymentType $acceptanceType;

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Student', 'slug' => 'student']);
        $this->school = School::create(['name' => 'Test School', 'code' => 'TST']);
        $this->dept = Department::create([
            'name' => 'Computer Studies',
            'code' => 'COM',
            'school_id' => $this->school->id,
        ]);
        $this->prog = Programme::create([
            'name' => 'Computer Science',
            'code' => 'CSC',
            'department_id' => $this->dept->id,
        ]);
        $this->session = AcademicSession::create([
            'name' => '2025/2026',
            'is_current' => true,
        ]);

        $this->acceptanceType = PaymentType::create([
            'purpose' => PaymentType::PURPOSE_ACCEPTANCE,
            'name'    => 'Acceptance Fee',
            'amount'  => 25000,
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
            $t->id();
            $t->string('name');
            $t->boolean('is_current')->default(false);
            $t->timestamps();
        });
        Schema::create('payment_types', function ($t) {
            $t->id();
            $t->string('purpose', 64);
            $t->string('name');
            $t->decimal('amount', 10, 2);
            $t->timestamps();
        });
        Schema::create('fees', function ($t) {
            $t->id();
            $t->string('name');
            $t->decimal('amount', 10, 2);
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
            $t->timestamp('password_changed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('applicants', function ($t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('application_number')->unique();
            $t->string('surname')->nullable();
            $t->string('first_name')->nullable();
            $t->string('email')->nullable();
            $t->foreignId('school_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable()->constrained();
            $t->foreignId('programme_id')->nullable()->constrained();
            $t->foreignId('session_id')->nullable()->constrained();
            $t->string('status', 30)->default('draft');
            $t->timestamp('acceptance_paid_at')->nullable();
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
            $t->unsignedBigInteger('applicant_id')->nullable();
            $t->timestamps();
        });
        Schema::create('payments', function ($t) {
            $t->id();
            $t->foreignId('applicant_id')->nullable()->constrained();
            $t->foreignId('fee_id')->nullable()->constrained();
            $t->decimal('amount', 10, 2);
            $t->string('status', 30)->default('pending');
            $t->timestamps();
        });
        Schema::create('system_settings', function ($t) {
            // The applicant.admission-letter view reads SystemSetting
            // for institution_name/address/phone/email/website,
            // registrar_name, registrar_signature_path. Without the table
            // the view crashes on a "no such table" PDOException.
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->string('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }
}