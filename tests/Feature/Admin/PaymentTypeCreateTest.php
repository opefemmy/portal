<?php

namespace Tests\Feature\Admin;

use App\Models\PaymentType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression test for admin "Add Payment Type".
 *
 * User complaint: "i am unable to add a payment type" — the modal at
 * /admin/payment-types index posts to PaymentTypeController@store but
 * the row never appears.
 *
 * This test exercises the full flow:
 *   - Admin opens the index
 *   - Admin POSTs the same payload the modal would submit
 *   - The PaymentType row must be created
 *   - The admin must be redirected with success flash
 *
 * Uses a hand-rolled schema to stay independent of the (numerous)
 * pre-existing migration issues — same pattern as the applicant tests.
 */
class PaymentTypeCreateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    /**
     * Happy path: an admin POSTs a new payment type via the same
     * payload the modal submits. The row must be created and the
     * user redirected to the index with a success flash.
     */
    public function test_admin_can_add_a_payment_type(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)
            ->post('/admin/payment-types', [
                'name' => 'Convocation Fee',
                'code' => 'CONVOCATION',
                'description' => 'Annual convocation fee',
                'amount' => 10000,
                'payment_channel' => 'both',
                'purpose' => 'other',
                'audience' => 'student',
                'is_active' => 1,
                'requires_payment' => 1,
                'priority' => 5,
            ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.payment-types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_types', [
            'code' => 'CONVOCATION',
            'name' => 'Convocation Fee',
            'amount' => 10000,
            'audience' => 'student',
            'is_active' => 1,
            'requires_payment' => 1,
        ]);
    }

    /**
     * Required-field validation: missing `name` must NOT create a row.
     */
    public function test_missing_name_is_rejected(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)
            ->post('/admin/payment-types', [
                'code' => 'NONAME',
                'amount' => 1000,
                'audience' => 'both',
                'payment_channel' => 'both',
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('payment_types', ['code' => 'NONAME']);
    }

    /**
     * Missing audience must NOT crash the controller. After making
     * `audience` `nullable` with a default of 'both', the controller
     * accepts a missing audience and stores 'both' instead. This
     * test pins that behaviour so a future refactor doesn't silently
     * make it required again.
     */
    public function test_missing_audience_defaults_to_both(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)
            ->post('/admin/payment-types', [
                'name' => 'No Audience',
                'code' => 'NOAUD',
                'amount' => 1000,
                'payment_channel' => 'both',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_types', [
            'code' => 'NOAUD',
            'audience' => 'both',
        ]);
    }

    /**
     * Duplicate code is rejected (the `unique:payment_types,code`
     * rule on the controller).
     */
    public function test_duplicate_code_is_rejected(): void
    {
        $admin = $this->makeUser('admin');

        PaymentType::create([
            'name' => 'Existing',
            'code' => 'EXISTING',
            'amount' => 500,
            'audience' => 'both',
            'is_active' => true,
            'requires_payment' => true,
            'payment_channel' => 'both',
        ]);

        $response = $this->actingAs($admin)
            ->post('/admin/payment-types', [
                'name' => 'Duplicate',
                'code' => 'EXISTING',
                'amount' => 1000,
                'audience' => 'both',
                'payment_channel' => 'both',
            ]);

        $response->assertSessionHasErrors('code');
        // Existing row untouched, no duplicate created.
        $this->assertEquals(1, PaymentType::where('code', 'EXISTING')->count());
    }

    /**
     * Unauthenticated POSTs to the admin store must be rejected.
     * (Sanity check — same shape as the applicant payment test.)
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->post('/admin/payment-types', [
            'name' => 'Anon',
            'code' => 'ANON',
            'amount' => 1,
            'audience' => 'both',
            'payment_channel' => 'both',
        ]);

        $this->assertContains($response->getStatusCode(), [302, 401, 403]);
    }

    /**
     * The "i am unable to add a payment type" complaint was traced to
     * production deployments where the `audience` column hadn't been
     * added by migration 2026_08_04_000001 yet. The old controller
     * blindly passed `audience` into INSERT and the SQL error surfaced
     * as a 500 with no actionable message.
     *
     * This test simulates that scenario: a payment_types table that
     * pre-dates the audience migration. The store handler must:
     *   - drop the unsupported column from the insert payload so the
     *     row saves successfully
     *   - flash a migration hint so the admin knows to run migrate
     */
    public function test_store_succeeds_when_audience_column_missing_and_flashes_migration_hint(): void
    {
        // Drop the audience column to simulate the unrun-migration
        // scenario on production. Tear-down restores the test schema
        // regardless.
        Schema::table('payment_types', function ($t) {
            $t->dropColumn('audience');
        });

        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)
            ->post('/admin/payment-types', [
                'name' => 'Legacy Add',
                'code' => 'LEGACY_ADD',
                'description' => 'Before audience column was added',
                'amount' => 2500,
                'payment_channel' => 'both',
                'purpose' => 'other',
                'audience' => 'student',
                'is_active' => 1,
                'requires_payment' => 1,
                'priority' => 1,
            ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.payment-types.index'));
        $response->assertSessionHas('success');
        // The migration hint must be in the success flash so the admin
        // knows the row saved but they should run migrate for the
        // full feature set.
        $flash = (string) session('success');
        $this->assertStringContainsString('php artisan migrate', $flash);

        $this->assertDatabaseHas('payment_types', [
            'code' => 'LEGACY_ADD',
            'name' => 'Legacy Add',
            'amount' => 2500,
        ]);
    }

    /**
     * Same legacy-schema guard on update().
     */
    public function test_update_succeeds_when_audience_column_missing(): void
    {
        $admin = $this->makeUser('admin');

        $type = PaymentType::create([
            'name' => 'Pre-existing',
            'code' => 'PREEXIST',
            'amount' => 1000,
            'audience' => 'both',
            'is_active' => true,
            'requires_payment' => true,
            'payment_channel' => 'both',
        ]);

        Schema::table('payment_types', function ($t) {
            $t->dropColumn('audience');
        });

        $response = $this->actingAs($admin)
            ->put('/admin/payment-types/' . $type->id, [
                'name' => 'Pre-existing (edited)',
                'code' => 'PREEXIST',
                'description' => 'Edited',
                'amount' => 2000,
                'payment_channel' => 'both',
                'purpose' => 'other',
                'audience' => 'student',
                'is_active' => 1,
                'requires_payment' => 1,
                'priority' => 1,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertStringContainsString('php artisan migrate', (string) session('success'));

        $type->refresh();
        $this->assertEquals('Pre-existing (edited)', $type->name);
        $this->assertEquals(2000.0, (float) $type->amount);
    }

    /**
     * The user's complaint: "payment type fails to add". The most
     * likely silent-failure mode is that the row IS saved in the
     * database but the user can't see it on the redirected index
     * page (flash hidden, modal still open, datatable not refreshed,
     * etc). This test asserts the new row IS visible on the
     * post-redirect page so we can tell the difference between
     * "save failed" and "save worked but UI didn't show it".
     */
    public function test_new_payment_type_is_visible_on_index_after_creation(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)
            ->from('/admin/payment-types')
            ->post('/admin/payment-types', [
                'name' => 'Visible Convocation',
                'code' => 'VISIBLE_CONV',
                'description' => 'Should appear in the table',
                'amount' => 7500,
                'payment_channel' => 'both',
                'purpose' => 'other',
                'audience' => 'student',
                'is_active' => 1,
                'requires_payment' => 1,
                'priority' => 7,
            ]);

        $response->assertStatus(302);

        // Follow the redirect and confirm the new row is rendered.
        $response->assertRedirect(route('admin.payment-types.index'));
        $follow = $this->actingAs($admin)->get('/admin/payment-types');
        $follow->assertOk();
        $follow->assertSee('Visible Convocation', false);
        $follow->assertSee('VISIBLE_CONV', false);
        $follow->assertSee('7,500.00', false);
    }

    /**
     * The modal submits a hidden value for `audience` even when the
     * user doesn't change the select. Confirm the modal renders the
     * right default so the form never submits an empty audience
     * (which would fail the `required` validation).
     */
    public function test_create_modal_renders_audience_default_selected(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/admin/payment-types');

        $response->assertOk();
        // The create modal's audience select defaults to "both"
        // (selected attribute on the matching <option>).
        $response->assertSee('<option value="both" selected', false);
    }

    /**
     * The Create button is wired to the create modal via
     * data-bs-target="#createModal". Confirm the trigger and target
     * are both in the DOM and resolve correctly. This is the path
     * the user takes to open the modal.
     */
    public function test_create_button_is_wired_to_create_modal(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/admin/payment-types');

        $response->assertOk();
        $response->assertSee('data-bs-target="#createModal"', false);
        $response->assertSee('id="createModal"', false);
    }

    /* --- helpers --- */

    private function makeUser(string $roleSlug): User
    {
        return User::create([
            'name' => 'Test ' . ucfirst($roleSlug),
            'email' => $roleSlug . '_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', $roleSlug)->value('id'),
            'school_id' => null,
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
            $t->unsignedBigInteger('school_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        // Mirror the production schema: all the columns the admin
        // controller writes to, including `audience` (added by
        // migration 2026_08_04_000001).
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
        Role::create(['name' => 'Super Admin', 'slug' => 'super_admin']);
        Role::create(['name' => 'Applicant', 'slug' => 'applicant']);
        Role::create(['name' => 'Student', 'slug' => 'student']);
    }
}
