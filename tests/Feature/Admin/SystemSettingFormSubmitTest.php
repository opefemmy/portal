<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins that Admin\SystemSettingController::updateSettings only persists
 * settings the form actually submitted.
 *
 * Regression: the controller's top-level $settingsKeys loop used to call
 * `SystemSetting::set($key, 'false')` for every key not present in the
 * request. That meant saving any unrelated section (e.g. toggling
 * course_registration_open) would clobber every other unchecked checkbox
 * — most painfully payment_open, which silently closed the student
 * payment portal after every admin save. Now the loop only writes when
 * `$request->has($key)` is true.
 */
class SystemSettingFormSubmitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_submitting_only_course_registration_open_does_not_reset_payment_open(): void
    {
        // Pre-existing values — admin opened both toggles earlier today.
        SystemSetting::set('payment_open', 'true');
        SystemSetting::set('course_registration_open', 'true');

        // Admin opens the form, leaves payment_open alone (checked), and
        // submits the form. Because the checkbox is checked, the request
        // carries payment_open=on — that's fine. But here we simulate the
        // pathological case: the admin only updates course_registration,
        // and the request does NOT carry payment_open at all (browser
        // omits unchecked boxes; we omit it entirely here to make the
        // intent explicit).
        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->put('/admin/settings', [
            'course_registration_open' => 'true',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        // payment_open must NOT have been reset.
        $this->assertEquals(
            'true',
            SystemSetting::get('payment_open'),
            "payment_open was reset by an unrelated form submit — updateSettings() is not honoring \$request->has(\$key)."
        );

        // course_registration_open was updated as requested.
        $this->assertEquals(
            'true',
            SystemSetting::get('course_registration_open')
        );
    }

    public function test_submitting_form_with_unset_payment_open_checkbox_keeps_existing_value(): void
    {
        // Browser-side equivalent: the admin loads the page, sees the
        // payment_open checkbox already checked, and submits the form to
        // update admission_form_open. The browser submits only the checked
        // fields. payment_open is NOT in the payload at all.
        SystemSetting::set('payment_open', 'true');
        SystemSetting::set('admission_form_open', 'true');

        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->put('/admin/settings', [
            'admission_form_open' => 'false',
        ]);

        $response->assertRedirect(route('admin.settings.index'));

        // payment_open unchanged.
        $this->assertEquals(
            'true',
            SystemSetting::get('payment_open'),
            'payment_open was reset to false by an unrelated submit — the controller must only write keys present in the request.'
        );
        // admission_form_open DID change.
        $this->assertEquals('false', SystemSetting::get('admission_form_open'));
    }

    public function test_submitting_form_with_payment_open_false_actually_closes_the_portal(): void
    {
        // Pins that the strict isOpen() (SystemSettingIsOpenTest) and the
        // $request->has() guard work together: an admin who explicitly
        // UN-checks payment_open and submits the form should see the
        // portal close. Previously the bool-cast kept the portal open
        // even after the write.
        SystemSetting::set('payment_open', 'true');

        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->put('/admin/settings', [
            'payment_open' => 'false',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $this->assertEquals('false', SystemSetting::get('payment_open'));
        $this->assertFalse(
            SystemSetting::isOpen('payment_open'),
            'isOpen() returned true for a row with value false — the strict truth-table check regressed.'
        );
    }

    /* --- helpers --- */

    private function makeAdmin(): User
    {
        return User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id'  => Role::where('slug', 'admin')->value('id'),
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
        Schema::create('system_settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Admin', 'slug' => 'admin']);
    }
}