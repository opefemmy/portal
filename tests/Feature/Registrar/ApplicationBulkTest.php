<?php

namespace Tests\Feature\Registrar;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression test for POST /registrar/applications/bulk.
 *
 * The bulk endpoint used to:
 *   - iterate IDs with Applicant::find() and Applicant::update(),
 *     one row at a time, no transaction
 *   - NOT call assertSameSchool(), unlike every other registrar
 *     route, so a registrar could silently update applicants from
 *     another school
 *
 * After the fix:
 *   - a single whereIn(id, ...)->update() is used, wrapped in a
 *     transaction
 *   - the query is pre-filtered by school_id when the auth user
 *     has a school_id; admin / super_admin (school_id=null) keep
 *     the legacy "all schools" behaviour
 *
 * These tests pin both halves of the contract.
 */
class ApplicationBulkTest extends TestCase
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
        Schema::dropIfExists('users');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    /**
     * Happy path: a registrar with a school_id bulk-sends two
     * applicants in their own school to screening. Both should
     * flip status and stamp reviewed_by/reviewed_at.
     */
    public function test_registrar_can_bulk_send_own_school_applicants_to_screening(): void
    {
        $registrar = $this->makeUser('registrar', $this->schoolA->id);
        $a1 = $this->makeApplicant($this->schoolA->id, 'pending');
        $a2 = $this->makeApplicant($this->schoolA->id, 'pending');

        $response = $this->actingAs($registrar)
            ->post('/registrar/applications/bulk', [
                'applications' => [$a1->id, $a2->id],
                'action' => 'screening',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $a1->refresh();
        $a2->refresh();
        $this->assertEquals('screening', $a1->status);
        $this->assertEquals('screening', $a2->status);
        $this->assertEquals($registrar->id, $a1->reviewed_by);
        $this->assertNotNull($a1->reviewed_at);
        $this->assertEquals($registrar->id, $a2->reviewed_by);
        $this->assertNotNull($a2->reviewed_at);
    }

    /**
     * The critical authorization fix: a registrar at school A must
     * NOT be able to update an applicant from school B even if the
     * applicant id is in the POST body.
     */
    public function test_registrar_cannot_bulk_update_cross_school_applicants(): void
    {
        $registrar = $this->makeUser('registrar', $this->schoolA->id);
        $own = $this->makeApplicant($this->schoolA->id, 'pending');
        $other = $this->makeApplicant($this->schoolB->id, 'pending');

        $response = $this->actingAs($registrar)
            ->post('/registrar/applications/bulk', [
                'applications' => [$own->id, $other->id],
                'action' => 'screening',
            ]);

        $response->assertStatus(302);

        $own->refresh();
        $other->refresh();

        $this->assertEquals('screening', $own->status, 'own-school applicant should update');
        $this->assertEquals('pending', $other->status, 'cross-school applicant must NOT update');
        $this->assertNull($other->reviewed_by);
    }

    /**
     * Admin (no school_id) keeps the legacy "all schools"
     * behaviour — they can bulk update applicants across schools.
     */
    public function test_admin_without_school_can_update_across_schools(): void
    {
        $admin = $this->makeUser('admin', null);
        $a = $this->makeApplicant($this->schoolA->id, 'pending');
        $b = $this->makeApplicant($this->schoolB->id, 'pending');

        $response = $this->actingAs($admin)
            ->post('/registrar/applications/bulk', [
                'applications' => [$a->id, $b->id],
                'action' => 'approved',
            ]);

        $response->assertStatus(302);

        $a->refresh();
        $b->refresh();
        $this->assertEquals('approved', $a->status);
        $this->assertEquals('approved', $b->status);
    }

    /**
     * Validation: invalid action rejected (no DB writes).
     */
    public function test_invalid_action_is_rejected(): void
    {
        $registrar = $this->makeUser('registrar', $this->schoolA->id);
        $a = $this->makeApplicant($this->schoolA->id, 'pending');

        $response = $this->actingAs($registrar)
            ->post('/registrar/applications/bulk', [
                'applications' => [$a->id],
                'action' => 'explode',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('action');

        $a->refresh();
        $this->assertEquals('pending', $a->status);
    }

    /**
     * The endpoint requires auth — anonymous POST is rejected.
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $a = $this->makeApplicant($this->schoolA->id, 'pending');

        $response = $this->post('/registrar/applications/bulk', [
            'applications' => [$a->id],
            'action' => 'screening',
        ]);

        // Anonymous is redirected to login (302) rather than 401
        // because the route sits behind the auth middleware.
        $this->assertContains($response->getStatusCode(), [302, 401]);
    }

    /* --- helpers --- */

    private School $schoolA;
    private School $schoolB;

    private function makeUser(string $roleSlug, ?int $schoolId): User
    {
        return User::create([
            'name' => 'Test ' . ucfirst($roleSlug),
            'email' => $roleSlug . '_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', $roleSlug)->value('id'),
            'school_id' => $schoolId,
            'is_active' => true,
        ]);
    }

    private function makeApplicant(int $schoolId, string $status): Applicant
    {
        $user = User::create([
            'name' => 'Applicant',
            'email' => 'applicant_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', 'applicant')->value('id'),
            'is_active' => true,
        ]);

        return Applicant::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'application_number' => Applicant::generateApplicationNumber(),
            'status' => $status,
            'school_id' => $schoolId,
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
            $t->foreignId('state_id')->nullable();
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
            $t->unsignedBigInteger('school_id')->nullable();
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
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->dateTime('reviewed_at')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->string('matric_number')->nullable();
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
            $t->string('payer_name')->nullable();
            $t->string('payer_email')->nullable();
            $t->string('payer_phone')->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->text('payment_details')->nullable();
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Applicant', 'slug' => 'applicant']);
        Role::create(['name' => 'Registrar', 'slug' => 'registrar']);
        Role::create(['name' => 'Admin', 'slug' => 'admin']);
        Role::create(['name' => 'Super Admin', 'slug' => 'super_admin']);

        $this->schoolA = School::create(['name' => 'School A', 'code' => 'SCHA']);
        $this->schoolB = School::create(['name' => 'School B', 'code' => 'SCHB']);

        $deptA = Department::create(['name' => 'Dept A', 'code' => 'DEPTA', 'school_id' => $this->schoolA->id]);
        $deptB = Department::create(['name' => 'Dept B', 'code' => 'DEPTB', 'school_id' => $this->schoolB->id]);
        Programme::create(['name' => 'Prog A', 'code' => 'PROGA', 'department_id' => $deptA->id]);
        Programme::create(['name' => 'Prog B', 'code' => 'PROGB', 'department_id' => $deptB->id]);
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
    }
}