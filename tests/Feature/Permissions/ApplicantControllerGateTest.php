<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-applicant regression — every Applicant\*.php controller (5
 * controllers, 26 of 33 public methods gated) now calls
 * `requirePermission($slug)` at the top of the auth-required entry
 * points. The 4 public endpoints (3 dropdown APIs + checkStatus) and
 * the `showValidatePayment` page stay public.
 *
 * Before slice 8i-applicant, the applicant routes relied SOLELY on the
 * `auth` middleware — there was no `role:applicant` check, so any
 * authenticated user (a bursar, a registrar, a student) could reach
 * applicant endpoints. The role middleware was implicitly delegating
 * isolation to the auth-only gate. The trait gate closes the gap: a
 * user who passes the auth middleware but lacks the applicant slug is
 * 403'd at the controller body.
 *
 * Tests use the same hand-rolled in-memory sqlite schema as the
 * other permission tests. We seed only the catalogue slugs the matrix
 * needs and use `actingAs()` to simulate auth.
 *
 * Note: we hit the routes through HTTP (not direct controller
 * invocation) because the route's `auth` middleware chain is what
 * admits the user. The controller trait gate then fires AFTER the
 * middleware, denying users who lack the slug. We assert on status
 * code — not response body — because the underlying controller body
 * may 500 in the test env (missing applicants / payments tables) and
 * the gate is what we care about.
 */
class ApplicantControllerGateTest extends TestCase
{
    /**
     * Catalogue — slug => group. The 5 applicant slugs created in
     * slice 8i-applicant (one per controller).
     */
    private const CATALOG = [
        'applicant.application.manage'  => 'applicant',
        'applicant.payments.manage'     => 'applicant',
        'applicant.payments.validate'   => 'applicant',
        'applicant.payments.receipt'    => 'applicant',
        'applicant.auto-login.issue'    => 'applicant',
    ];

    /**
     * Per-role pivot. We test:
     *  - applicant with all slugs → passes the gate
     *  - applicant with NO slugs → 403'd at the controller body
     *  - bursar (in the wrong role for the applicant routes) →
     *    blocked by the auth-or-trait gate (specifically by the
     *    trait gate, since the route uses `auth` not `role:applicant`)
     *  - guest → blocked by the auth middleware BEFORE the controller
     *    gate fires
     */
    private const ROLE_PERMISSIONS = [
        // Wildcard roles — pass every gate.
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        // Applicant with NO slugs — the regression we care about. The
        // auth middleware admits them; the controller gate 403s them.
        'applicant' => [],

        // Bursar — has bursar.* slugs but is NOT supposed to access
        // applicant endpoints. The auth middleware admits them (any
        // authenticated user reaches the route); the trait gate 403s
        // them because they have no applicant.* slugs.
        'bursar' => [
            'bursar.dashboard.view',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
        PermissionService::flush();
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::enableForeignKeyConstraints();
        PermissionService::flush();
        parent::tearDown();
    }

    /**
     * Guest is blocked at the auth middleware — never reaches the
     * controller gate. The `auth` middleware in a browser context
     * redirects guests to the login page (302); in a non-web request
     * context (e.g. API consumer) it 403s. Both are correct — the
     * test is asserting that the guest is denied, not that they get
     * a specific status code. The auth middleware is the layer that
     * keeps guests out; the controller gate never fires for them.
     */
    public function test_guest_is_blocked_at_auth_middleware(): void
    {
        $resp = $this->get('/applicant/dashboard');
        $status = $resp->getStatusCode();
        $this->assertContains($status, [302, 403],
            "guest should be blocked at the auth middleware (302 redirect or 403), got {$status}");
        $this->assertNotSame(200, $status,
            'guest must never reach a 200 response on an applicant route');
    }

    /**
     * Sanity test — bursar is blocked at the controller gate. The
     * auth middleware admits them (bursar is authenticated), but the
     * trait gate 403s them because they lack applicant.* slugs.
     */
    public function test_bursar_is_403_at_controller_gate(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/applicant/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar (authenticated but lacks applicant.* slugs) should be 403 at the controller gate');
    }

    /**
     * Applicant with the slug passes the controller gate. The
     * underlying controller body may still 500 (missing tables),
     * but the gate is what we care about.
     */
    public function test_applicant_with_slug_passes_dashboard_controller_gate(): void
    {
        $applicant = $this->makeUser('applicant');
        $applicantRole = Role::where('slug', 'applicant')->first();
        $applicantRole->permissions()->sync(
            Permission::whereIn('slug', ['applicant.application.manage'])->pluck('id')->all()
        );
        PermissionService::flush();

        $resp = $this->actingAs($applicant)->get('/applicant/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'applicant with applicant.application.manage should pass the controller gate');
    }

    /**
     * Applicant without the slug is 403'd at the controller body.
     * This is the regression slice 8i-applicant closes — before this
     * slice, the auth middleware admitted them and the controller
     * body executed freely.
     */
    public function test_applicant_without_slug_is_403_at_controller(): void
    {
        $applicant = $this->makeUser('applicant');
        // NO slug grant — the role has the pivot but it's empty.

        $resp = $this->actingAs($applicant)->get('/applicant/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'applicant without applicant.application.manage should be 403 at the controller gate');
    }

    /**
     * Applicant with applicant.payments.manage passes the payments
     * gateway controller gate. The controller body may 500 (missing
     * tables), but the gate is what we care about.
     */
    public function test_applicant_with_payments_slug_passes_payment_gateway_gate(): void
    {
        $applicant = $this->makeUser('applicant');
        $applicantRole = Role::where('slug', 'applicant')->first();
        $applicantRole->permissions()->sync(
            Permission::whereIn('slug', ['applicant.payments.manage'])->pluck('id')->all()
        );
        PermissionService::flush();

        $resp = $this->actingAs($applicant)->get('/applicant/payment/gateway');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'applicant with applicant.payments.manage should pass the payment gateway gate');
    }

    /**
     * Applicant with the wrong slug (only application.manage, not
     * payments.manage) is 403'd at the payment gateway controller.
     */
    public function test_applicant_without_payments_slug_is_403_at_payment_gateway(): void
    {
        $applicant = $this->makeUser('applicant');
        $applicantRole = Role::where('slug', 'applicant')->first();
        $applicantRole->permissions()->sync(
            Permission::whereIn('slug', ['applicant.application.manage'])->pluck('id')->all()
        );
        PermissionService::flush();

        $resp = $this->actingAs($applicant)->get('/applicant/payment/gateway');
        $this->assertSame(403, $resp->getStatusCode(),
            'applicant with application.manage but NOT payments.manage should be 403 at payment gateway');
    }

    /**
     * Applicant with all slugs passes multiple gates. Confirms the
     * wiring is symmetric — every controller's first method returns
     * through the trait gate without 403.
     */
    public function test_applicant_with_all_slugs_passes_multiple_gates(): void
    {
        $applicant = $this->makeUser('applicant');
        $applicantRole = Role::where('slug', 'applicant')->first();
        $applicantRole->permissions()->sync(Permission::pluck('id')->all());
        PermissionService::flush();

        // Hit a couple of controllers via the auth middleware. The
        // controller gate must pass for all of them.
        $urls = [
            '/applicant/dashboard',
            '/applicant/payment',
            '/applicant/payment/gateway',
        ];
        foreach ($urls as $url) {
            $resp = $this->actingAs($applicant)->get($url);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "applicant with all slugs should pass the controller gate at {$url}");
        }
    }

    /**
     * Bursar CANNOT reach /applicant/auto-login. The route uses
     * `auth` (not `role:applicant`), so the trait gate is the
     * slug-level check that protects this endpoint.
     */
    public function test_bursar_cannot_reach_auto_login_endpoint(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/applicant/auto-login');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at /applicant/auto-login (no applicant.auto-login.issue slug)');
    }

    /**
     * Bursar CANNOT reach /applicant/payments/{id}/receipt. The
     * trait gate protects this endpoint with applicant.payments.receipt.
     */
    public function test_bursar_cannot_reach_payment_receipt(): void
    {
        $bursar = $this->makeUser('bursar');

        // The {payment} segment is polymorphic — use an arbitrary id
        // that will miss everything. The trait gate fires before the
        // model binding, so the missing id doesn't matter.
        $resp = $this->actingAs($bursar)->get('/applicant/payments/1/receipt');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at /applicant/payments/{id}/receipt');
    }

    /**
     * Applicant with a forgotten slug is 403'd at the auto-login
     * endpoint.
     */
    public function test_applicant_without_auto_login_slug_is_403(): void
    {
        $applicant = $this->makeUser('applicant');
        // Grant everything except auto-login.issue.
        $applicantRole = Role::where('slug', 'applicant')->first();
        $autoLoginPerm = Permission::where('slug', 'applicant.auto-login.issue')->first();
        $applicantRole->permissions()->sync(
            Permission::where('id', '!=', $autoLoginPerm->id)->pluck('id')->all()
        );
        PermissionService::flush();

        $resp = $this->actingAs($applicant)->get('/applicant/auto-login');
        $this->assertSame(403, $resp->getStatusCode(),
            'applicant without applicant.auto-login.issue should be 403 at auto-login');
    }

    // ----------------------------------------------------------------
    // Schema + fixtures
    // ----------------------------------------------------------------

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

        Schema::create('role_user', function ($t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->primary(['role_id', 'user_id']);
            $t->timestamps();
        });

        Schema::create('permissions', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('group', 50)->nullable();
            $t->string('description')->nullable();
            $t->timestamps();
        });

        Schema::create('role_permissions', function ($t) {
            $t->id();
            $t->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $t->unique(['role_id', 'permission_id']);
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        foreach (self::CATALOG as $slug => $group) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'group' => $group],
            );
        }

        foreach (self::ROLE_PERMISSIONS as $roleSlug => $slugs) {
            $role = Role::firstOrCreate(
                ['slug' => $roleSlug],
                ['name' => ucfirst(str_replace('_', ' ', $roleSlug))],
            );

            if ($slugs === ['*']) {
                $role->permissions()->sync(Permission::pluck('id')->all());
                continue;
            }

            $role->permissions()->sync(
                Permission::whereIn('slug', $slugs)->pluck('id')->all(),
            );
        }
    }

    private function makeUser(string $roleSlug): User
    {
        $role = Role::firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst(str_replace('_', ' ', $roleSlug))],
        );
        return User::create([
            'name'      => ucfirst(str_replace('_', ' ', $roleSlug)) . ' User',
            'email'     => $roleSlug . '_' . uniqid('', true) . '@test.local',
            'password'  => bcrypt('password'),
            'role_id'   => $role->id,
            'is_active' => true,
        ]);
    }
}
