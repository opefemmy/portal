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
use App\Services\ApplicantPaymentService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins that ApplicantPaymentService::migrateApplicantToStudent()
 * mirrors `applicants.gender` onto `users.gender` so the student-
 * side hostel listing filter (`Student\HostelController::availableHostels`)
 * has the value it needs.
 *
 * Background: the user said "let applicant gender be part of
 * migrating file from application to student portal, just for the
 * sake of hostel selection purpose". Pre-fix, the migration only
 * updated `users.role_id` and `users.is_active` — it never carried
 * gender forward, so a student who only set gender on the application
 * form saw an empty `/student/hostel/apply` page on their first login
 * (the controller's unknown-gender fallback shows the listing but
 * with a warning banner; the proper fix is to populate the field).
 *
 * The migration only writes gender when the user row's current gender
 * is empty — never clobbers a value the user already updated.
 */
class ApplicantGenderMigrationTest extends TestCase
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
        Schema::dropIfExists('students');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('external_payments');
        parent::tearDown();
    }

    public function test_migration_mirrors_applicant_gender_onto_user(): void
    {
        $applicant = $this->makeAdmittedApplicant(gender: 'male');

        // Pre-migration state — user row was created without gender.
        $this->assertNull($applicant->user->fresh()->gender);
        $this->assertSame('male', $applicant->gender);

        app(ApplicantPaymentService::class)->migrateApplicantToStudent($applicant->fresh());

        $user = $applicant->user->fresh();
        $this->assertSame(
            'male',
            $user->gender,
            'migrateApplicantToStudent must copy applicants.gender onto users.gender so the student-side hostel filter has a value to read.'
        );
    }

    public function test_migration_mirrors_female_gender(): void
    {
        $applicant = $this->makeAdmittedApplicant(gender: 'female');

        app(ApplicantPaymentService::class)->migrateApplicantToStudent($applicant->fresh());

        $this->assertSame('female', $applicant->user->fresh()->gender);
    }

    public function test_migration_does_not_overwrite_existing_user_gender(): void
    {
        $applicant = $this->makeAdmittedApplicant(gender: 'male');

        // Pretend the student already updated their portal profile
        // gender to a different value before migration ran (uncommon
        // but possible — e.g. data fixup in admin). The migration
        // must NOT clobber that.
        $applicant->user->update(['gender' => 'female']);

        app(ApplicantPaymentService::class)->migrateApplicantToStudent($applicant->fresh());

        $this->assertSame(
            'female',
            $applicant->user->fresh()->gender,
            'When the user already has a gender set, migration must not overwrite it.'
        );
    }

    public function test_migration_leaves_user_gender_empty_when_applicant_gender_empty(): void
    {
        $applicant = $this->makeAdmittedApplicant(gender: null);

        app(ApplicantPaymentService::class)->migrateApplicantToStudent($applicant->fresh());

        $this->assertNull(
            $applicant->user->fresh()->gender,
            'When the applicant has no gender, the user row should stay null — the unknown-gender fallback in the hostel controller handles it.'
        );
    }

    /* --- helpers --- */

    private function makeAdmittedApplicant(?string $gender): Applicant
    {
        $studentRole = Role::where('slug', 'student')->first();

        $user = User::create([
            'name'     => 'Migration Gender Test',
            'email'    => 'mig_gender_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $studentRole?->id,
            // No gender on the user row — the migration is supposed to fill it.
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
            'entry_level'        => 1,
            'gender'             => $gender,
            'application_paid_at' => now(),
            'acceptance_paid_at'  => now(),
            'compulsory_paid_at'  => now(),
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
            $t->string('gender', 10)->nullable();
            $t->foreignId('role_id')->nullable()->constrained();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('applicants', function ($t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('application_number')->unique();
            $t->string('email')->nullable();
            $t->string('status', 20)->default('pending');
            $t->string('gender', 10)->nullable();
            $t->foreignId('school_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable()->constrained();
            $t->foreignId('programme_id')->nullable()->constrained();
            $t->foreignId('session_id')->nullable()->constrained();
            $t->string('entry_level')->nullable();
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
            $t->integer('priority')->default(0);
            $t->string('audience', 20)->default('both');
            $t->timestamps();
        });
        Schema::create('fees', function ($t) {
            $t->id();
            $t->string('name');
            $t->decimal('amount', 12, 2);
            $t->foreignId('session_id')->nullable()->constrained();
            $t->foreignId('school_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable()->constrained();
            $t->integer('level')->nullable();
            $t->timestamps();
        });
        Schema::create('payments', function ($t) {
            $t->id();
            $t->foreignId('student_id')->nullable();
            $t->foreignId('fee_id')->nullable();
            $t->decimal('amount', 12, 2);
            $t->decimal('total_amount', 12, 2)->default(0);
            $t->string('status', 20)->default('pending');
            $t->string('student_type')->nullable();
            $t->string('payment_purpose')->nullable();
            $t->string('fee_type')->nullable();
            $t->foreignId('payer_id')->nullable();
            $t->dateTime('payment_date')->nullable();
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
            $t->unsignedBigInteger('state_id')->nullable();
            $t->unsignedBigInteger('lga_id')->nullable();
            $t->unsignedBigInteger('nationality_id')->nullable();
            $t->boolean('from_application')->default(false);
            $t->foreignId('applicant_id')->nullable();
            $t->timestamps();
        });
        Schema::create('external_payments', function ($t) {
            $t->id();
            $t->string('transaction_id')->nullable()->unique();
            $t->decimal('amount', 12, 2)->default(0);
            $t->unsignedBigInteger('applicant_id')->nullable();
            $t->timestamps();
        });
        Schema::create('system_settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('payment_gateways', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        Role::firstOrCreate(['slug' => 'applicant'], ['name' => 'Applicant']);

        $school = School::create(['name' => 'Test School', 'code' => 'TST']);
        $dept = Department::create(['name' => 'Test Department', 'code' => 'TSTD', 'school_id' => $school->id]);
        Programme::create(['name' => 'Test Programme', 'code' => 'TSTP', 'department_id' => $dept->id]);
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        foreach ([
            ['name' => 'Application Fee',  'code' => 'APP',   'purpose' => 'application'],
            ['name' => 'Acceptance Fee',  'code' => 'ACC',   'purpose' => 'acceptance'],
            ['name' => 'Compulsory Fee',  'code' => 'COMP',  'purpose' => 'compulsory'],
        ] as $row) {
            PaymentType::create(array_merge($row, [
                'amount' => 10000,
                'is_active' => true,
                'requires_payment' => true,
                'priority' => 1,
                'audience' => 'both',
            ]));
        }
    }
}