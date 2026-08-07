<?php

namespace Tests\Feature\Registrar;

use App\Models\Department;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Services\MatricNumberService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the matric number format the user wants: EKSCOTECH/COM/26/001.
 *
 * Old format: `2026/COM/0001`  — 4-digit year, 4-digit sequence
 * New format: `EKSCOTECH/COM/26/001`  — institution_code / dept / 2-digit year / 3-digit sequence
 *
 * The institution_code is sourced from `system_settings.institution_code`.
 * If unset we fall back to a 3-letter uppercase prefix derived from
 * `institution_name`. If that's also unset, we use "APP".
 */
class MatricNumberFormatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('students');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('users');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_format_uses_institution_code_2digit_year_and_3digit_sequence(): void
    {
        SystemSetting::create([
            'key' => 'institution_code',
            'value' => 'EKSCOTECH',
            'is_active' => true,
        ]);

        $applicant = $this->makeApplicant();
        $matric = MatricNumberService::generate($applicant);

        // Must look like EKSCOTECH/COM/26/001 (year is the last 2 of current year).
        $expectedYear = substr((string) date('Y'), -2);
        $this->assertMatchesRegularExpression(
            '#^EKSCOTECH/COM/' . $expectedYear . '/\d{3}$#',
            $matric,
            "Matric '{$matric}' did not match the new format EKSCOTECH/COM/{$expectedYear}/NNN."
        );
    }

    public function test_first_student_in_dept_gets_sequence_001(): void
    {
        SystemSetting::create([
            'key' => 'institution_code',
            'value' => 'EKSCOTECH',
            'is_active' => true,
        ]);

        $applicant = $this->makeApplicant();
        $matric = MatricNumberService::generate($applicant);

        // First in the (department, year) pair — sequence 001.
        $expectedYear = substr((string) date('Y'), -2);
        $this->assertEquals(
            "EKSCOTECH/COM/{$expectedYear}/001",
            $matric,
            "First student in the COM/{$expectedYear} pair must be 001."
        );
    }

    public function test_second_student_in_same_dept_year_increments_sequence(): void
    {
        SystemSetting::create([
            'key' => 'institution_code',
            'value' => 'EKSCOTECH',
            'is_active' => true,
        ]);

        $year = (int) date('Y');
        $expectedYear = substr((string) $year, -2);

        // Pre-seed an existing student row for this dept/year pair.
        Student::create([
            'matric_number' => "EKSCOTECH/COM/{$expectedYear}/001",
            'department_id' => $this->dept->id,
            'level' => 1,
            'status' => 'active',
        ]);

        $applicant = $this->makeApplicant();
        $matric = MatricNumberService::generate($applicant);

        $this->assertEquals(
            "EKSCOTECH/COM/{$expectedYear}/002",
            $matric,
            'Second student must be 002 — the per-(dept,year) counter must walk forward.'
        );
    }

    public function test_uniqueness_guard_walks_sequence_when_colliding(): void
    {
        SystemSetting::create([
            'key' => 'institution_code',
            'value' => 'EKSCOTECH',
            'is_active' => true,
        ]);

        $year = (int) date('Y');
        $expectedYear = substr((string) $year, -2);

        // Pre-seed 001 AND 002 so generate() must walk to 003.
        Student::create([
            'matric_number' => "EKSCOTECH/COM/{$expectedYear}/001",
            'department_id' => $this->dept->id,
            'level' => 1,
            'status' => 'active',
        ]);
        Student::create([
            'matric_number' => "EKSCOTECH/COM/{$expectedYear}/002",
            'department_id' => $this->dept->id,
            'level' => 1,
            'status' => 'active',
        ]);

        $applicant = $this->makeApplicant();
        $matric = MatricNumberService::generate($applicant);

        $this->assertEquals(
            "EKSCOTECH/COM/{$expectedYear}/003",
            $matric,
            'When 001 and 002 are taken the generator must walk to 003, not collide.'
        );
    }

    public function test_falls_back_to_institution_name_prefix_when_code_unset(): void
    {
        // No institution_code set — but institution_name is.
        SystemSetting::create([
            'key' => 'institution_name',
            'value' => 'Ekiti State College of Technology',
            'is_active' => true,
        ]);

        $applicant = $this->makeApplicant();
        $matric = MatricNumberService::generate($applicant);

        // Fallback: strip non-alphanumerics from institution_name → uppercase →
        // first 3 letters. "Ekiti State College of Technology" → "EKI".
        // (Note: to get the user's preferred "EKSCOTECH" the registrar must
        // set institution_code explicitly — this test pins the fallback, not
        // the override.)
        $this->assertStringStartsWith('EKI/COM/', $matric, "Expected fallback to 'EKI' prefix from institution_name; got '{$matric}'.");
    }

    public function test_legacy_4digit_year_matrices_do_not_inflate_new_counter(): void
    {
        // Sanity: a student with the OLD format (2024/COM/0001) must NOT
        // bump the new-format counter. The new counter uses
        // LIKE '.../YY/...' so the old format never matches.
        SystemSetting::create([
            'key' => 'institution_code',
            'value' => 'EKSCOTECH',
            'is_active' => true,
        ]);

        Student::create([
            'matric_number' => '2024/COM/0001',
            'department_id' => $this->dept->id,
            'level' => 1,
            'status' => 'active',
        ]);

        $applicant = $this->makeApplicant();
        $matric = MatricNumberService::generate($applicant);

        $year = (int) date('Y');
        $expectedYear = substr((string) $year, -2);
        $this->assertEquals(
            "EKSCOTECH/COM/{$expectedYear}/001",
            $matric,
            "Old-format matric '2024/COM/0001' must not affect the new counter — expected 001, got '{$matric}'."
        );
    }

    /* --- helpers --- */

    private School $school;
    private Department $dept;
    private Programme $prog;
    private AcademicSession $session;

    private function makeApplicant(): \App\Models\Applicant
    {
        return \App\Models\Applicant::create([
            'application_number' => 'APP/' . random_int(10000, 99999),
            'first_name' => 'Test',
            'surname' => 'Applicant',
            'email' => 'applicant_' . uniqid() . '@example.com',
            'phone' => '08000000000',
            'gender' => 'Male',
            'school_id' => $this->school->id,
            'department_id' => $this->dept->id,
            'programme_id' => $this->prog->id,
            'session_id' => $this->session->id,
            'status' => 'admitted',
        ]);
    }

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
        Schema::create('system_settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('applicants', function ($t) {
            $t->id();
            $t->string('application_number')->unique();
            $t->string('first_name')->nullable();
            $t->string('surname')->nullable();
            $t->string('middle_name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('gender', 10)->nullable();
            $t->foreignId('school_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable()->constrained();
            $t->foreignId('programme_id')->nullable()->constrained();
            $t->foreignId('session_id')->nullable()->constrained();
            $t->string('status', 20)->default('pending');
            $t->boolean('student_created')->default(false);
            $t->string('matric_number')->nullable();
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
    }
}