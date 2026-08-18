<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-admin-fees (sub-slice 5 of 8i-admin) regression —
 * the 3 fee-config Admin\*.php controllers (FeeController,
 * PaymentTypeController, PaymentFlowController) now call
 * `requirePermission()` at the top of every public method.
 *
 * 6+7+2 = 15 public methods = 15 gates. 3 unique per-controller
 * slugs:
 *
 *   admin.fees.manage          (FeeController)
 *   admin.payment-types.manage (PaymentTypeController)
 *   admin.payment-flows.manage (PaymentFlowController)
 *
 * No dual-use routes in this group — every method is reachable
 * only via auth-admin routes, so all 15 methods are gated.
 *
 * These three controllers together comprise the Finance Admin
 * surface — they configure the catalogue of fees and payment
 * types that drive the applicant dashboard's payable list and
 * the bursar's reconciliation flow. Before this slice, every
 * fee-config endpoint was reachable by any user in the route's
 * `role:super_admin,admin,ict_admin,staff` allowlist with no
 * slug-level check. The trait gate added in this slice closes
 * that gap.
 */
class AdminFeesControllerGateTest extends TestCase
{
    private const catalogue = [
        // Sub-slice 5 (this slice).
        'admin.fees.manage'          => 'admin',
        'admin.payment-types.manage' => 'admin',
        'admin.payment-flows.manage' => 'admin',
        // Sub-slices 1-4 (needed because ict_admin / staff roles
        // mirror the full admin allowlist).
        'admin.schools.manage'            => 'admin',
        'admin.departments.manage'        => 'admin',
        'admin.programmes.manage'         => 'admin',
        'admin.sessions.manage'           => 'admin',
        'admin.grading.manage'            => 'admin',
        'admin.grades.manage'             => 'admin',
        'admin.users.manage'              => 'admin',
        'admin.user-roles.manage'         => 'admin',
        'admin.user-unlocks.manage'       => 'admin',
        'admin.students.manage'           => 'admin',
        'admin.student-imports.manage'    => 'admin',
        'admin.student-id-cards.manage'   => 'admin',
        'admin.admission-centres.manage'  => 'admin',
        'admin.courses.manage'            => 'admin',
        'admin.course-assignments.manage' => 'admin',
        'admin.course-registrations.manage' => 'admin',
        'admin.exam-timetables.manage'    => 'admin',
        'admin.timetables.manage'         => 'admin',
        'admin.results.manage'            => 'admin',
    ];

    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'ict_admin' => [
            // Sub-slice 5 (this slice).
            'admin.fees.manage',
            'admin.payment-types.manage',
            'admin.payment-flows.manage',
        ],
        'staff' => [
            // Sub-slice 5 (this slice).
            'admin.fees.manage',
            'admin.payment-types.manage',
            'admin.payment-flows.manage',
        ],

        // Bursar — wrong role, no admin slugs.
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

    public function test_guest_is_blocked_at_auth_middleware_fees_index(): void
    {
        $resp = $this->get('/admin/fees');
        $status = $resp->getStatusCode();
        $this->assertContains($status, [302, 403],
            "guest should be blocked at auth middleware (302 or 403), got {$status}");
        $this->assertNotSame(200, $status);
    }

    // ----------------------------------------------------------------
    // Route middleware — wrong role
    // ----------------------------------------------------------------

    public function test_bursar_is_403_at_role_middleware_fees_index(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/admin/fees');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at the route role: middleware');
    }

    // ----------------------------------------------------------------
    // Controller gate — wildcard roles
    // ----------------------------------------------------------------

    public function test_super_admin_wildcard_passes_fees_index(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/fees');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the FeeController gate');
    }

    public function test_super_admin_wildcard_passes_payment_types(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/payment-types');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the PaymentTypeController gate');
    }

    public function test_super_admin_wildcard_passes_payment_flow(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/admission/payment-flow');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the PaymentFlowController gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — ict_admin / staff (mirroring the route allowlist)
    // ----------------------------------------------------------------

    public function test_ict_admin_with_full_grants_passes_fees_index(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->get('/admin/fees');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.fees.manage should pass the FeeController gate');
    }

    public function test_staff_with_full_grants_passes_payment_types(): void
    {
        $staff = $this->makeUser('staff');

        $resp = $this->actingAs($staff)->get('/admin/payment-types');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'staff with admin.payment-types.manage should pass the PaymentTypeController gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — per-controller slug isolation
    // ----------------------------------------------------------------

    /**
     * PRIMARY regression slice 8i-admin-fees closes: an
     * ict_admin with ONLY admin.fees.manage (no payment slug)
     * is 403'd at a controller that requires a DIFFERENT slug.
     * Without this slice, the route's role: middleware admitted
     * ict_admin to every fee-config endpoint regardless of which
     * slug the controller needed.
     */
    public function test_ict_admin_without_payment_types_slug_is_403_at_payment_types(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.fees.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/payment-types');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.payment-types.manage should be 403 at /admin/payment-types');
    }

    public function test_ict_admin_without_payment_flows_slug_is_403_at_payment_flow(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.fees.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/admission/payment-flow');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.payment-flows.manage should be 403 at /admin/admission/payment-flow');
    }

    public function test_ict_admin_without_fees_slug_is_403_at_fees_index(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.payment-types.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/fees');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.fees.manage should be 403 at /admin/fees');
    }

    /**
     * ict_admin with all 3 fee-config slugs reaches every controller.
     * Proves the wiring is symmetric.
     */
    public function test_ict_admin_with_all_fee_slugs_passes_all_controllers(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $urls = [
            '/admin/fees',
            '/admin/payment-types',
            '/admin/admission/payment-flow',
        ];
        foreach ($urls as $url) {
            $resp = $this->actingAs($ictAdmin)->get($url);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "ict_admin with all slugs should pass the controller gate at {$url}");
        }
    }

    /**
     * ict_admin with the full fee slug set can PUT (destructive verb)
     * on the PaymentFlowController. Proves the per-resource slug
     * covers all verbs, not just GET.
     */
    public function test_ict_admin_with_payment_flows_slug_passes_destructive_verbs(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        // PUT /admin/admission/payment-flow (update). Body 500s in
        // test env (missing tables) but the gate is what we care about.
        $resp = $this->actingAs($ictAdmin)->put('/admin/admission/payment-flow', [
            'overrides' => [],
            'is_active' => [],
        ]);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.payment-flows.manage should pass the controller gate on PUT');
    }

    /**
     * ict_admin with admin.payment-types.manage can POST the
     * destructive-verb toggle endpoint. Independent regression guard
     * for the toggle method on the PaymentTypeController.
     */
    public function test_ict_admin_with_payment_types_slug_passes_toggle(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->post('/admin/payment-types/1/toggle');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.payment-types.manage should pass the toggle route');
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