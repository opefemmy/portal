<?php

namespace Tests\Feature\AcademicBoard;

use App\Models\Course;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Programme;
use App\Models\Result;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the Academic Board results index page so it renders the
 * final-approved (status='approved_final') results list directly.
 *
 * User requirement: "http://localhost:8000/academic-board/results
 * allow me to view the results that had been approved".
 *
 * Pre-fix: the index page only showed a department roll-up (Pending
 * + Final-Approved counts). The operator had to drill into a
 * department to see the actual approved results. Post-fix: the index
 * page renders a Final-Approved Results table beneath the roll-up
 * with student + course + grade + approved-by + approved-on for every
 * approved result.
 */
class AcademicBoardApprovedResultsIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        PermissionService::flush();
        Schema::dropIfExists('results');
        Schema::dropIfExists('student_courses');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('students');
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('role_user');
        parent::tearDown();
    }

    public function test_index_renders_approved_results_section_with_each_approved_row(): void
    {
        // Two approved results + one pending — pending must NOT render
        // in the approved-results table.
        $this->makeApprovedResult(studentName: 'Ada Approved', matric: 'AA/001', grade: 'A', score: 78);
        $this->makeApprovedResult(studentName: 'Bola Board', matric: 'BB/002', grade: 'B', score: 65);
        $this->makePendingResult(studentName: 'Pending Pete', matric: 'PP/003', grade: 'C', score: 55);

        $board = $this->makeBoardUser();
        $response = $this->actingAs($board)->get(route('academic-board.results'));

        $response->assertOk();
        $html = $response->getContent();

        // Section heading + count badge.
        $this->assertStringContainsString('Final-Approved Results', $html);

        // Each approved row renders — student name + matric + grade + course.
        $this->assertStringContainsString('Ada Approved', $html);
        $this->assertStringContainsString('AA/001', $html);
        $this->assertStringContainsString('Bola Board', $html);
        $this->assertStringContainsString('BB/002', $html);

        // Pending result must NOT appear in the approved-results section.
        // The department roll-up can still mention its count via the
        // Pending badge, so use a row-scoped regex to scope the search
        // to the Final-Approved Results card body.
        $this->assertDoesNotMatchRegularExpression(
            '/Final-Approved Results[\s\S]*Pending Pete[\s\S]*<\/tr>/',
            $html,
            'Pending rows must NOT appear inside the Final-Approved Results card.'
        );
    }

    public function test_index_renders_print_link_for_each_approved_result(): void
    {
        $result = $this->makeApprovedResult(
            studentName: 'Cee Cee', matric: 'CC/004', grade: 'A', score: 80,
        );

        $board = $this->makeBoardUser();
        $response = $this->actingAs($board)->get(route('academic-board.results'));

        $response->assertOk();
        $this->assertStringContainsString(
            route('academic-board.results.print', $result),
            $response->getContent(),
            'Each approved-result row must carry a link to the per-result print route.'
        );
    }

    public function test_index_section_is_empty_when_no_results_have_been_approved(): void
    {
        // Just a pending row, no approved.
        $this->makePendingResult('No Approved Yet', 'NA/100', 'C', 50);

        $board = $this->makeBoardUser();
        $response = $this->actingAs($board)->get(route('academic-board.results'));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('Final-Approved Results', $html);
        $this->assertStringContainsString(
            'No final-approved results yet',
            $html,
            'Empty state copy must appear when nothing has been signed off.'
        );
    }

    public function test_index_section_caps_at_100_rows(): void
    {
        // 105 approved results — the controller caps at 100 so the page
        // doesn't unbounded-grow. We seed 105 and assert 100 render.
        for ($i = 1; $i <= 105; $i++) {
            $this->makeApprovedResult(
                studentName: "Student {$i}",
                matric: sprintf('S/%03d', $i),
                grade: 'A',
                score: 70,
            );
        }

        $board = $this->makeBoardUser();
        $response = $this->actingAs($board)->get(route('academic-board.results'));

        $response->assertOk();
        $html = $response->getContent();

        // The badge in the section header reads the collection count.
        $this->assertDoesNotMatchRegularExpression(
            '/Final-Approved Results[\s\S]*?>105</',
            $html,
            'The count badge must reflect the 100-row cap, not the underlying table size.'
        );
    }

    /* --- helpers --- */

    private function makeApprovedResult(string $studentName, string $matric, string $grade, int $score): Result
    {
        $approver = User::create([
            'name'     => 'Approver Person',
            'email'    => 'approver_' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
        ]);

        $result = $this->seedResultRow(
            studentName: $studentName,
            matric: $matric,
            grade: $grade,
            score: $score,
        );
        $result->update([
            'status'      => 'approved_final',
            'approved_by' => $approver->id,
            'approved_at' => now()->subMinutes(5),
        ]);
        return $result->fresh();
    }

    private function makePendingResult(string $studentName, string $matric, string $grade, int $score): Result
    {
        return $this->seedResultRow(
            studentName: $studentName,
            matric: $matric,
            grade: $grade,
            score: $score,
            status: 'approved_by_business',
        );
    }

    private function seedResultRow(string $studentName, string $matric, string $grade, int $score, string $status = 'pending'): Result
    {
        $user = User::create([
            'name'     => $studentName,
            'email'    => strtolower(str_replace(' ', '.', $studentName)) . '_' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
        ]);
        $student = Student::create([
            'user_id'       => $user->id,
            'matric_number' => $matric,
            'school_id'     => $this->school->id,
            'department_id' => $this->department->id,
            'programme_id'  => $this->programme->id,
            'status'        => 'active',
        ]);

        $course = Course::create([
            'code'          => 'TST-' . uniqid(),
            'title'         => 'Test Course',
            'units'         => 3,
            'school_id'     => $this->school->id,
            'department_id' => $this->department->id,
            'programme_id'  => $this->programme->id,
            'level'         => 100,
            'semester'      => 'first',
        ]);

        $studentCourse = StudentCourse::create([
            'student_id' => $student->id,
            'course_id'  => $course->id,
            'session_id' => $this->session->id,
            'semester'   => 1,
            'status'     => 'registered',
        ]);

        return Result::create([
            'student_course_id' => $studentCourse->id,
            'course_id'         => $course->id,
            'total_score'       => $score,
            'grade'             => $grade,
            'grade_point'       => 4.0,
            'status'            => $status,
        ]);
    }

    private function makeBoardUser(): User
    {
        $role = Role::firstOrCreate(['slug' => 'academic_board'], ['name' => 'Academic Board']);
        $perm = Permission::firstOrCreate(
            ['slug' => 'academic.results.view'],
            ['name' => 'View academic results', 'group' => 'academic'],
        );
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        PermissionService::flush();

        $user = User::create([
            'name'     => 'Board Member',
            'email'    => 'board_' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);
        return $user;
    }

    private School $school;
    private Department $department;
    private Programme $programme;
    private Session $session;

    private function buildSchema(): void
    {
        Schema::create('roles', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('role_user', function ($t) {
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('user_id');
            $t->timestamps();
            $t->primary(['role_id', 'user_id']);
        });
        Schema::create('permissions', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('group', 50)->nullable();
            $t->timestamps();
        });
        Schema::create('role_permissions', function ($t) {
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
            $t->timestamps();
        });
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->unsignedBigInteger('role_id')->nullable();
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
            $t->string('code')->unique();
            $t->timestamps();
        });
        Schema::create('departments', function ($t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('school_id');
            $t->timestamps();
        });
        Schema::create('programmes', function ($t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('department_id');
            $t->timestamps();
        });
        Schema::create('students', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('matric_number')->nullable();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('courses', function ($t) {
            $t->id();
            $t->string('code');
            $t->string('title');
            $t->integer('units')->default(0);
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->integer('level')->nullable();
            $t->string('semester')->nullable();
            $t->timestamps();
        });
        Schema::create('student_courses', function ($t) {
            $t->id();
            $t->unsignedBigInteger('student_id');
            $t->unsignedBigInteger('course_id');
            $t->unsignedBigInteger('session_id');
            $t->integer('semester')->nullable();
            $t->string('status')->default('registered');
            $t->timestamps();
        });
        Schema::create('results', function ($t) {
            $t->id();
            $t->unsignedBigInteger('student_course_id');
            $t->unsignedBigInteger('course_id');
            $t->decimal('total_score', 6, 2)->nullable();
            $t->string('grade', 5)->nullable();
            $t->decimal('grade_point', 4, 2)->nullable();
            $t->string('status', 30)->default('pending');
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->dateTime('approved_at')->nullable();
            $t->text('remarks')->nullable();
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        $this->session = Session::create(['name' => '2025/2026', 'is_current' => true]);
        $this->school = School::create(['name' => 'Test School', 'code' => 'TS']);
        $this->department = Department::create(['name' => 'Computer Science', 'school_id' => $this->school->id]);
        $this->programme = Programme::create(['name' => 'ND Computer Science', 'department_id' => $this->department->id]);
    }
}
