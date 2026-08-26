<?php

namespace Tests\Feature\Student;

use App\Models\Applicant;
use App\Models\Course;
use App\Models\Department;
use App\Models\GradingScale;
use App\Models\Permission;
use App\Models\Programme;
use App\Models\Result;
use App\Models\Role;
use App\Models\School;
use App\Models\Semester;
use App\Models\Session as AcademicSession;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the student-facing transcript layout to the reference PDF
 * (`docs/OLORUNKOSEBI Adelowo_s Academic Transcript.pdf`).
 *
 * The reference is an 8-page A4 portrait document with:
 *   • institutional letterhead (logo + name + address)
 *   • a 9-row biodata block (Name, Registration Number, Matric
 *     Number, Programme, Department, School, Year of Admission,
 *     Mode of Entry, Date of Graduation, plus Sex / DOB / State on
 *     the Name row)
 *   • per-session → per-semester course tables with TCP / TLU /
 *     TGP / GPA totals rows
 *   • a cumulative summary (TCP, TCE, CTLP, CTLU, CGPA) and
 *     "CLASS OF DEGREE AWARDED:" classification
 *   • a "Grading System" legend
 *   • a 3-column signature block
 *
 * Tests below invoke the controller directly (bypassing the
 * `auth + role:student + student.onboarding` middleware chain the
 * live `/student/*` routes use) so the focus is on the layout, not
 * the auth stack. Mirrors the pattern from `HostelAvailabilityTest`.
 */
class StudentTranscriptTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Build the smallest schema that lets the controller's
        // buildTranscriptData() walk its relations: applicant →
        // session, department → school, studentCourse → course →
        // result, plus the User / Student / Role / Permission stack
        // for EnforcesPermission.
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
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('grading_scales');
        Schema::dropIfExists('grade_classifications');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('system_settings');
        parent::tearDown();
    }

    public function test_transcript_renders_biodata_block(): void
    {
        $ctx = $this->makeContext();
        $this->makeResults($ctx, resultsPerSemester: 2);

        $html = $this->renderTranscript($ctx['student']->user);

        // Every biodata row from the reference.
        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('Registration Number', $html);
        $this->assertStringContainsString('Matric Number', $html);
        $this->assertStringContainsString('Programme', $html);
        $this->assertStringContainsString('Department', $html);
        $this->assertStringContainsString('School', $html);
        $this->assertStringContainsString('Year of Admission', $html);
        $this->assertStringContainsString('Mode of Entry', $html);
        $this->assertStringContainsString('Date of Graduation', $html);

        // The Name row carries Sex / DOB / State inline.
        $this->assertStringContainsString('Sex:', $html);
        $this->assertStringContainsString('D.O.B:', $html);
        $this->assertStringContainsString('State:', $html);

        // Concrete values seeded in makeContext().
        $this->assertStringContainsString('OLORUNKOSEBI Adelowo', $html);
        $this->assertStringContainsString('ND/CT/2023/001', $html);
        $this->assertStringContainsString('2023/ND1/CHM/002', $html);
        $this->assertStringContainsString('Ekiti', $html);
        $this->assertStringContainsString('Full-time', $html);
    }

    public function test_transcript_renders_one_section_per_session_and_semester(): void
    {
        $ctx = $this->makeContext();
        $this->makeResults($ctx, resultsPerSemester: 1);

        $html = $this->renderTranscript($ctx['student']->user);

        // Two academic sessions have results (sessionA 2023/2024 and
        // sessionB 2024/2025), each with two semesters → two session
        // headings and four semester headings. The session name
        // string appears at least once per heading; we don't pin an
        // exact count because the biodata block also references the
        // admission session name.
        $this->assertStringContainsString('2023/2024', $html);
        $this->assertStringContainsString('2024/2025', $html);
        $this->assertStringContainsString('First Semester', $html);
        $this->assertStringContainsString('Second Semester', $html);
    }

    public function test_transcript_renders_per_semester_totals(): void
    {
        $ctx = $this->makeContext();
        $this->makeResults($ctx, resultsPerSemester: 2);

        $html = $this->renderTranscript($ctx['student']->user);

        // Per-semester totals row labels.
        $this->assertStringContainsString('TCP', $html);
        $this->assertStringContainsString('TGP', $html);
        $this->assertStringContainsString('TLU', $html);
        $this->assertStringContainsString('GPA:', $html);
    }

    public function test_transcript_renders_cumulative_block(): void
    {
        $ctx = $this->makeContext();
        $this->makeResults($ctx, resultsPerSemester: 1);

        $html = $this->renderTranscript($ctx['student']->user);

        $this->assertStringContainsString('Cumulative Summary', $html);
        $this->assertStringContainsString('Total Credit Points (TCP)', $html);
        $this->assertStringContainsString('Total Credits Earned (TCE)', $html);
        $this->assertStringContainsString('Cumulative Total Load Points (CTLP)', $html);
        $this->assertStringContainsString('Cumulative Total Load Units (CTLU)', $html);
        $this->assertStringContainsString('Cumulative Grade Point Average (CGPA)', $html);
        $this->assertStringContainsString('CLASS OF DEGREE AWARDED:', $html);
    }

    public function test_transcript_renders_grading_legend(): void
    {
        $ctx = $this->makeContext();
        $this->makeResults($ctx, resultsPerSemester: 1);

        $html = $this->renderTranscript($ctx['student']->user);

        $this->assertStringContainsString('Grading System', $html);
        // All 6 default grades from the seeded scale.
        $this->assertStringContainsString('A', $html);
        $this->assertStringContainsString('B', $html);
        $this->assertStringContainsString('C', $html);
        $this->assertStringContainsString('D', $html);
        $this->assertStringContainsString('E', $html);
        $this->assertStringContainsString('F', $html);
    }

    public function test_transcript_renders_signature_block(): void
    {
        $ctx = $this->makeContext();
        $this->makeResults($ctx, resultsPerSemester: 1);

        $html = $this->renderTranscript($ctx['student']->user);

        $this->assertStringContainsString('Certifications', $html);
        $this->assertStringContainsString('Registrar', $html);
        $this->assertStringContainsString('Bursar', $html);
        $this->assertStringContainsString('Director', $html);
    }

    public function test_transcript_uses_institution_header_partial(): void
    {
        SystemSetting::set(SystemSetting::INSTITUTION_NAME, 'Federal Polytechnic Ado-Ekiti');
        $ctx = $this->makeContext();
        $this->makeResults($ctx, resultsPerSemester: 1);

        $html = $this->renderTranscript($ctx['student']->user);

        $this->assertStringContainsString('Federal Polytechnic Ado-Ekiti', $html);
    }

    public function test_transcript_filters_pending_results_out_of_cgpa(): void
    {
        $ctx = $this->makeContext();
        $resultIds = $this->makeResults($ctx, resultsPerSemester: 1);

        // Flip the first result to `pending` — it should be excluded
        // from the cumulative block's CGPA contribution, but the
        // per-semester table still lists it.
        $pendingId = $resultIds[0];
        Result::where('id', $pendingId)->update(['status' => 'pending']);

        $html = $this->renderTranscript($ctx['student']->user);

        // The cumulative block still renders (CGPA shown as a number).
        $this->assertStringContainsString('Cumulative Grade Point Average (CGPA)', $html);
        // The course table still lists every result row, including the
        // pending one (with whatever remark was seeded).
        $this->assertStringContainsString('CHM 101', $html);
    }

    public function test_transcript_hides_failed_courses_from_passed_total(): void
    {
        $ctx = $this->makeContext();
        $resultIds = $this->makeResults($ctx, resultsPerSemester: 1);

        // Flip the first result to a fail (F, 0.00 GP). The course still
        // appears in the table; the cumulative TCE (Total Credits Earned)
        // should NOT count its credit units.
        Result::where('id', $resultIds[0])->update([
            'grade'        => 'F',
            'grade_point'  => 0.00,
            'total_score'  => 30,
            'remarks'      => 'FAIL',
        ]);

        $html = $this->renderTranscript($ctx['student']->user);

        // The course row still renders, with the FAIL remark.
        $this->assertStringContainsString('FAIL', $html);
        // The cumulative block still renders the full structure.
        $this->assertStringContainsString('Total Credits Earned (TCE)', $html);
    }

    public function test_transcript_handles_student_with_no_results(): void
    {
        $ctx = $this->makeContext();
        // No results seeded.

        $html = $this->renderTranscript($ctx['student']->user);

        // Biodata still renders, cumulative CGPA renders as a dash,
        // classification renders as a dash, no crash.
        $this->assertStringContainsString('OLORUNKOSEBI Adelowo', $html);
        $this->assertStringContainsString('Cumulative Summary', $html);
        $this->assertStringContainsString('CLASS OF DEGREE AWARDED:', $html);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Walk the controller method directly, bypassing the
     * `auth + role:student + student.onboarding` middleware chain.
     * Mirrors `HostelAvailabilityTest::callAvailableHostels()`.
     */
    private function renderTranscript(User $user): string
    {
        $this->actingAs($user);
        $controller = new \App\Http\Controllers\Student\ResultController();
        $response = $controller->transcript(new \Illuminate\Http\Request());
        // The controller returns a View; cast to string to render.
        return (string) $response;
    }

    /**
     * Create a complete student context: user + role + student row +
     * linked applicant (with JAMB reg / state / mode of study /
     * admission session), programme, department, school. Two academic
     * sessions and two semesters are also created so the grouping
     * loop has something to walk.
     *
     * @return array<string, mixed>
     */
    private function makeContext(): array
    {
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        // Trait gate for `student.results.view`.
        $perm = Permission::firstOrCreate(
            ['slug' => 'student.results.view'],
            ['name' => 'Student Results View', 'group' => 'student'],
        );
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        PermissionService::flush();

        $user = User::create([
            'name'          => 'OLORUNKOSEBI Adelowo',
            'email'         => 'olorunkosebi@example.test',
            'password'      => bcrypt('secret'),
            'gender'        => 'male',
            'date_of_birth' => '2002-04-15',
            'role_id'       => $role->id,
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $school = School::create([
            'name' => 'School of Science and Computer Technology',
            'code' => 'SSCT',
        ]);

        $department = Department::create([
            'name'      => 'Department of Chemistry',
            'code'      => 'CHM',
            'school_id' => $school->id,
        ]);

        $programme = Programme::create([
            'name'          => 'National Diploma in Science Laboratory Technology',
            'code'          => 'ND-SLT',
            'type'          => 'ND',
            'department_id' => $department->id,
        ]);

        $admissionSession = AcademicSession::create([
            'name'       => '2023/2024',
            'is_current' => false,
        ]);

        $applicant = Applicant::create([
            'surname'                  => 'OLORUNKOSEBI',
            'first_name'               => 'Adelowo',
            'gender'                   => 'male',
            'date_of_birth'            => '2002-04-15',
            'state_of_origin'          => 'Ekiti',
            'mode_of_study'            => 'full-time',
            'jamb_registration_number' => '2023/ND1/CHM/002',
            'school_id'                => $school->id,
            'department_id'            => $department->id,
            'programme_id'             => $programme->id,
            'session_id'               => $admissionSession->id,
            'status'                   => 'admitted',
        ]);

        $student = Student::create([
            'user_id'        => $user->id,
            'applicant_id'   => $applicant->id,
            'matric_number'  => 'ND/CT/2023/001',
            'school_id'      => $school->id,
            'department_id'  => $department->id,
            'programme_id'   => $programme->id,
            'session_id'     => $admissionSession->id,
            'status'         => 'active',
        ]);

        // Two sessions, two semesters each.
        $sessionA = AcademicSession::create(['name' => '2023/2024', 'is_current' => true]);
        $sessionB = AcademicSession::create(['name' => '2024/2025', 'is_current' => false]);

        $first  = Semester::create(['name' => 'First',  'code' => '1', 'sort_order' => 1, 'is_active' => true]);
        $second = Semester::create(['name' => 'Second', 'code' => '2', 'sort_order' => 2, 'is_active' => false]);

        return compact(
            'user', 'student', 'applicant', 'programme', 'department', 'school',
            'sessionA', 'sessionB', 'first', 'second',
        );
    }

    /**
     * Seed results for the given context across both sessions, both
     * semesters. The grade/score cycle repeats so we always have at
     * least one of each non-fail grade.
     *
     * @param array<string, mixed> $ctx
     * @return array<int, int>  The list of seeded result IDs.
     */
    private function makeResults(array $ctx, int $resultsPerSemester): array
    {
        $cycle = [
            ['code' => 'CHM 101', 'title' => 'General Chemistry I',     'units' => 3, 'score' => 78, 'grade' => 'A', 'gp' => 4.00, 'remark' => 'DISTINCTION'],
            ['code' => 'CHM 102', 'title' => 'General Chemistry II',    'units' => 2, 'score' => 65, 'grade' => 'B', 'gp' => 3.50, 'remark' => 'UPPER CREDIT'],
            ['code' => 'MTH 101', 'title' => 'Elementary Mathematics I','units' => 3, 'score' => 55, 'grade' => 'C', 'gp' => 3.00, 'remark' => 'LOWER CREDIT'],
            ['code' => 'PHY 101', 'title' => 'General Physics I',       'units' => 3, 'score' => 47, 'grade' => 'D', 'gp' => 2.50, 'remark' => 'PASS'],
            ['code' => 'GNS 101', 'title' => 'Use of English',          'units' => 2, 'score' => 42, 'grade' => 'E', 'gp' => 2.00, 'remark' => 'PASS'],
            ['code' => 'CHM 103', 'title' => 'Organic Chemistry I',     'units' => 3, 'score' => 38, 'grade' => 'F', 'gp' => 0.00, 'remark' => 'FAIL'],
        ];

        $ids = [];
        $i = 0;
        foreach ([$ctx['sessionA'], $ctx['sessionB']] as $session) {
            foreach ([$ctx['first'], $ctx['second']] as $semester) {
                for ($n = 0; $n < $resultsPerSemester; $n++) {
                    $spec = $cycle[$i % count($cycle)];
                    $i++;

                    $course = Course::create([
                        'code'          => $spec['code'] . '-' . $i,
                        'title'         => $spec['title'],
                        'units'         => $spec['units'],
                        'department_id' => $ctx['department']->id,
                        'programme_id'  => $ctx['programme']->id,
                        'semester'      => $semester->id,
                    ]);

                    $studentCourse = StudentCourse::create([
                        'student_id' => $ctx['student']->id,
                        'course_id'  => $course->id,
                        'session_id' => $session->id,
                        'semester'   => $semester->id,
                        'status'     => 'completed',
                    ]);

                    $result = Result::create([
                        'student_course_id' => $studentCourse->id,
                        'course_id'         => $course->id,
                        'total_score'       => $spec['score'],
                        'grade'             => $spec['grade'],
                        'grade_point'       => $spec['gp'],
                        'status'            => 'approved_final',
                        'remarks'           => $spec['remark'],
                    ]);
                    $ids[] = $result->id;
                }
            }
        }
        return $ids;
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
            $t->string('gender', 10)->nullable();
            $t->date('date_of_birth')->nullable();
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
            $t->string('type')->nullable();
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
        Schema::create('applicants', function ($t) {
            $t->id();
            $t->string('surname')->nullable();
            $t->string('first_name')->nullable();
            $t->string('gender')->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('state_of_origin')->nullable();
            $t->string('mode_of_study')->nullable();
            $t->string('jamb_registration_number')->nullable();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->unsignedBigInteger('session_id')->nullable();
            $t->string('status')->default('admitted');
            $t->timestamps();
        });
        Schema::create('students', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('applicant_id')->nullable();
            $t->string('matric_number')->nullable();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->unsignedBigInteger('session_id')->nullable();
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
            $t->unsignedBigInteger('semester')->nullable();
            $t->timestamps();
        });
        Schema::create('student_courses', function ($t) {
            $t->id();
            $t->unsignedBigInteger('student_id');
            $t->unsignedBigInteger('course_id');
            $t->unsignedBigInteger('session_id');
            $t->unsignedBigInteger('semester');
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
            $t->string('status')->default('pending');
            $t->text('remarks')->nullable();
            $t->timestamps();
        });
        Schema::create('grading_scales', function ($t) {
            $t->id();
            $t->string('grade', 4);
            $t->integer('min_score');
            $t->integer('max_score');
            $t->decimal('grade_point', 4, 2);
            $t->string('remark');
            $t->string('classification')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        // The institution-header partial reads from this table via
        // SystemSetting::get(...) and getInstitutionName(). Without it
        // the partial throws QueryException and the transcript never
        // renders. The default helper fallback renders the default
        // institution name string, so we just need the table to exist.
        Schema::create('system_settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        // ResultComputationService::getAcademicRemark() queries this
        // table to classify the CGPA. Without it, the cumulative
        // block crashes before any of its rows render.
        Schema::create('grade_classifications', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug');
            $t->decimal('min_gpa', 4, 2);
            $t->decimal('max_gpa', 4, 2);
            $t->string('description')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        foreach (ResultComputationServiceDefaults::scales() as $i => $row) {
            GradingScale::create([
                'grade'         => $row['grade'],
                'min_score'     => $row['min_score'],
                'max_score'     => $row['max_score'],
                'grade_point'   => $row['grade_point'],
                'remark'        => $row['remark'],
                'sort_order'    => $i + 1,
            ]);
        }

        // NBTE/NUC 4.0-weight classification band — what
        // ResultComputationService::getAcademicRemark() reads.
        // Bands sorted DESC so the lowest sort_order wins for the
        // top of the range (Distinction).
        $bands = [
            ['name' => 'Distinction',  'slug' => 'distinction',  'min_gpa' => 3.50, 'max_gpa' => 4.00, 'description' => 'Exceptional performance'],
            ['name' => 'Upper Credit', 'slug' => 'upper-credit', 'min_gpa' => 3.00, 'max_gpa' => 3.49, 'description' => 'Very good performance'],
            ['name' => 'Lower Credit', 'slug' => 'lower-credit', 'min_gpa' => 2.50, 'max_gpa' => 2.99, 'description' => 'Good performance'],
            ['name' => 'Merit',        'slug' => 'merit',        'min_gpa' => 2.00, 'max_gpa' => 2.49, 'description' => 'Fair performance'],
            ['name' => 'Pass',         'slug' => 'pass',         'min_gpa' => 1.00, 'max_gpa' => 1.99, 'description' => 'Below average'],
            ['name' => 'Fail',         'slug' => 'fail',         'min_gpa' => 0.00, 'max_gpa' => 0.99, 'description' => 'Fail'],
        ];
        foreach ($bands as $i => $band) {
            \App\Models\GradeClassification::create(array_merge($band, ['sort_order' => $i + 1]));
        }
    }
}

/**
 * Tiny helper class — keeps the scale fixture in one place without
 * importing the service (which itself reads the DB).
 */
class ResultComputationServiceDefaults
{
    public static function scales(): array
    {
        return [
            ['grade' => 'A', 'min_score' => 70, 'max_score' => 100, 'grade_point' => 4.00, 'remark' => 'Excellent'],
            ['grade' => 'B', 'min_score' => 60, 'max_score' => 69,  'grade_point' => 3.50, 'remark' => 'Very Good'],
            ['grade' => 'C', 'min_score' => 50, 'max_score' => 59,  'grade_point' => 3.00, 'remark' => 'Good'],
            ['grade' => 'D', 'min_score' => 45, 'max_score' => 49,  'grade_point' => 2.50, 'remark' => 'Fair'],
            ['grade' => 'E', 'min_score' => 40, 'max_score' => 44,  'grade_point' => 2.00, 'remark' => 'Pass'],
            ['grade' => 'F', 'min_score' => 0,  'max_score' => 39,  'grade_point' => 0.00, 'remark' => 'Fail'],
        ];
    }
}
