<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-applicant-routes regression — every applicant route
 * inside the `auth` group (32 routes) now carries
 * `permission:<slug>` middleware matching the controller trait gate
 * from slice 8i-applicant. This is the second layer of defence:
 * the route middleware fires BEFORE the controller is resolved, so
 * any user who passes auth but lacks the slug is 403'd at route
 * resolution.
 *
 * Before slice 8i-applicant-routes, the applicant route group used
 * bare `auth` middleware (no `role:applicant`), so the slug check
 * lived only on the controller trait gate (slice 8i-applicant). This
 * slice mirrors the slug onto the route's middleware chain, the same
 * defence-in-depth pattern slice 8i-routes used for dashboard-config
 * and slice 8i-maintenance-routes just used for maintenance.
 *
 * Public routes (no auth required) are intentionally NOT gated:
 *   - GET /validate-payment (the showValidatePayment page)
 *   - GET /status, POST /status-check
 *   - GET /register, POST /register (guest middleware)
 *
 * The POST /validate-payment is gated (the body calls Auth::user()
 * and an unauthenticated caller would 500).
 *
 * Tests use the same hand-rolled in-memory sqlite schema as
 * ApplicantControllerGateTest. We hit routes through HTTP so the
 * permission: middleware fires at route resolution.
 */
class ApplicantRouteMiddlewareTest extends TestCase
{
    private const catalogue = [
        'applicant.application.manage'  => 'applicant',
        'applicant.payments.manage'     => 'applicant',
        'applicant.payments.validate'   => 'applicant',
        'applicant.payments.receipt'    => 'applicant',
        'applicant.auto-login.issue'    => 'applicant',
    ];

    private const ROLE_PERMISSIONS = [
        // Wildcard roles — pass every gate.
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        // Applicant with all slugs — the canonical applicant role.
        'applicant' => [
            'applicant.application.manage',
            'applicant.payments.manage',
            'applicant.payments.validate',
            'applicant.payments.receipt',
            'applicant.auto-login.issue',
        ],

        // Bursar — wrong role, no applicant slugs. The auth
        // middleware admits them; the permission: middleware
        // 403s them.
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

    // ----------------------------------------------------------------
    // Auth middleware
    // ----------------------------------------------------------------

    /**
     * Guest is blocked at the auth middleware for a gated route —
     * never reaches the permission: middleware or the controller
     * trait gate. The auth middleware in a browser context redirects
     * guests to the login page (302); in a non-web context it 403s.
     */
    public function test_guest_is_blocked_at_auth_middleware(): void
    {
        $resp = $this->get('/applicant/dashboard');
        $status = $resp->getStatusCode();
        $this->assertContains($status, [302, 403],
            "guest should be blocked at the auth middleware (302 or 403), got {$status}");
        $this->assertNotSame(200, $status);
    }

    // ----------------------------------------------------------------
    // Route middleware — wrong role
    // ----------------------------------------------------------------

    /**
     * Bursar (auth-admitted but no applicant slugs) is 403'd at the
     * permission: middleware. The route uses bare `auth`, not
     * `role:applicant`, so the auth middleware admits the bursar;
     * the permission: middleware is the slug-level check.
     */
    public function test_bursar_is_403_at_permission_middleware(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/applicant/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar (authenticated but no applicant.* slugs) should be 403 at permission:applicant.application.manage');
    }

    // ----------------------------------------------------------------
    // Route middleware — wildcard roles
    // ----------------------------------------------------------------

    /**
     * Super admin wildcard passes the permission: middleware.
     */
    public function test_super_admin_wildcard_passes_dashboard_route(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/applicant/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin should pass permission:applicant.application.manage');
    }

    // ----------------------------------------------------------------
    // Route middleware — slug-specific check (the new behaviour)
    // ----------------------------------------------------------------

    /**
     * Applicant with all slugs passes the permission: middleware on
     * every route. This is the regression guard — without the
     * explicit grants in ApplicantPermissions, an applicant would
     * have been 403'd at the permission: middleware even though the
     * auth middleware admits them.
     */
    public function test_applicant_with_all_slugs_passes_dashboard_route(): void
    {
        $applicant = $this->makeUser('applicant');

        $resp = $this->actingAs($applicant)->get('/applicant/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'applicant with applicant.application.manage should pass the dashboard route');
    }

    /**
     * The PRIMARY regression slice 8i-applicant-routes enables: an
     * applicant with only ONE slug is 403'd at a route gated on a
     * different slug. We use an applicant-role user whose pivot
     * grants only applicant.application.manage — they pass the
     * dashboard route, fail the payment gateway route.
     */
    public function test_applicant_with_only_application_slug_is_403_at_payment_gateway(): void
    {
        $applicant = $this->makeUserWithSubset('applicant', [
            'applicant.application.manage',
        ]);

        $resp = $this->actingAs($applicant)->get('/applicant/payment/gateway');
        $this->assertSame(403, $resp->getStatusCode(),
            'applicant with only applicant.application.manage should be 403 at permission:applicant.payments.manage');
    }

    /**
     * Same scenario for auto-login: an applicant without
     * applicant.auto-login.issue is 403'd at /applicant/auto-login.
     */
    public function test_applicant_without_auto_login_slug_is_403(): void
    {
        $applicant = $this->makeUserWithSubset('applicant', [
            'applicant.application.manage',
            'applicant.payments.manage',
            'applicant.payments.validate',
            'applicant.payments.receipt',
            // applicant.auto-login.issue is intentionally NOT granted.
        ]);

        $resp = $this->actingAs($applicant)->get('/applicant/auto-login');
        $this->assertSame(403, $resp->getStatusCode(),
            'applicant without applicant.auto-login.issue should be 403 at /applicant/auto-login');
    }

    /**
     * Same scenario for payment receipt: an applicant without
     * applicant.payments.receipt is 403'd at the receipt route.
     */
    public function test_applicant_without_payments_receipt_slug_is_403(): void
    {
        $applicant = $this->makeUserWithSubset('applicant', [
            'applicant.payments.manage',
        ]);

        // The {payment} segment is polymorphic — use an arbitrary id
        // that will miss everything. The permission: middleware
        // fires before the model binding.
        $resp = $this->actingAs($applicant)->get('/applicant/payments/1/receipt');
        $this->assertSame(403, $resp->getStatusCode(),
            'applicant without applicant.payments.receipt should be 403 at /applicant/payments/{id}/receipt');
    }

    /**
     * Same scenario for the validate-payment POST: an applicant
     * without applicant.payments.validate is 403'd at the
     * POST /validate-payment route.
     */
    public function test_applicant_without_validate_slug_is_403_at_post(): void
    {
        $applicant = $this->makeUserWithSubset('applicant', [
            'applicant.application.manage',
        ]);

        $resp = $this->actingAs($applicant)->post('/applicant/validate-payment', [
            'transaction_id' => 'TEST12345',
        ]);
        $this->assertSame(403, $resp->getStatusCode(),
            'applicant without applicant.payments.validate should be 403 at POST /applicant/validate-payment');
    }

    /**
     * Bursar is 403'd at the auto-login route — proves the
     * permission: middleware is slug-specific, not just "any
     * authenticated user passes".
     */
    public function test_bursar_is_403_at_auto_login_route(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/applicant/auto-login');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at /applicant/auto-login');
    }

    /**
     * Bursar is 403'd at the payment receipt route.
     */
    public function test_bursar_is_403_at_payment_receipt_route(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/applicant/payments/1/receipt');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at /applicant/payments/{id}/receipt');
    }

    /**
     * Bursar is 403'd at the payment gateway route.
     */
    public function test_bursar_is_403_at_payment_gateway_route(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/applicant/payment/gateway');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at /applicant/payment/gateway');
    }

    // ----------------------------------------------------------------
    // Public routes — NOT gated
    // ----------------------------------------------------------------

    /**
     * The GET /applicant/validate-payment is intentionally NOT
     * gated — guests need to reach it to enter a transaction ID
     * and bootstrap an applicant row. The POST IS gated (see
     * test_applicant_without_validate_slug_is_403_at_post above).
     *
     * We assert the GET is reachable for an authenticated bursar
     * (proving the route itself doesn't carry a permission: slug).
     * The body might 500 in test env (missing applicants table) —
     * we only care that the permission: middleware doesn't fire.
     */
    public function test_get_validate_payment_is_public(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/applicant/validate-payment');
        // The status can be 200, 302 (redirect), or 500 (test-env
        // body error). What matters is it's NOT 403.
        $this->assertNotSame(403, $resp->getStatusCode(),
            'GET /applicant/validate-payment should be public (no permission: middleware)');
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
        foreach (self::catalogue as $slug => $group) {
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

    /**
     * Make a user whose role pivot grants ONLY the supplied slugs.
     * Mirrors the helper in MaintenanceControllerGateTest.
     */
    private function makeUserWithSubset(string $roleSlug, array $slugs): User
    {
        $role = Role::firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst(str_replace('_', ' ', $roleSlug))],
        );
        $role->permissions()->sync(
            Permission::whereIn('slug', $slugs)->pluck('id')->all()
        );
        PermissionService::flush();
        return $this->makeUser($roleSlug);
    }
}