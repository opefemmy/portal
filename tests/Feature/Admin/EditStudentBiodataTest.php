<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the contract that the Edit Student view/form persists BOTH
 * the academic fields (Student row) AND the User biodata (User row)
 * in a single submit.
 *
 * Before this change, the edit form only handled the Student row —
 * name/email/phone/dob/etc. on the User row were unreachable. The
 * registrar could change a student's level but not their name.
 */
class EditStudentBiodataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('nationalities');
        Schema::dropIfExists('local_governments');
        Schema::dropIfExists('states');
        Schema::dropIfExists('students');
        Schema::dropIfExists('users');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_edit_form_persists_user_biodata_along_with_student_fields(): void
    {
        $student = $this->makeStudent();
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->put("/admin/students/{$student->id}", [
            // Student academic fields
            'matric_number' => 'ND/2026/999',
            'status'        => 'suspended',
            'school_id'     => $student->school_id,
            'department_id' => $student->department_id,
            'programme_id'  => $student->programme_id,
            'session_id'    => $student->session_id,
            'level'         => 2,

            // User biodata (the new fields)
            'name'              => 'Renamed Student',
            'email'             => $student->user->email, // unchanged, but required
            'gender'            => 'female',
            'date_of_birth'     => '2002-04-15',
            'phone'             => '+2348012345678',
            'address'           => '12 Test Avenue, Lagos',
            'next_of_kin'       => 'Jane Doe',
            'next_of_kin_phone' => '+2348098765432',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.students.index'));

        // Academic row updated.
        $student->refresh();
        $this->assertEquals('ND/2026/999', $student->matric_number);
        $this->assertEquals('suspended', $student->status);
        $this->assertEquals(2, (int) $student->level);

        // User biodata row updated.
        $user = $student->user->fresh();
        $this->assertEquals('Renamed Student', $user->name);
        $this->assertEquals('female', $user->gender);
        $this->assertEquals('+2348012345678', $user->phone);
        $this->assertEquals('12 Test Avenue, Lagos', $user->address);
        $this->assertEquals('Jane Doe', $user->next_of_kin);
        $this->assertEquals('+2348098765432', $user->next_of_kin_phone);
        $this->assertEquals('2002-04-15', optional($user->date_of_birth)->format('Y-m-d'));
    }

    public function test_edit_form_rejects_missing_name(): void
    {
        $student = $this->makeStudent();
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->put("/admin/students/{$student->id}", [
            'matric_number' => $student->matric_number,
            'status'        => $student->status,
            'school_id'     => $student->school_id,
            'department_id' => $student->department_id,
            'programme_id'  => $student->programme_id,
            'session_id'    => $student->session_id,
            'level'         => $student->level,
            'name'          => '',                  // <- bad
            'email'         => $student->user->email,
        ]);

        $response->assertSessionHasErrors('name');
        // The Student row must NOT have been mutated.
        $student->refresh();
        $this->assertEquals($student->getOriginal('matric_number'), $student->matric_number);
    }

    public function test_edit_form_accepts_email_change_when_unique(): void
    {
        $student = $this->makeStudent();
        $admin = $this->makeUser('admin');
        $newEmail = 'rename_' . uniqid() . '@example.com';

        $response = $this->actingAs($admin)->put("/admin/students/{$student->id}", [
            'matric_number' => $student->matric_number,
            'status'        => $student->status,
            'school_id'     => $student->school_id,
            'department_id' => $student->department_id,
            'programme_id'  => $student->programme_id,
            'session_id'    => $student->session_id,
            'level'         => $student->level,
            'name'          => $student->user->name,
            'email'         => $newEmail,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals($newEmail, $student->user->fresh()->email);
    }

    public function test_edit_form_rejects_duplicate_email(): void
    {
        $studentA = $this->makeStudent();
        $studentB = $this->makeStudent();
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->put("/admin/students/{$studentA->id}", [
            'matric_number' => $studentA->matric_number,
            'status'        => $studentA->status,
            'school_id'     => $studentA->school_id,
            'department_id' => $studentA->department_id,
            'programme_id'  => $studentA->programme_id,
            'session_id'    => $studentA->session_id,
            'level'         => $studentA->level,
            'name'          => $studentA->user->name,
            'email'         => $studentB->user->email, // already taken
        ]);

        $response->assertSessionHasErrors('email');
        // Both users kept their original emails.
        $this->assertEquals($studentA->user->email, $studentA->user->fresh()->email);
        $this->assertEquals($studentB->user->email, $studentB->user->fresh()->email);
    }

    public function test_edit_view_renders_user_biodata_fields(): void
    {
        $student = $this->makeStudent();
        $student->user->update([
            'gender'        => 'female',
            'phone'         => '+2348000000000',
            'next_of_kin'   => 'Kin Person',
        ]);
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get("/admin/students/{$student->id}/edit");

        $response->assertOk();
        // Form sections render.
        $response->assertSee('Personal Information');
        $response->assertSee('Academic Information');
        $response->assertSee('Location');
        // Field values pre-populate from $user.
        $response->assertSee('value="' . $student->user->name . '"', false);
        $response->assertSee('value="+2348000000000"', false);
        $response->assertSee('value="Kin Person"', false);
        $response->assertSee('<option value="female" selected', false);
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

    private function makeStudent(): Student
    {
        $user = User::create([
            'name'     => 'Original Name',
            'email'    => 'student_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id'  => Role::where('slug', 'student')->value('id'),
            'is_active' => true,
        ]);

        return Student::create([
            'user_id'       => $user->id,
            'matric_number' => 'ND/2026/' . random_int(100, 999),
            'school_id'     => School::first()->id,
            'department_id' => Department::first()->id,
            'programme_id'  => Programme::first()->id,
            'session_id'    => AcademicSession::first()->id,
            'level'         => 1,
            'status'        => 'active',
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
            $t->foreignId('role_id')->nullable()->constrained();
            $t->boolean('is_active')->default(true);
            // Biodata columns the edit form writes to.
            $t->string('gender')->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('address', 500)->nullable();
            $t->string('next_of_kin')->nullable();
            $t->string('next_of_kin_phone', 30)->nullable();
            $t->timestamps();
        });
        Schema::create('students', function ($t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('matric_number')->unique();
            $t->foreignId('school_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable()->constrained();
            $t->foreignId('programme_id')->nullable()->constrained();
            $t->foreignId('session_id')->nullable()->constrained();
            $t->integer('level')->nullable();
            $t->string('status', 20)->default('active');
            $t->unsignedBigInteger('state_id')->nullable();
            $t->unsignedBigInteger('lga_id')->nullable();
            $t->unsignedBigInteger('nationality_id')->nullable();
            $t->timestamps();
        });
        // The edit controller eagerly loads these — they're required
        // for the Location section's <select> dropdowns.
        Schema::create('states', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('local_governments', function ($t) {
            $t->id();
            $t->string('name');
            $t->foreignId('state_id')->constrained();
            $t->timestamps();
        });
        Schema::create('nationalities', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Admin', 'slug' => 'admin']);
        Role::create(['name' => 'Student', 'slug' => 'student']);
        $school = School::create(['name' => 'Test School', 'code' => 'TST']);
        $dept = Department::create(['name' => 'Test Dept', 'code' => 'TSTD', 'school_id' => $school->id]);
        Programme::create(['name' => 'Test Prog', 'code' => 'TSTP', 'department_id' => $dept->id]);
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
    }
}