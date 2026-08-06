<?php

namespace Tests\Feature\Admin;

use App\Models\PaymentType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Reproduce the user's exact submission:
 *   - Name:    Compulsory Fee
 *   - Code:    Comp_Fee (then COMP_FEE_2026)
 *   - Amount:  100000 (then 1000000)
 *   - Channel: external
 *   - Purpose: compulsory
 *   - Audience: applicant
 *   - Priority: 1
 *   - Active: unchecked
 *   - Requires Payment: unchecked
 *
 * The user reported this still bounced back with a generic
 * "Please fix the errors below" banner. This test asserts the row
 * is created and verifies the storage and normalization behaviour.
 */
class PaymentTypeCompulsoryFeeTest extends TestCase
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
     * User submission #1: name="Compulsory Fee", code="Comp_Fee",
     * amount=1000000, channel=external, purpose="Compulsory Fee",
     * audience=applicant, priority=1, both checkboxes UNCHECKED.
     * Expected: row created, code normalised to "COMP_FEE",
     * purpose normalised to "compulsory_fee", audience stored as
     * "applicant", is_active=0, requires_payment=0.
     */
    public function test_user_payload_creates_row_with_normalisation(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)
            ->post('/admin/payment-types', [
                'name'             => 'Compulsory Fee',
                'code'             => 'Comp_Fee',
                'description'      => null,
                'amount'           => 1000000,
                'payment_channel'  => 'external',
                'purpose'          => 'Compulsory Fee',
                'audience'         => 'applicant',
                'priority'         => 1,
                'is_active'        => 0,
                'requires_payment' => 0,
            ]);

        // Should NOT be a redirect-with-errors (302 with errors bag).
        // If validation fails we still get 302, so we check the bag
        // explicitly.
        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $response->assertRedirect(route('admin.payment-types.index'));

        $row = PaymentType::where('code', 'COMP_FEE')->first();
        $this->assertNotNull($row, 'Row with normalised code COMP_FEE should exist');
        $this->assertEquals('Compulsory Fee', $row->name);
        $this->assertEquals('1000000.00', (string) $row->amount);
        $this->assertEquals('applicant', $row->audience);
        $this->assertEquals('external', $row->payment_channel);
        $this->assertEquals('compulsory_fee', $row->purpose);
        $this->assertEquals(0, (int) $row->is_active);
        $this->assertEquals(0, (int) $row->requires_payment);
    }

    /**
     * After the first row is created, the user re-submitted with
     * code=COMP_FEE_2026 to dodge the unique collision. Confirm
     * that variant also stores cleanly.
     */
    public function test_user_payload_with_unique_code_creates_second_row(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)
            ->post('/admin/payment-types', [
                'name'             => 'Compulsory Fee',
                'code'             => 'COMP_FEE_2026',
                'description'      => null,
                'amount'           => 100000,
                'payment_channel'  => 'external',
                'purpose'          => 'compulsory',
                'audience'         => 'applicant',
                'priority'         => 1,
                'is_active'        => 0,
                'requires_payment' => 0,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $row = PaymentType::where('code', 'COMP_FEE_2026')->first();
        $this->assertNotNull($row);
        $this->assertEquals('100000.00', (string) $row->amount);
        $this->assertEquals('applicant', $row->audience);
        $this->assertEquals('compulsory', $row->purpose);
    }

    /**
     * If the user re-submits the same code twice, the second
     * attempt MUST bounce back with a clear, per-field "code has
     * already been taken" error and MUST NOT create a duplicate
     * row. This is the most-likely cause of the user's "bounce
     * back" complaint — they had created the row on a previous
     * attempt and were re-submitting without realising.
     */
    public function test_repeat_submission_with_same_code_bounces_with_field_error(): void
    {
        $admin = $this->makeUser('admin');

        $payload = [
            'name'             => 'Compulsory Fee',
            'code'             => 'COMP_FEE_2026',
            'description'      => null,
            'amount'           => 100000,
            'payment_channel'  => 'external',
            'purpose'          => 'compulsory',
            'audience'         => 'applicant',
            'priority'         => 1,
            'is_active'        => 0,
            'requires_payment' => 0,
        ];

        $first = $this->actingAs($admin)->post('/admin/payment-types', $payload);
        $first->assertSessionHasNoErrors();
        $this->assertEquals(1, PaymentType::where('code', 'COMP_FEE_2026')->count());

        $second = $this->actingAs($admin)->post('/admin/payment-types', $payload);
        // Second attempt MUST bounce with a per-field error.
        $second->assertSessionHasErrors('code');
        // And the row count must still be exactly 1 — no duplicate.
        $this->assertEquals(1, PaymentType::where('code', 'COMP_FEE_2026')->count());
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
    }
}
