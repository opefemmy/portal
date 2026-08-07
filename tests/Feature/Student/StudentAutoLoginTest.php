<?php

namespace Tests\Feature\Student;

use App\Http\Controllers\Student\AutoLoginController;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Pins the registrar-generated auto-login flow.
 *
 * Flow:
 *   1. Registrar calls AutoLoginController::generateForStudent($student) — gets a signed URL.
 *   2. URL flips must_change_password=true on the user.
 *   3. Student opens the signed URL.
 *   4. They land authenticated (no password prompt) on /student/password/change-required,
 *      with the form skipping the current-password field.
 *   5. They submit a new password and end up at the student dashboard with
 *      must_change_password=false.
 *
 * Defensive pins:
 *   - Tampered URLs (different signature) reject with 403.
 *   - Expired URLs reject with 403.
 *   - User-deletion post-generation rejects with redirect to login + error flash.
 *   - Non-student user (e.g. registrar with the same id) rejects with redirect.
 */
class StudentAutoLoginTest extends TestCase
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
        Schema::dropIfExists('users');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_generate_sets_must_change_password_and_returns_signed_url(): void
    {
        $student = $this->makeStudent();
        $userId = $student->user_id;

        $this->assertFalse(
            User::find($userId)->must_change_password,
            'Pre-condition: must_change_password is false before we generate the link.'
        );

        $url = AutoLoginController::generateForStudent($student);

        $this->assertNotEmpty($url);
        $this->assertTrue(
            User::find($userId)->must_change_password,
            'Generating the link must flip must_change_password=true so the onboarding middleware gates the user.'
        );

        // URL must be signed (Laravel appends signature query params).
        $this->assertMatchesRegularExpression(
            '#/student/auto-login/\d+#',
            $url,
            'URL must hit the auto-login consume endpoint.'
        );
        $this->assertStringContainsString('signature=', $url, 'Signed URL must carry a signature query param.');
        $this->assertStringContainsString('expires=', $url, 'Signed URL must carry an expires query param.');
    }

    public function test_consume_authenticates_and_redirects_to_password_change(): void
    {
        $student = $this->makeStudent();
        $url = AutoLoginController::generateForStudent($student);

        // Don't use actingAs — the whole point is that the user is NOT
        // authenticated yet, then becomes authenticated by consuming the link.
        $response = $this->get($url);

        $response->assertStatus(302);
        $response->assertRedirect(route('student.password.change.required'));
        $response->assertSessionHas('info');

        $this->assertTrue(Auth::check(), 'Consuming the auto-login URL must sign the user in.');
        $this->assertEquals(
            $student->user_id,
            Auth::id(),
            'The signed-in user must be the student the URL was minted for.'
        );
    }

    public function test_tampered_signature_rejects_with_403(): void
    {
        $student = $this->makeStudent();
        $url = AutoLoginController::generateForStudent($student);

        // Tamper with the signature — flip a few characters.
        $tampered = preg_replace(
            '#signature=[a-f0-9]+#',
            'signature=' . str_repeat('0', 64),
            $url
        );

        $this->assertNotEquals(
            $url,
            $tampered,
            'Sanity: the tampered URL should actually differ from the original.'
        );

        $response = $this->get($tampered);

        // Laravel's signed-middleware throws a 403 (or renders a 403 page).
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'A tampered signature must be rejected with 403; got ' . $response->getStatusCode()
        );

        $this->assertFalse(
            Auth::check(),
            'A tampered URL must NOT sign the user in.'
        );
    }

    public function test_expired_url_rejects_with_403(): void
    {
        $student = $this->makeStudent();

        // 1-hour link, then advance the clock past the expiry.
        $url = AutoLoginController::generateForStudent($student, hours: 1);
        $this->travel(2)->hours();

        $response = $this->get($url);

        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'An expired URL must be rejected with 403; got ' . $response->getStatusCode()
        );

        $this->travelBack();
    }

    public function test_consume_redirects_to_login_if_user_was_deleted(): void
    {
        $student = $this->makeStudent();
        $url = AutoLoginController::generateForStudent($student);

        // Delete the user AFTER generating the link. The student row
        // references users via FK, so we have to drop the student first.
        $student->delete();
        User::find($student->user_id)->delete();

        $response = $this->get($url);

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertFalse(Auth::check(), 'A consumed-but-deleted link must NOT authenticate anyone.');
    }

    public function test_forced_change_password_form_omits_current_password_field(): void
    {
        $student = $this->makeStudent();
        AutoLoginController::generateForStudent($student);

        $response = $this->actingAs($student->user)
            ->get(route('student.password.change.required'));

        $response->assertStatus(200);
        $this->assertStringNotContainsString(
            'name="current_password"',
            $response->getContent(),
            'Forced-change form must NOT render the current_password input — the auto-login flow has no current password.'
        );
        $this->assertStringContainsString(
            'Set Password &amp; Continue',
            $response->getContent(),
            'Forced-change form must show the "Set Password & Continue" button label.'
        );
    }

    public function test_setting_new_password_via_forced_change_lands_on_dashboard(): void
    {
        $student = $this->makeStudent();
        AutoLoginController::generateForStudent($student);

        $response = $this->actingAs($student->user)
            ->post(route('student.password.change'), [
                'new_password' => 'fresh-pass-99',
                'new_password_confirmation' => 'fresh-pass-99',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('success');

        $student->user->refresh();
        $this->assertFalse(
            $student->user->must_change_password,
            'After a forced password change, must_change_password must be false.'
        );
        $this->assertTrue(
            password_verify('fresh-pass-99', $student->user->password),
            'New password must be hashed and stored.'
        );
    }

    public function test_voluntary_change_still_requires_current_password(): void
    {
        // Sanity: we did not break the voluntary-change flow. A student who
        // signs in normally and reaches the change form without the
        // forced-change flag must still have to type their current password.
        $student = $this->makeStudent();
        $student->user->update(['must_change_password' => false]);

        $response = $this->actingAs($student->user)
            ->get(route('student.password.change.required'));

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'name="current_password"',
            $response->getContent(),
            'Voluntary-change form must still render the current_password input.'
        );

        // And submitting a new password WITHOUT the current password must fail.
        $fail = $this->actingAs($student->user)
            ->post(route('student.password.change'), [
                'new_password' => 'newpass',
                'new_password_confirmation' => 'newpass',
            ]);
        $fail->assertSessionHasErrors('current_password');
    }

    /* --- helpers --- */

    private function makeStudent(): Student
    {
        $user = User::create([
            'name'  => 'Auto Login Student',
            'email' => 'auto_login_' . uniqid() . '@example.com',
            'password' => bcrypt('whatever-old-password'),
            'role_id' => Role::where('slug', 'student')->value('id'),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $student = Student::create([
            'user_id'       => $user->id,
            'matric_number' => 'AUTO/' . random_int(1000, 9999) . '/2026',
            'school_id'     => $this->school->id,
            'department_id' => $this->dept->id,
            'programme_id'  => $this->prog->id,
            'session_id'    => $this->session->id,
            'level'         => 1,
            'status'        => 'active',
        ]);

        return $student;
    }

    private School $school;
    private Department $dept;
    private Programme $prog;
    private AcademicSession $session;

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
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->foreignId('role_id')->nullable()->constrained();
            $t->boolean('is_active')->default(true);
            $t->boolean('must_change_password')->default(false);
            $t->timestamp('password_changed_at')->nullable();
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
    }
}