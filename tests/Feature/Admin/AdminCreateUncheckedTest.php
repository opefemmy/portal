<?php

namespace Tests\Feature\Admin;

use App\Models\PaymentType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the regression: native HTML checkboxes without value="..."
 * submit "on" (not "1") when checked, and Laravel's `boolean`
 * rule only accepts [true, false, 0, 1, "0", "1"] — so the modal
 * bounced the admin back with "The is active field must be true or
 * false." even though the field was checked.
 *
 * Fix has two layers:
 *   1. Controller coerces checkbox values via $request->boolean()
 *      before validate(), so any truthy payload ("on", "1",
 *      "true", "yes") survives the validator.
 *   2. Modal inputs explicitly declare value="1" so the browser
 *      sends "1" (which the validator accepts directly).
 *
 * This test simulates the exact "on" payload a native checkbox
 * produces and asserts the row saves with the expected boolean
 * values.
 */
class AdminCreateUncheckedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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
            $t->string('audience')->default('both');
            $t->timestamps();
        });
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
        Role::create(['name' => 'Admin', 'slug' => 'admin']);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('payment_types');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_posting_with_checkboxes_omitted_does_not_fail(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'a@b.com', 'password' => bcrypt('x'),
            'role_id' => Role::where('slug', 'admin')->value('id'),
            'is_active' => true,
        ]);

        // Mirror what the browser sends when the admin unchecks both boxes.
        $response = $this->actingAs($admin)->post('/admin/payment-types', [
            'name' => 'School Fees',
            'code' => 'SCHOOL_FEE',
            'description' => 'Tuition and other school fees',
            'amount' => 50000,
            'payment_channel' => 'external',
            'purpose' => 'school_fees',
            'audience' => 'applicant',
            'priority' => 3,
            // is_active + requires_payment intentionally omitted
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payment_types', [
            'code' => 'SCHOOL_FEE',
            'is_active' => 0,
            'requires_payment' => 0,
            'audience' => 'applicant',
        ]);
    }

    /**
     * The actual reported failure: a native HTML checkbox that has
     * been CHECKED but carries no value="..." attribute sends
     * `is_active=on` to the server. Laravel's `boolean` rule only
     * accepts [true, false, 0, 1, "0", "1"], so the validator
     * rejects the row with "The is active field must be true or
     * false." — even though the admin clearly meant to check it.
     *
     * The controller must coerce the value via $request->boolean()
     * before validate() so any truthy checkbox payload survives.
     */
    public function test_posting_with_checkbox_payload_on_is_accepted(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'a@b.com', 'password' => bcrypt('x'),
            'role_id' => Role::where('slug', 'admin')->value('id'),
            'is_active' => true,
        ]);

        // Use a fresh code so we don't collide with the previous test
        // case's SCHOOL_FEE row.
        $response = $this->actingAs($admin)->post('/admin/payment-types', [
            'name' => 'Convocation Fee',
            'code' => 'CONVOCATION_ON',
            'amount' => 10000,
            'audience' => 'student',
            'payment_channel' => 'external',
            'purpose' => 'other',
            'priority' => 6,
            // Native HTML default for a checked checkbox with no
            // value= attribute is the literal string "on".
            'is_active' => 'on',
            'requires_payment' => 'on',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payment_types', [
            'code' => 'CONVOCATION_ON',
            'is_active' => 1,
            'requires_payment' => 1,
        ]);
    }

    /**
     * Same regression on update() — the Edit modal had checkboxes
     * without value="..." too. Editing any payment type with both
     * boxes checked would bounce with the same boolean error
     * before the controller fix.
     */
    public function test_update_with_checkbox_payload_on_is_accepted(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'a@b.com', 'password' => bcrypt('x'),
            'role_id' => Role::where('slug', 'admin')->value('id'),
            'is_active' => true,
        ]);

        $type = PaymentType::create([
            'name' => 'Existing',
            'code' => 'EDIT_ON',
            'amount' => 1000,
            'audience' => 'both',
            'is_active' => false,
            'requires_payment' => false,
            'payment_channel' => 'both',
        ]);

        $response = $this->actingAs($admin)->put('/admin/payment-types/' . $type->id, [
            'name' => 'Existing (edited)',
            'code' => 'EDIT_ON',
            'amount' => 1500,
            'audience' => 'both',
            'payment_channel' => 'both',
            'purpose' => 'other',
            'priority' => 1,
            'is_active' => 'on',
            'requires_payment' => 'on',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $type->refresh();
        $this->assertEquals('Existing (edited)', $type->name);
        $this->assertEquals(1, (int) $type->is_active);
        $this->assertEquals(1, (int) $type->requires_payment);
    }
}
