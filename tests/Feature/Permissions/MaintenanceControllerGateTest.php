<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-maintenance regression — Admin\MaintenanceController (21
 * public methods, 10 unique slugs across the maintenance.* domain)
 * now calls `requirePermission($slug)` at the top of every entry
 * point.
 *
 * Before slice 8i-maintenance, the maintenance routes relied on
 * `auth + role:super_admin,admin,ict_admin,staff` middleware. That
 * allowed any ict_admin or staff user to reach every maintenance
 * endpoint with no slug-level check. The trait gate added in this
 * slice closes that hole: any authenticated user who lacks the
 * specific maintenance.* slug is 403'd at the controller body.
 *
 * Tests use the same hand-rolled in-memory sqlite schema as the
 * other permission tests (see ApplicantControllerGateTest). We seed
 * only the maintenance slugs and the roles we need to drive the
 * test matrix.
 *
 * Note: we hit the routes through HTTP (not direct controller
 * invocation) because the route's `auth` + `role:` middleware chain
 * is what admits the user. The controller trait gate then fires
 * AFTER the middleware, denying users who lack the slug. We assert
 * on status code — not response body — because the underlying
 * controller body may 500 in the test env (missing
 * SystemMaintenanceService dependencies) and the gate is what we
 * care about.
 */
class MaintenanceControllerGateTest extends TestCase
{
    /**
     * The 16 unique maintenance slugs covering the 21 public methods
     * (some methods share a slug because they're in the same domain,
     * e.g. runMigrations and runSeeders both map to
     * maintenance.updates.apply).
     */
    private const CATALOG = [
        'maintenance.dashboard.view'  => 'maintenance',
        'maintenance.health.view'     => 'maintenance',
        'maintenance.health.repair'   => 'maintenance',
        'maintenance.updates.view'    => 'maintenance',
        'maintenance.updates.apply'   => 'maintenance',
        'maintenance.repairs.view'    => 'maintenance',
        'maintenance.repairs.run'     => 'maintenance',
        'maintenance.scanners.view'   => 'maintenance',
        'maintenance.cache.view'      => 'maintenance',
        'maintenance.cache.manage'    => 'maintenance',
        'maintenance.backups.view'    => 'maintenance',
        'maintenance.backups.create'  => 'maintenance',
        'maintenance.logs.view'       => 'maintenance',
        'maintenance.versions.view'   => 'maintenance',
        'maintenance.versions.manage' => 'maintenance',
        'maintenance.report.view'     => 'maintenance',
    ];

    /**
     * Per-role grants used by the test matrix. Wildcard roles pass
     * every gate; ict_admin and staff mirror the route's allowlist
     * (full maintenance set); maintenance_viewer is a hypothetical
     * role granted only the read-only `*.view` slugs.
     */
    private const ROLE_PERMISSIONS = [
        // Wildcard roles — pass every gate.
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        // Existing route-allowlist roles — full maintenance access
        // (mirrors the route's `role:super_admin,admin,ict_admin,staff`
        // middleware chain).
        'ict_admin' => [
            'maintenance.dashboard.view',
            'maintenance.health.view',
            'maintenance.health.repair',
            'maintenance.updates.view',
            'maintenance.updates.apply',
            'maintenance.repairs.view',
            'maintenance.repairs.run',
            'maintenance.scanners.view',
            'maintenance.cache.view',
            'maintenance.cache.manage',
            'maintenance.backups.view',
            'maintenance.backups.create',
            'maintenance.logs.view',
            'maintenance.versions.view',
            'maintenance.versions.manage',
            'maintenance.report.view',
        ],
        'staff' => [
            'maintenance.dashboard.view',
            'maintenance.health.view',
            'maintenance.health.repair',
            'maintenance.updates.view',
            'maintenance.updates.apply',
            'maintenance.repairs.view',
            'maintenance.repairs.run',
            'maintenance.scanners.view',
            'maintenance.cache.view',
            'maintenance.cache.manage',
            'maintenance.backups.view',
            'maintenance.backups.create',
            'maintenance.logs.view',
            'maintenance.versions.view',
            'maintenance.versions.manage',
            'maintenance.report.view',
        ],

        // Hypothetical read-only role — gets only the *.view slugs.
        'maintenance_viewer' => [
            'maintenance.dashboard.view',
            'maintenance.health.view',
            'maintenance.updates.view',
            'maintenance.repairs.view',
            'maintenance.scanners.view',
            'maintenance.cache.view',
            'maintenance.backups.view',
            'maintenance.logs.view',
            'maintenance.versions.view',
            'maintenance.report.view',
        ],

        // Bursar — has bursar.* slugs but is NOT supposed to access
        // maintenance endpoints. The route's role: middleware
        // admits ict_admin/staff, so a bursar fails the role check
        // (the user is in a bursar role). The trait gate is the
        // second layer; if the role middleware is misconfigured, the
        // trait gate still 403s them because they have no
        // maintenance.* slugs.
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
     * Guest is blocked at the auth middleware — never reaches the
     * controller gate. The `auth` middleware in a browser context
     * redirects guests to the login page (302); in a non-web request
     * context (e.g. API consumer) it 403s. Both are correct — the
     * test is asserting that the guest is denied, not that they get
     * a specific status code.
     */
    public function test_guest_is_blocked_at_auth_middleware(): void
    {
        $resp = $this->get('/admin/maintenance/dashboard');
        $status = $resp->getStatusCode();
        $this->assertContains($status, [302, 403],
            "guest should be blocked at the auth middleware (302 redirect or 403), got {$status}");
        $this->assertNotSame(200, $status,
            'guest must never reach a 200 response on a maintenance route');
    }

    // ----------------------------------------------------------------
    // Controller gate — wrong role
    // ----------------------------------------------------------------

    /**
     * Sanity test — bursar is blocked at the route's role: middleware
     * (bursar is not in the role:super_admin,admin,ict_admin,staff
     * allowlist). The trait gate is the second layer; both layers
     * agree that bursar should not reach maintenance endpoints.
     */
    public function test_bursar_is_403_at_route_middleware(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/admin/maintenance/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at the route role: middleware');
    }

    // ----------------------------------------------------------------
    // Controller gate — wildcard roles
    // ----------------------------------------------------------------

    /**
     * Super admin wildcard passes the controller gate.
     */
    public function test_super_admin_wildcard_passes_dashboard(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/maintenance/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the controller gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — ict_admin / staff (mirroring the route allowlist)
    // ----------------------------------------------------------------

    /**
     * ict_admin with the full maintenance grant set passes the
     * controller gate. They reach every maintenance endpoint today
     * via the route's role: middleware; after slice 8i-maintenance
     * they continue to do so via the trait gate's slug check.
     */
    public function test_ict_admin_with_slug_passes_dashboard(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->get('/admin/maintenance/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin should pass the controller gate at /admin/maintenance/dashboard');
    }

    /**
     * Staff with the full maintenance grant set passes the controller
     * gate. Staff is the other half of the route's allowlist.
     */
    public function test_staff_with_slug_passes_dashboard(): void
    {
        $staff = $this->makeUser('staff');

        $resp = $this->actingAs($staff)->get('/admin/maintenance/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'staff should pass the controller gate at /admin/maintenance/dashboard');
    }

    // ----------------------------------------------------------------
    // Controller gate — view-only role split (slice 8i-maintenance's
    // primary motivation: a future maintenance_viewer can have read-only
    // access without destructive capability)
    // ----------------------------------------------------------------

    /**
     * maintenance_viewer passes the read-only dashboard endpoint.
     * Uses `staff` as the role (which IS in the route's
     * role:super_admin,admin,ict_admin,staff allowlist), then
     * overrides the pivot to grant only the read-only `*.view`
     * slugs — simulating a future operator who has read-only
     * maintenance access.
     */
    public function test_maintenance_viewer_passes_dashboard(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.dashboard.view',
        ]);

        $resp = $this->actingAs($viewer)->get('/admin/maintenance/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'viewer with maintenance.dashboard.view should pass the dashboard gate');
    }

    /**
     * maintenance_viewer passes the read-only health view endpoint.
     */
    public function test_maintenance_viewer_passes_health_view(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.health.view',
        ]);

        $resp = $this->actingAs($viewer)->get('/admin/maintenance/health');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'viewer with maintenance.health.view should pass the health view gate');
    }

    /**
     * maintenance_viewer is 403'd at the destructive health repair
     * endpoint — they have maintenance.health.view but NOT
     * maintenance.health.repair. This is the primary regression
     * slice 8i-maintenance enables: a viewer can see the health
     * report but cannot repair it.
     */
    public function test_maintenance_viewer_is_403_at_destructive_health_repair(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.health.view',
            // maintenance.health.repair is intentionally NOT granted.
        ]);

        $resp = $this->actingAs($viewer)->post('/admin/maintenance/health/repair');
        $this->assertSame(403, $resp->getStatusCode(),
            'viewer without maintenance.health.repair should be 403 at /admin/maintenance/health/repair');
    }

    /**
     * maintenance_viewer is 403'd at the destructive repairs.run
     * endpoint.
     */
    public function test_maintenance_viewer_is_403_at_repairs_run(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.repairs.view',
        ]);

        $resp = $this->actingAs($viewer)->post('/admin/maintenance/repairs/run');
        $this->assertSame(403, $resp->getStatusCode(),
            'viewer without maintenance.repairs.run should be 403 at /admin/maintenance/repairs/run');
    }

    /**
     * maintenance_viewer is 403'd at the destructive cache.clear
     * endpoint.
     */
    public function test_maintenance_viewer_is_403_at_cache_clear(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.cache.view',
        ]);

        $resp = $this->actingAs($viewer)->post('/admin/maintenance/cache/clear');
        $this->assertSame(403, $resp->getStatusCode(),
            'viewer without maintenance.cache.manage should be 403 at /admin/maintenance/cache/clear');
    }

    /**
     * maintenance_viewer is 403'd at the destructive backup.create
     * endpoint.
     */
    public function test_maintenance_viewer_is_403_at_backup_create(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.backups.view',
        ]);

        $resp = $this->actingAs($viewer)->post('/admin/maintenance/backup/create');
        $this->assertSame(403, $resp->getStatusCode(),
            'viewer without maintenance.backups.create should be 403 at /admin/maintenance/backup/create');
    }

    // ----------------------------------------------------------------
    // Slug-per-domain shape — destructive endpoints share their slug
    // ----------------------------------------------------------------

    /**
     * A role with only maintenance.updates.apply passes BOTH
     * /admin/maintenance/migrations/run AND /admin/maintenance/seeders/run
     * because they share the same slug. This proves the
     * slug-per-domain shape (not slug-per-method). Uses `staff` as
     * the role (in the route's allowlist) and overrides the pivot
     * to grant only maintenance.updates.apply.
     */
    public function test_destructive_endpoints_share_their_slug(): void
    {
        $user = $this->makeUserWithSubset('staff', [
            'maintenance.updates.apply',
        ]);

        $resp = $this->actingAs($user)->post('/admin/maintenance/migrations/run');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'role with maintenance.updates.apply should pass /admin/maintenance/migrations/run');

        $resp = $this->actingAs($user)->post('/admin/maintenance/seeders/run');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'role with maintenance.updates.apply should pass /admin/maintenance/seeders/run (shared slug)');
    }

    /**
     * ict_admin passes the destructive repairs.run endpoint. They
     * have maintenance.repairs.run in their grant list.
     */
    public function test_ict_admin_passes_destructive_repair(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->post('/admin/maintenance/repairs/run');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with maintenance.repairs.run should pass /admin/maintenance/repairs/run');
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

    /**
     * Make a user with a role from the route's allowlist, but
     * override the role's pivot to grant ONLY the supplied slugs.
     * This simulates a future operator who passes the route's
     * role: middleware but lacks the destructive maintenance.*
     * slugs — the precise scenario the trait gate is designed to
     * gate.
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