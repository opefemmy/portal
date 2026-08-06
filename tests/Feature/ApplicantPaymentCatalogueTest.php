<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\PaymentType;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\User;
use App\Services\ApplicantPaymentService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Verify that ApplicantPaymentService::getApplicantPaymentTypes() and
 * getStudentPaymentTypes() return only the catalogue rows visible to
 * each audience, in priority order, and that adding a brand-new
 * PaymentType via the service (no controller edit) is visible
 * immediately.
 *
 * These tests are the regression net for the "dynamic catalogue"
 * refactor — they ensure an admin creating a new fee at
 * /admin/payment-types does NOT need a code change for it to appear on
 * the applicant or student dashboard.
 */
class ApplicantPaymentCatalogueTest extends TestCase
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
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('academic_sessions');
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_catalogue_returns_only_applicant_audience_rows_in_priority_order(): void
    {
        PaymentType::create($this->typeAttrs('Applicant Late Fee', 'APP_LATE', 'other', 2, 1000));
        PaymentType::create($this->typeAttrs('Student Late Fee',  'STU_LATE', 'other', 2, 1000, 'student'));
        PaymentType::create($this->typeAttrs('Both Audience Fee', 'BOTH_FEE', 'other', 1, 2500, 'both'));

        $catalogue = app(ApplicantPaymentService::class)->getApplicantPaymentTypes();

        // 3 rows created, but only audience=applicant or audience=both
        // show up — the row targeted at students is filtered out.
        $this->assertCount(2, $catalogue);
        $codes = $catalogue->pluck('code')->all();
        $this->assertContains('BOTH_FEE', $codes);
        $this->assertContains('APP_LATE', $codes);
        $this->assertNotContains('STU_LATE', $codes, 'student-only row must not appear on applicant catalogue');

        // Ordered by priority (asc), then name as tie-breaker.
        $this->assertEquals(['BOTH_FEE', 'APP_LATE'], $codes);
    }

    public function test_student_catalogue_returns_only_student_audience_rows(): void
    {
        PaymentType::create($this->typeAttrs('Applicant Late Fee', 'APP_LATE', 'other', 2, 1000, 'applicant'));
        PaymentType::create($this->typeAttrs('Student Late Fee',  'STU_LATE', 'other', 2, 1000, 'student'));
        PaymentType::create($this->typeAttrs('Both Audience Fee', 'BOTH_FEE', 'other', 1, 2500, 'both'));

        $catalogue = app(ApplicantPaymentService::class)->getStudentPaymentTypes();

        $codes = $catalogue->pluck('code')->all();
        $this->assertCount(2, $catalogue);
        $this->assertContains('STU_LATE', $codes);
        $this->assertContains('BOTH_FEE', $codes);
        $this->assertNotContains('APP_LATE', $codes, 'applicant-only row must not appear on student catalogue');
    }

    public function test_inactive_rows_are_excluded_from_catalogue(): void
    {
        PaymentType::create($this->typeAttrs('Active Fee',   'ACTIVE',   'other', 1, 1000, 'applicant', true));
        PaymentType::create($this->typeAttrs('Inactive Fee', 'INACTIVE', 'other', 1, 1000, 'applicant', false));

        $catalogue = app(ApplicantPaymentService::class)->getApplicantPaymentTypes();
        $codes = $catalogue->pluck('code')->all();

        $this->assertContains('ACTIVE', $codes);
        $this->assertNotContains('INACTIVE', $codes,
            'Inactive catalogue rows must be filtered out so the dashboard only shows payable fees.');
    }

    public function test_statically_accessible_without_di(): void
    {
        PaymentType::create($this->typeAttrs('Static Helper Fee', 'STATIC_FEE', 'other', 1, 1500, 'applicant'));

        $catalogue = ApplicantPaymentService::getApplicantPaymentTypesStatic();

        $this->assertCount(1, $catalogue);
        $this->assertEquals('STATIC_FEE', $catalogue->first()->code);
    }

    /* --- helpers --- */

    private function typeAttrs(
        string $name,
        string $code,
        string $purpose,
        int $priority,
        float $amount,
        string $audience = 'applicant',
        bool $isActive = true,
    ): array {
        return [
            'name'             => $name,
            'code'             => $code,
            'purpose'          => $purpose,
            'audience'         => $audience,
            'amount'           => $amount,
            'priority'         => $priority,
            'is_active'        => $isActive,
            'requires_payment' => true,
            'payment_channel'  => 'external',
        ];
    }

    private function buildSchema(): void
    {
        Schema::create('roles', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->foreignId('role_id')->nullable()->constrained();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->boolean('is_active')->default(true);
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
            $t->timestamps();
        });
        Schema::create('departments', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('programmes', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('applicants', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('application_number');
            $t->string('email')->nullable();
            $t->string('status')->default('pending');
            $t->unsignedBigInteger('school_id');
            $t->unsignedBigInteger('department_id');
            $t->unsignedBigInteger('programme_id');
            $t->unsignedBigInteger('session_id')->nullable();
            $t->timestamp('application_paid_at')->nullable();
            $t->timestamp('acceptance_paid_at')->nullable();
            $t->timestamp('compulsory_paid_at')->nullable();
            $t->unsignedBigInteger('student_id')->nullable();
            $t->timestamp('migrated_to_student_at')->nullable();
            $t->timestamps();
        });
        Schema::create('payments', function ($t) {
            $t->id();
            $t->unsignedBigInteger('payer_id')->nullable();
            $t->string('reference')->nullable();
            $t->string('payment_purpose')->nullable();
            $t->string('status')->default('pending');
            $t->timestamps();
        });
        Schema::create('payment_types', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->text('description')->nullable();
            $t->decimal('amount', 12, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('requires_payment')->default(true);
            $t->string('payment_channel')->nullable();
            $t->integer('priority')->default(0);
            $t->string('purpose')->nullable();
            $t->enum('audience', ['applicant', 'student', 'both'])->default('both');
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Admin', 'slug' => 'admin']);
        Role::create(['name' => 'Applicant', 'slug' => 'applicant']);
        Role::create(['name' => 'Student', 'slug' => 'student']);

        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        School::create(['name' => 'Test School']);
        Department::create(['name' => 'Test Department']);
        Programme::create(['name' => 'Test Programme']);
    }
}
