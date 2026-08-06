<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression net for the cross-audience test-payment simulator.
 *
 * The /payment/test pages reachable from applicant, student, bursar,
 * and registrar all share the same controller (Payment\TestPaymentController)
 * which is gated on APP_ENV != production and writes an activity log
 * row on every call.
 *
 * Each area adds its own quick links — every admin / applicant / student
 * can fire a simulated payment without going through real Paystack.
 */
class TestPaymentSimulatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('students');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_applicant_test_payment_page_lists_payment_types(): void
    {
        $user = $this->makeUser('applicant');

        $response = $this->actingAs($user)
            ->get('/applicant/payment/test/applicant');

        $response->assertOk();
        $response->assertViewIs('payments.test');
        $response->assertSee('Test Payment Simulator', false);
        $response->assertSee('Compulsory Fee', false); // from seeder
        $response->assertSee('Application Form Fee', false);
    }

    public function test_applicant_can_process_a_test_payment(): void
    {
        $user = $this->makeUser('applicant');
        $type = PaymentType::where('code', 'COMP_FEE')->first();

        $this->assertNotNull($type);

        $response = $this->actingAs($user)->post('/applicant/payment/test/applicant/process', [
            'payment_type_id' => $type->id,
            'amount'          => $type->amount,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'payer_id'        => Applicant::where('user_id', $user->id)->value('id'),
            'payment_purpose' => 'compulsory',
            'gateway'         => 'test',
            'status'          => 'completed',
        ]);
    }

    public function test_student_test_payment_page_uses_student_catalogue(): void
    {
        $user = $this->makeUser('student');

        $response = $this->actingAs($user)
            ->get('/student/payment/test');

        $response->assertOk();
        $response->assertSee('Test Payment Simulator', false);
        // Student catalogue should NOT include applicant-only rows.
        $response->assertDontSee('Acceptance Fee', false);
    }

    public function test_bursar_sees_both_catalogue(): void
    {
        $user = $this->makeUser('bursar');

        $response = $this->actingAs($user)
            ->get('/bursar/payment/test');

        $response->assertOk();
        $response->assertSee('Test Payment Simulator', false);
        // Bursar should see both applicant rows (Acceptance Fee) and
        // student rows (Library Fee).
        $response->assertSee('Acceptance Fee', false);
        $response->assertSee('Library Fee', false);
    }

    public function test_registrar_sees_both_catalogue(): void
    {
        $user = $this->makeUser('registrar');

        $response = $this->actingAs($user)
            ->get('/registrar/payment/test');

        $response->assertOk();
        $response->assertSee('Test Payment Simulator', false);
        $response->assertSee('Compulsory Fee', false);
    }

    public function test_process_writes_activity_log_row(): void
    {
        $user = $this->makeUser('applicant');
        $type = PaymentType::where('code', 'APP_FORM')->first();

        $this->actingAs($user)->post('/applicant/payment/test/applicant/process', [
            'payment_type_id' => $type->id,
            'amount'          => $type->amount,
        ])->assertStatus(302);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action'  => 'test_payment.process',
        ]);
    }

    public function test_show_writes_activity_log_row(): void
    {
        $user = $this->makeUser('bursar');

        $this->actingAs($user)->get('/bursar/payment/test')->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action'  => 'test_payment.show',
        ]);
    }

    public function test_invalid_amount_is_rejected(): void
    {
        $user = $this->makeUser('applicant');
        $type = PaymentType::where('code', 'APP_FORM')->first();

        $this->actingAs($user)
            ->post('/applicant/payment/test/applicant/process', [
                'payment_type_id' => $type->id,
                'amount'          => 5, // below min=100
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_unknown_payment_type_id_is_rejected(): void
    {
        $user = $this->makeUser('applicant');

        $this->actingAs($user)
            ->post('/applicant/payment/test/applicant/process', [
                'payment_type_id' => 999999,
                'amount'          => 5000,
            ])
            ->assertSessionHasErrors('payment_type_id');
    }

    /* --- helpers --- */

    private function makeUser(string $roleSlug): User
    {
        return User::create([
            'name'     => 'Test ' . ucfirst($roleSlug),
            'email'    => $roleSlug . '_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id'  => Role::where('slug', $roleSlug)->value('id'),
            'is_active' => true,
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
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->foreignId('role_id')->nullable()->constrained();
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
            $t->string('status')->default('pending');
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->unsignedBigInteger('session_id')->nullable();
            $t->timestamp('application_paid_at')->nullable();
            $t->timestamp('acceptance_paid_at')->nullable();
            $t->timestamp('compulsory_paid_at')->nullable();
            $t->unsignedBigInteger('student_id')->nullable();
            $t->timestamp('migrated_to_student_at')->nullable();
            $t->timestamps();
        });
        Schema::create('students', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('matric_number');
            $t->string('status')->default('active');
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->unsignedBigInteger('session_id')->nullable();
            $t->timestamps();
        });
        Schema::create('payment_types', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->decimal('amount', 12, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('requires_payment')->default(true);
            $t->string('payment_channel')->nullable();
            $t->integer('priority')->default(0);
            $t->string('purpose')->nullable();
            $t->enum('audience', ['applicant', 'student', 'both'])->default('both');
            $t->timestamps();
        });
        Schema::create('payments', function ($t) {
            $t->id();
            $t->unsignedBigInteger('student_id')->nullable();
            $t->unsignedBigInteger('fee_id')->nullable();
            $t->decimal('amount', 12, 2);
            $t->decimal('total_amount', 12, 2)->nullable();
            $t->string('reference')->nullable();
            $t->string('payment_ref')->nullable();
            $t->string('transaction_id')->nullable();
            $t->string('gateway')->nullable();
            $t->string('payment_method')->nullable();
            $t->string('status')->default('pending');
            $t->boolean('is_verified')->default(false);
            $t->string('student_type')->nullable();
            $t->string('payment_purpose')->nullable();
            $t->string('fee_type')->nullable();
            $t->unsignedBigInteger('payer_id')->nullable();
            $t->string('payer_name')->nullable();
            $t->string('payer_email')->nullable();
            $t->string('payer_phone')->nullable();
            $t->dateTime('payment_date')->nullable();
            $t->text('payment_details')->nullable();
            $t->timestamps();
        });
        Schema::create('activity_logs', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action');
            $t->string('description')->nullable();
            $t->text('metadata')->nullable();
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Admin',     'slug' => 'admin']);
        Role::create(['name' => 'SuperAdmin','slug' => 'super_admin']);
        Role::create(['name' => 'Applicant', 'slug' => 'applicant']);
        Role::create(['name' => 'Student',   'slug' => 'student']);
        Role::create(['name' => 'Bursar',    'slug' => 'bursar']);
        Role::create(['name' => 'Registrar', 'slug' => 'registrar']);

        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        School::create(['name' => 'Test School']);
        Department::create(['name' => 'Test Department']);
        Programme::create(['name' => 'Test Programme']);

        PaymentType::create([
            'name' => 'Application Form Fee', 'code' => 'APP_FORM',
            'purpose' => 'application', 'audience' => 'applicant',
            'amount' => 5000, 'priority' => 1,
        ]);
        PaymentType::create([
            'name' => 'Acceptance Fee', 'code' => 'ACCEPT_FEE',
            'purpose' => 'acceptance', 'audience' => 'applicant',
            'amount' => 25000, 'priority' => 2,
        ]);
        PaymentType::create([
            'name' => 'Compulsory Fee', 'code' => 'COMP_FEE',
            'purpose' => 'compulsory', 'audience' => 'applicant',
            'amount' => 100000, 'priority' => 3,
        ]);
        PaymentType::create([
            'name' => 'Library Fee', 'code' => 'LIBRARY',
            'purpose' => 'library', 'audience' => 'student',
            'amount' => 1000, 'priority' => 4,
        ]);
    }
}
