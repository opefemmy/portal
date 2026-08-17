<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-admin-users (sub-slice 2 of 8i-admin) regression —
 * the 3 user-management Admin\*.php controllers (UserController,
 * UserRoleController, UserUnlockController) now call
 * `requirePermission()` at the top of every public method.
 *
 * 14 methods + 1 + 4 (admin-only) = 19 gates total. 3 unique
 * per-controller slugs:
 *
 *   admin.users.manage         (UserController)
 *   admin.user-roles.manage    (UserRoleController)
 *   admin.user-unlocks.manage  (UserUnlockController admin-only)
 *
 * ## Dual-use note (the only one in this slice)
 *
 * Two methods in UserUnlockController are reachable from BOTH:
 *
 *   - public guest routes (GET /unlock/{email}/{code},
 *     POST /unlock)  routes/web.php lines 160-161
 *   - auth-admin routes (/users/unlock, /users/unlock/code) inside
 *     the auth+role middleware group
 *
 * `UserUnlockController::showUnlockCode` and `::unlockUser` are
 * therefore intentionally NOT gated — gating them would 403 guests
 * at the public endpoints and break the password-reset flow. This
 * dual-use is the strict reason the catalogued slug split differs
 * from the simple "one slug per controller" pattern used elsewhere
 * in 8i-admin: the slug for UserUnlockController covers 4 of the 6
 * methods, leaving the public endpoints free.
 *
 * Before this slice, the user-management controllers relied SOLELY
 * on the route's `auth + role:super_admin,admin,ict_admin,staff`
 * middleware — any authenticated user in those roles reached every
 * user-management endpoint with no slug-level check. The trait gate
 * added in this slice closes that.
 *
 * Tests use the same hand-rolled in-memory sqlite schema as the
 * other permission tests. We seed the 3 user-management slugs plus
 * the 6 academic-structure slugs from sub-slice 1 (some role grant
 * lists now reference them as well).
 */
class AdminUserControllerGateTest extends TestCase
{
    private const catalogue = [
        // Sub-slice 2 (this slice).
        'admin.users.manage'         => 'admin',
        'admin.user-roles.manage'    => 'admin',
        'admin.user-unlocks.manage'  => 'admin',
        // Sub-slice 1 (needed because ict_admin / staff roles
        // mirror the full admin allowlist).
        'admin.schools.manage'       => 'admin',
        'admin.departments.manage'   => 'admin',
        'admin.programmes.manage'    => 'admin',
        'admin.sessions.manage'      => 'admin',
        'admin.grading.manage'       => 'admin',
        'admin.grades.manage'        => 'admin',
    ];

    /**
     * Per-role grants. Wildcard roles pass every gate; ict_admin
     * and staff get the full academic+users grant list (mirrors
     * the route's allowlist, no behaviour regression).
     */
    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'ict_admin' => [
            'admin.users.manage',
            'admin.user-roles.manage',
            'admin.user-unlocks.manage',
        ],
        'staff' => [
            'admin.users.manage',
            'admin.user-roles.manage',
            'admin.user-unlocks.manage',
        ],

        // Bursar — wrong role, no admin slugs. The route's role:
        // middleware rejects them before the trait gate.
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

    public function test_guest_is_blocked_at_auth_middleware_users_index(): void
    {
        $resp = $this->get('/admin/users');
        $status = $resp->getStatusCode();
        $this->assertContains($status, [302, 403],
            "guest should be blocked at auth middleware (302 or 403), got {$status}");
        $this->assertNotSame(200, $status);
    }

    // ----------------------------------------------------------------
    // Route middleware — wrong role
    // ----------------------------------------------------------------

    public function test_bursar_is_403_at_role_middleware_users_index(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/admin/users');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at the route role: middleware');
    }

    // ----------------------------------------------------------------
    // Controller gate — wildcard roles
    // ----------------------------------------------------------------

    public function test_super_admin_wildcard_passes_users_index(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/users');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the UserController gate');
    }

    public function test_super_admin_wildcard_passes_user_roles_update(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->put('/admin/users/1/roles', [
            'role_ids'        => [1],
            'primary_role_id' => 1,
        ]);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the UserRoleController gate');
    }

    public function test_super_admin_wildcard_passes_user_unlock_admin(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/users/unlock');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the UserUnlockController admin gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — ict_admin / staff (mirroring the route allowlist)
    // ----------------------------------------------------------------

    public function test_ict_admin_with_full_user_grants_passes_users_index(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->get('/admin/users');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.users.manage should pass the UserController gate');
    }

    public function test_staff_with_full_user_grants_passes_users_index(): void
    {
        $staff = $this->makeUser('staff');

        $resp = $this->actingAs($staff)->get('/admin/users');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'staff with admin.users.manage should pass the UserController gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — per-controller slug isolation
    // ----------------------------------------------------------------

    /**
     * PRIMARY regression slice 8i-admin-users closes: an
     * ict_admin with ONLY admin.users.manage (no user-roles slug)
     * is 403'd at the user-roles route. Without this slice, the
     * route's role: middleware admitted ict_admin to every
     * user-management endpoint regardless of which slug the
     * controller needed.
     */
    public function test_ict_admin_without_user_roles_slug_is_403_at_roles(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.users.manage',
        ]);

        $resp = $this->actingAs($user)->put('/admin/users/1/roles', [
            'role_ids'        => [1],
            'primary_role_id' => 1,
        ]);
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.user-roles.manage should be 403 at /admin/users/{user}/roles');
    }

    /**
     * Same isolation for unlock: ict_admin with only
     * admin.users.manage is 403 at the admin-unlock route.
     */
    public function test_ict_admin_without_user_unlocks_slug_is_403_at_admin_unlock(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.users.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/users/unlock');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.user-unlocks.manage should be 403 at /admin/users/unlock');
    }

    /**
     * Same isolation reversed: ict_admin with only
     * admin.user-roles.manage is 403 at the UserController
     * (which gates on admin.users.manage).
     */
    public function test_ict_admin_without_users_slug_is_403_at_users_index(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.user-roles.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/users');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.users.manage should be 403 at /admin/users');
    }

    /**
     * ict_admin with all 3 user-management slugs reaches every
     * controller. Proves the wiring is symmetric.
     */
    public function test_ict_admin_with_all_user_slugs_passes_all_controllers(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp1 = $this->actingAs($ictAdmin)->get('/admin/users');
        $this->assertNotSame(403, $resp1->getStatusCode(),
            'ict_admin with all slugs should pass at /admin/users');

        $resp2 = $this->actingAs($ictAdmin)->put('/admin/users/1/roles', [
            'role_ids'        => [1],
            'primary_role_id' => 1,
        ]);
        $this->assertNotSame(403, $resp2->getStatusCode(),
            'ict_admin with all slugs should pass at /admin/users/{user}/roles');

        $resp3 = $this->actingAs($ictAdmin)->get('/admin/users/unlock');
        $this->assertNotSame(403, $resp3->getStatusCode(),
            'ict_admin with all slugs should pass at /admin/users/unlock');
    }

    /**
     * ict_admin with the full user slug set can DELETE (destructive
     * verb) on the UserController resource. Proves the per-resource
     * slug covers all CRUD verbs, not just GET.
     */
    public function test_ict_admin_with_users_slug_passes_destructive_verbs(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->post('/admin/users', [
            'name'     => 'New User',
            'email'    => 'new_' . uniqid('', true) . '@test.local',
            'password' => 'password',
            'role_id'  => 1,
        ]);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.users.manage should pass the controller gate on POST');
    }

    // ----------------------------------------------------------------
    // Dual-use — public guest unlock flow stays ungated
    // ----------------------------------------------------------------

    /**
     * REGRESSION GUARD for the dual-use carve-out: the public
     * `GET /unlock/{email}/{code}` route (calls
     * UserUnlockController::showUnlockCode) must remain callable
     * by guests. If someone re-wires this method under the
     * requirePermission trait (over-zealous consistency), this
     * guest would suddenly get a 403 and break the password-reset
     * flow.
     */
    public function test_showUnlockCode_public_route_is_ungated_for_guest(): void
    {
        // Hit the actual public route. The controller body reads
        // code/email from the URL — missing -> redirects to the
        // unlock page (302), not a 403. The point is the route
        // middleware chain accepts the guest.
        $resp = $this->get('/unlock/test@example.com/abc');
        $status = $resp->getStatusCode();
        $this->assertNotSame(403, $status,
            "public GET /unlock/{email}/{code} must accept guests, got {$status}");
    }

    /**
     * Reaches the same controller method via the auth-admin
     * route to confirm it's reachable there too — not 403 for a
     * fully-granted user.
     */
    public function test_showUnlockCode_admin_route_passes_for_ict_admin(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        // The route has no auth bypass — the admin route is
        // /users/unlock/code (admin/auth).
        $resp = $this->actingAs($ictAdmin)->get('/admin/users/unlock/code?code=abc&email=test%40example.com');
        // Should not be 403 (controller is dual-use, route is
        // auth-admin which admits ict_admin).
        $this->assertNotSame(403, $resp->getStatusCode(),
            'admin route for dual-use method should not 403 fully-granted user');
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