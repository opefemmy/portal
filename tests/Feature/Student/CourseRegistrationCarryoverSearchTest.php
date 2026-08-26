<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Programme;
use App\Models\Result;
use App\Models\Role;
use App\Models\School;
use App\Models\Semester;
use App\Models\Session as AcademicSession;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the JSON contract of `Student\CourseRegistrationController::searchCarryOvers`.
 *
 * The frontend (resources/views/student/courses-register.blade.php)
 * auto-fires `fetch('/student/courses/carryover-search')` on page
 * load and calls `r.json()` on the response. The view's renderResults()
 * branch handles the empty case with a "No past failed courses found"
 * message — but ONLY when the response is valid JSON with shape
 * `{carry_overs: []}`. Pre-fix, the controller was missing the
 * `use App\Models\Result;` import, so the unqualified `Result::query()`
 * resolved to a non-existent class and Laravel returned an HTML 500
 * page. The browser's JSON parse then surfaced as
 * "Search failed: Unexpected token '<', …" in the UI.
 *
 * These tests pin both the empty-results shape (the user's reported
 * "no courses found" rendering) and the populated shape.
 */
class CourseRegistrationCarryoverSearchTest extends TestCase
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
        Schema::dropIfExists('students');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_search_returns_empty_array_when_no_failures(): void
    {
        $student = $this->makeStudentWithFailingHistory(failureCount: 0);

        $response = $this->invoke($student->user, '');

        $response->assertOk();
        $response->assertJson(['carry_overs' => []]);
    }

    public function test_search_returns_failed_courses_with_expected_shape(): void
    {
        $student = $this->makeStudentWithFailingHistory(failureCount: 2);

        $response = $this->invoke($student->user, '');

        $response->assertOk();
        $body = $response->json();
        $this->assertArrayHasKey('carry_overs', $body);
        $this->assertCount(2, $body['carry_overs']);

        // Pin the field shape the frontend renders into a table row.
        $first = $body['carry_overs'][0];
        $this->assertArrayHasKey('id',         $first);
        $this->assertArrayHasKey('code',       $first);
        $this->assertArrayHasKey('title',      $first);
        $this->assertArrayHasKey('units',      $first);
        $this->assertArrayHasKey('semester',   $first);
        $this->assertArrayHasKey('department', $first);
        $this->assertArrayHasKey('failed_session', $first);
        $this->assertArrayHasKey('last_grade', $first);
        $this->assertArrayHasKey('last_total', $first);
    }

    public function test_search_filters_by_term_in_code_or_title(): void
    {
        $student = $this->makeStudentWithFailingHistory(failureCount: 2);

        // Two courses seeded with F grades; one is "MTH 101" and the
        // other is "PHY 101". A term search for "MTH" should narrow
        // to one row.
        $response = $this->invoke($student->user, 'MTH');

        $response->assertOk();
        $this->assertSame(1, count($response->json('carry_overs')));
        $this->assertStringContainsString('MTH', $response->json('carry_overs.0.code'));
    }

    public function test_response_is_json_not_html_when_no_results(): void
    {
        $student = $this->makeStudentWithFailingHistory(failureCount: 0);

        $response = $this->invoke($student->user, 'nonexistent');

        // The regression: a non-JSON response (e.g. an HTML 500 from
        // a missing import) caused the browser to throw
        // "Unexpected token '<'" inside `r.json()`. Pin that the
        // response is application/json with a carry_overs array.
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $body = $response->getContent();
        $this->assertStringStartsWith('{', ltrim($body));
        $this->assertStringNotContainsString('<', $body);
    }

    /* --- helpers --- */

    private function invoke(User $user, string $term): \Illuminate\Testing\TestResponse
    {
        $this->actingAs($user);
        $request = \Illuminate\Http\Request::create(
            '/student/courses/carryover-search',
            'GET',
            ['q' => $term],
        );
        app()->instance('request', $request);

        $controller = new \App\Http\Controllers\Student\CourseRegistrationController();
        // Wrap the JsonResponse in a TestResponse so we can use
        // assertOk / assertJson / assertJsonPath etc.
        return \Illuminate\Testing\TestResponse::fromBaseResponse(
            $controller->searchCarryOvers($request)
        );
    }

    private function makeStudentWithFailingHistory(int $failureCount): Student
    {
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $perm = Permission::firstOrCreate(
            ['slug' => 'student.courses.manage'],
            ['name' => 'Student Courses Manage', 'group' => 'student'],
        );
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        PermissionService::flush();

        $school = School::create(['name' => 'Test School', 'code' => 'TST']);
        $department = Department::create([
            'name'      => 'Mathematics',
            'code'      => 'MTH',
            'school_id' => $school->id,
        ]);
        $programme = Programme::create([
            'name'          => 'ND Mathematics',
            'code'          => 'NDMTH',
            'department_id' => $department->id,
        ]);

        $session = AcademicSession::create([
            'name'       => '2026/2027',
            'is_current' => true,
        ]);

        $user = User::create([
            'name'     => 'Carryover Student',
            'email'    => 'carry_' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $student = Student::create([
            'user_id'        => $user->id,
            'matric_number'  => 'CO/' . uniqid(),
            'school_id'      => $school->id,
            'department_id'  => $department->id,
            'programme_id'   => $programme->id,
            'session_id'     => $session->id,
            'level'          => 1,
            'status'         => 'active',
        ]);

        // Seed N failed results across two distinct courses so the
        // distinct-by-course de-duplication in the controller has
        // something to walk.
        $courses = [
            ['code' => 'MTH 101', 'title' => 'Algebra I',            'units' => 3],
            ['code' => 'PHY 101', 'title' => 'General Physics I',    'units' => 3],
        ];
        for ($i = 0; $i < $failureCount; $i++) {
            $spec = $courses[$i % count($courses)];
            $course = Course::create([
                'code'          => $spec['code'],
                'title'         => $spec['title'],
                'units'         => $spec['units'],
                'department_id' => $department->id,
                'programme_id'  => $programme->id,
                'semester'      => 'first',
            ]);
            $studentCourse = StudentCourse::create([
                'student_id' => $student->id,
                'course_id'  => $course->id,
                'session_id' => $session->id,
                'semester'   => 'first',
                'status'     => 'completed',
            ]);
            Result::create([
                'student_course_id' => $studentCourse->id,
                'course_id'         => $course->id,
                'grade'             => 'F',
                'total_score'       => 30,
                'status'            => 'approved_final',
            ]);
        }

        return $student;
    }

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
            $t->id();
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
        Schema::create('schools', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->timestamps();
        });
        Schema::create('departments', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->unsignedBigInteger('school_id');
            $t->timestamps();
        });
        Schema::create('programmes', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->unsignedBigInteger('department_id');
            $t->timestamps();
        });
        Schema::create('sessions', function ($t) {
            $t->id();
            $t->string('name');
            $t->boolean('is_current')->default(false);
            $t->timestamps();
        });
        Schema::create('semesters', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(false);
            $t->timestamps();
        });
        Schema::create('students', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('matric_number')->nullable();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->unsignedBigInteger('session_id')->nullable();
            $t->integer('level')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('courses', function ($t) {
            $t->id();
            $t->string('code');
            $t->string('title');
            $t->integer('units')->default(0);
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->string('semester')->nullable();
            $t->integer('level')->nullable();
            $t->timestamps();
        });
        Schema::create('student_courses', function ($t) {
            $t->id();
            $t->unsignedBigInteger('student_id');
            $t->unsignedBigInteger('course_id');
            $t->unsignedBigInteger('session_id');
            $t->string('semester')->nullable();
            $t->string('status')->default('registered');
            $t->timestamps();
        });
        Schema::create('results', function ($t) {
            $t->id();
            $t->unsignedBigInteger('student_course_id');
            $t->unsignedBigInteger('course_id');
            $t->decimal('total_score', 6, 2)->nullable();
            $t->string('grade', 4)->nullable();
            $t->decimal('grade_point', 4, 2)->nullable();
            $t->string('pass_status', 20)->nullable();
            $t->string('status')->default('pending');
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        // No seed data needed — tests build their own context.
    }
}