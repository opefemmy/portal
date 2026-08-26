<?php

namespace Tests\Feature\Student;

use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use App\Services\SchoolFeeCalculator;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins that three student-facing surfaces — exam clearance, course
 * registration form, result checker — redirect to /student/payments
 * with an error flash when the student has not paid tuition.
 *
 * User complaint: "if i dont pay my tution fee i should not have
 * access to exam clearance, course form and result checker".
 *
 * Pre-fix: the three surfaces either had no fee gate at all
 * (ResultController, printForm) or only gated on the print step
 * while the index rendered freely (ExamClearanceController). A
 * student could navigate to the URLs directly and view the pages
 * even when the dashboard said "School fee unpaid". The fix adds
 * a shared `SchoolFeeCalculator::hasPaidTuition()` helper that the
 * three controllers call at the top of their action methods.
 */
class TuitionGatedSurfacesTest extends TestCase
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
        Schema::dropIfExists('payments');
        Schema::dropIfExists('fees');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('students');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('role_user');
        parent::tearDown();
    }

    public function test_exam_clearance_redirects_when_tuition_unpaid(): void
    {
        $student = $this->makeStudent(tuitionPaid: false);

        $this->actingAs($student->user);
        $request = \Illuminate\Http\Request::create('/student/exam-clearance', 'GET');
        $request->setUserResolver(fn () => $student->user);

        $controller = new \App\Http\Controllers\Student\ExamClearanceController();
        $response = $controller->index($request);

        $this->assertSame(302, $response->getStatusCode(), 'Unpaid student must be redirected.');
        $this->assertStringContainsString(
            'payments',
            $response->headers->get('Location') ?? '',
            'Must redirect to the payments page.'
        );
        $this->assertTrue(
            $response->getSession()->has('error'),
            'An error flash must be set explaining why they were redirected.'
        );
    }

    public function test_exam_clearance_renders_when_tuition_paid(): void
    {
        $student = $this->makeStudent(tuitionPaid: true);

        $this->actingAs($student->user);
        $request = \Illuminate\Http\Request::create('/student/exam-clearance', 'GET');
        $request->setUserResolver(fn () => $student->user);

        $controller = new \App\Http\Controllers\Student\ExamClearanceController();
        $response = $controller->index($request);

        // The gate passed and the controller returned the exam-clearance
        // view (not a redirect).
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertSame('student.exam-clearance', $response->name());
    }

    public function test_course_form_redirects_when_tuition_unpaid(): void
    {
        $student = $this->makeStudent(tuitionPaid: false);

        $this->actingAs($student->user);
        $request = \Illuminate\Http\Request::create('/student/courses/print', 'GET');
        $request->setUserResolver(fn () => $student->user);

        $controller = new \App\Http\Controllers\Student\CourseRegistrationController();
        $response = $controller->printForm($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('payments', $response->headers->get('Location') ?? '');
        $this->assertTrue($response->getSession()->has('error'));
    }

    public function test_result_checker_redirects_when_tuition_unpaid(): void
    {
        $student = $this->makeStudent(tuitionPaid: false);

        $this->actingAs($student->user);
        $request = \Illuminate\Http\Request::create('/student/results', 'GET');
        $request->setUserResolver(fn () => $student->user);

        $controller = new \App\Http\Controllers\Student\ResultController();
        $response = $controller->index($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('payments', $response->headers->get('Location') ?? '');
        $this->assertTrue($response->getSession()->has('error'));
    }

    /**
     * The acceptance/compulsory fees must NOT count as tuition.
     * Regression for the legacy data shape where students paid the
     * admission-cycle fees but never paid tuition, yet the index
     * page rendered anyway because the gate logic grouped those
     * payments by fee_id and summed percent_paid regardless of
     * purpose.
     */
    public function test_only_school_fees_payment_counts_as_tuition(): void
    {
        $student = $this->makeStudent(tuitionPaid: false);

        // Add three completed payments that are NOT tuition:
        // application, acceptance, compulsory — all percent_paid=100.
        foreach (['application', 'acceptance', 'compulsory'] as $purpose) {
            Payment::create([
                'student_id'      => $student->id,
                'fee_id'          => null,
                'amount'          => 10000,
                'total_amount'    => 10000,
                'percent_paid'    => 100,
                'status'          => 'completed',
                'student_type'    => 'applicant',
                'payment_purpose' => $purpose,
                'fee_type'        => $purpose,
                'payer_id'        => $student->id,
                'payment_date'    => now(),
            ]);
        }

        $this->assertFalse(
            SchoolFeeCalculator::hasPaidTuition($student),
            'Acceptance/compulsory fees must NOT count as tuition — only school_fee/school_fees purpose rows do.'
        );
    }

    public function test_tuition_paid_with_legacy_purpose_name_works(): void
    {
        // The PaymentType constant uses 'school_fee' (no s) while
        // production ENUM uses 'school_fees' (with s). Both
        // spellings must count as tuition.
        foreach ([PaymentType::PURPOSE_SCHOOL_FEE, PaymentType::PURPOSE_SCHOOL_FEE_PRODUCTION] as $purpose) {
            $student = $this->makeStudent(tuitionPaid: false);
            Payment::create([
                'student_id'      => $student->id,
                'fee_id'          => null,
                'amount'          => 10000,
                'total_amount'    => 10000,
                'percent_paid'    => 60,
                'status'          => 'completed',
                'student_type'    => 'student',
                'payment_purpose' => $purpose,
                'fee_type'        => $purpose,
                'payer_id'        => $student->id,
                'payment_date'    => now(),
            ]);

            $this->assertTrue(
                SchoolFeeCalculator::hasPaidTuition($student->fresh()),
                "Payment with purpose='{$purpose}' must count as tuition."
            );
        }
    }

    public function test_zero_percent_tuition_payment_does_not_count(): void
    {
        // A tuition Payment row with percent_paid=0 is a placeholder,
        // not a receipt — it must NOT unlock the gated surfaces.
        $student = $this->makeStudent(tuitionPaid: false);
        Payment::create([
            'student_id'      => $student->id,
            'fee_id'          => null,
            'amount'          => 0,
            'total_amount'    => 0,
            'percent_paid'    => 0,
            'status'          => 'completed',
            'student_type'    => 'student',
            'payment_purpose' => PaymentType::PURPOSE_SCHOOL_FEE_PRODUCTION,
            'fee_type'        => 'school_fees',
            'payer_id'        => $student->id,
            'payment_date'    => now(),
        ]);

        $this->assertFalse(SchoolFeeCalculator::hasPaidTuition($student->fresh()));
    }

    /**
     * Regression for the dashboard "100% paid — exam clearance enabled"
     * badge that displayed even when the student had paid non-tuition fees
     * only. The badge partial must use SchoolFeeCalculator::hasPaidTuition()
     * — not maxPercentPaidAcrossRequiredFees() — when deciding whether
     * to render the success badge and the Exam Clearance button.
     *
     * We render the partial in isolation (rather than through
     * DashboardController::index) because the full dashboard view touches
     * relations the hand-rolled test schema doesn't have (welcome banner
     * reads user.passport + student.session + student.level_display).
     * The badge copy is the unit under test.
     */
    public function test_dashboard_badge_reflects_tuition_gate_not_fee_percent(): void
    {
        // Student has paid three non-tuition fees at 100% each — so
        // maxPercentPaidAcrossRequiredFees() = 100, but hasPaidTuition() = false.
        $student = $this->makeStudent(tuitionPaid: false);
        foreach (['application', 'acceptance', 'compulsory'] as $purpose) {
            Payment::create([
                'student_id'      => $student->id,
                'fee_id'          => null,
                'amount'          => 10000,
                'total_amount'    => 10000,
                'percent_paid'    => 100,
                'status'          => 'completed',
                'student_type'    => 'student',
                'payment_purpose' => $purpose,
                'fee_type'        => $purpose,
                'payer_id'        => $student->id,
                'payment_date'    => now(),
            ]);
        }

        $html = $this->renderBadge($student, paidPercent: 100, tuitionPaid: false);

        // Must NOT claim the success badge nor enable the Exam Clearance
        // button — those are unlocked only by tuition, not by the percent
        // of any other fee.
        $this->assertStringNotContainsString(
            'Both semesters + exam clearance enabled',
            $html,
            'Dashboard must not render the "exam clearance enabled" copy when tuition is unpaid.'
        );
        $this->assertStringContainsString(
            'School fee unpaid',
            $html,
            'Dashboard must show the unpaid badge copy when tuition is unpaid.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/>\s*Exam Clearance\s*</',
            $html,
            'Dashboard must not render the working Exam Clearance button when tuition is unpaid.'
        );
    }

    public function test_dashboard_badge_shows_paid_copy_when_tuition_paid(): void
    {
        // Inverse: when tuition is paid (only 60% so far), the badge must
        // say "60% paid" and the Exam Clearance button must render.
        $student = $this->makeStudent(tuitionPaid: true);

        $html = $this->renderBadge($student, paidPercent: 60, tuitionPaid: true);

        $this->assertStringContainsString(
            '60% paid',
            $html,
            'Dashboard must show the 60% badge copy when tuition is paid and paidPercent is 60.'
        );
        $this->assertMatchesRegularExpression(
            '/>\s*Exam Clearance\s*</',
            $html,
            'Exam Clearance button must render once tuition is paid (even at 60%).'
        );
    }

    public function test_dashboard_badge_shows_full_copy_when_tuition_paid_and_100_percent(): void
    {
        $student = $this->makeStudent(tuitionPaid: true);

        $html = $this->renderBadge($student, paidPercent: 100, tuitionPaid: true);

        $this->assertStringContainsString(
            '100% paid',
            $html,
            'Dashboard must show the 100% badge copy when tuition is fully paid.'
        );
        $this->assertStringContainsString(
            'Both semesters + exam clearance enabled',
            $html,
            'Dashboard must show the full-enablement copy when tuition is fully paid.'
        );
    }

    /**
     * Render the tuition badge partial in isolation. The partial reads
     * `route('student.payments')` and `route('student.exam-clearance')`,
     * which are wired in routes/web.php; if a future test breaks because
     * those routes are renamed, this helper will surface that immediately.
     */
    private function renderBadge(Student $student, int $paidPercent, bool $tuitionPaid): string
    {
        return view('partials.student.tuition-badge', [
            'student'     => $student,
            'paidPercent' => $paidPercent,
            'tuitionPaid' => $tuitionPaid,
        ])->render();
    }

    /* --- helpers --- */

    private function makeStudent(bool $tuitionPaid): Student
    {
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        // Grant the four permissions the gated controllers + dashboard check.
        foreach (['student.exam-clearance.view', 'student.courses.manage', 'student.results.view', 'student.dashboard.view'] as $slug) {
            $perm = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => ucwords(str_replace(['.', '-'], ' ', $slug)), 'group' => 'student'],
            );
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }
        PermissionService::flush();

        $user = User::create([
            'name'     => 'Tuition Test',
            'email'    => 'tuition_' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $student = Student::create([
            'user_id'        => $user->id,
            'matric_number'  => 'TU/' . uniqid(),
            'status'         => 'active',
        ]);

        if ($tuitionPaid) {
            Payment::create([
                'student_id'      => $student->id,
                'fee_id'          => null,
                'amount'          => 50000,
                'total_amount'    => 50000,
                'percent_paid'    => 100,
                'status'          => 'completed',
                'student_type'    => 'student',
                'payment_purpose' => PaymentType::PURPOSE_SCHOOL_FEE_PRODUCTION,
                'fee_type'        => 'school_fees',
                'payer_id'        => $student->id,
                'payment_date'    => now(),
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
        Schema::create('students', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('matric_number')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('payments', function ($t) {
            $t->id();
            $t->unsignedBigInteger('student_id')->nullable();
            $t->unsignedBigInteger('fee_id')->nullable();
            $t->decimal('amount', 12, 2);
            $t->decimal('total_amount', 12, 2)->default(0);
            $t->integer('percent_paid')->default(0);
            $t->string('status', 20)->default('pending');
            $t->string('student_type')->nullable();
            $t->string('payment_purpose')->nullable();
            $t->string('fee_type')->nullable();
            $t->unsignedBigInteger('payer_id')->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->timestamps();
        });
        Schema::create('sessions', function ($t) {
            $t->id();
            $t->string('name');
            $t->boolean('is_current')->default(false);
            $t->timestamps();
        });
        // ExamClearanceController::index queries the fees table for
        // requiredFeesFor(); an empty result is enough — we only care
        // that the gate passes when tuition is paid.
        Schema::create('fees', function ($t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->integer('level')->nullable();
            $t->unsignedBigInteger('session_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->decimal('price_indigene', 12, 2)->default(0);
            $t->decimal('price_non_indigene', 12, 2)->default(0);
            $t->decimal('portal_charge', 12, 2)->default(0);
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        // Exam clearance + result controller call Session::getCurrentSession().
        // Seed one so the gate-passed tests can render past the gate.
        \App\Models\Session::create(['name' => '2025/2026', 'is_current' => true]);
    }
}