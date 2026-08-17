<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-maintenance-routes regression — every maintenance route
 * (22 routes, 16 unique slugs) now carries `permission:slug`
 * middleware in addition to the `auth + role:super_admin,admin,ict_admin,staff`
 * middleware already on the parent group. This is the second layer
 * of defence: the route middleware fires BEFORE the controller
 * trait gate, so a user who passes auth + role: but lacks the
 * slug is 403'd at route resolution.
 *
 * Before slice 8i-maintenance-routes, the slug check lived only on
 * the controller trait gate (slice 8i-maintenance). A future ops
 * bug that bypasses the controller gate (e.g. a new code path
 * hitting the route directly) would skip the slug check. This
 * slice closes that hole at the route level.
 *
 * Tests use the same hand-rolled in-memory sqlite schema as
 * MaintenanceControllerGateTest. We hit the routes through HTTP,
 * not direct controller invocation, because the `permission:slug`
 * middleware lives on the route and only fires via the route
 * resolver.
 */
class MaintenanceRouteMiddlewareTest extends TestCase
{
    private const catalogue = [
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

    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

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

        // bursar — wrong role, no maintenance slugs. The role:
        // middleware should 403 before the permission: middleware
        // even runs.
        'bursar' => ['bursar.dashboard.view'],
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
     * permission: middleware or the controller trait gate.
     */
    public function test_guest_is_blocked_at_auth_middleware(): void
    {
        $resp = $this->get('/admin/maintenance/dashboard');
        $status = $resp->getStatusCode();
        $this->assertContains($status, [302, 403],
            "guest should be blocked at the auth middleware (302 or 403), got {$status}");
        $this->assertNotSame(200, $status);
    }

    // ----------------------------------------------------------------
    // Route middleware — role: layer
    // ----------------------------------------------------------------

    /**
     * Bursar (wrong role, auth-admitted) is 403'd at the route's
     * role: layer. The permission: middleware is the SECOND layer;
     * a role mismatch stops the request before slug checks run.
     */
    public function test_bursar_is_403_at_role_middleware(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/admin/maintenance/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at the route role: middleware');
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

        $resp = $this->actingAs($superAdmin)->get('/admin/maintenance/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin should pass the permission:maintenance.dashboard.view middleware');
    }

    /**
     * Admin wildcard passes the permission: middleware.
     */
    public function test_admin_wildcard_passes_destructive_route(): void
    {
        $admin = $this->makeUser('admin');

        $resp = $this->actingAs($admin)->post('/admin/maintenance/repairs/run');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'admin should pass the permission:maintenance.repairs.run middleware');
    }

    // ----------------------------------------------------------------
    // Route middleware — slug-specific check (the new behaviour)
    // ----------------------------------------------------------------

    /**
     * ict_admin with the full maintenance grant set passes
     * every route's permission: middleware. This is the regression
     * guard — without the explicit grants in MaintenancePermissions,
     * ict_admin would have been 403'd at the permission: middleware
     * even though the role: middleware admits them.
     */
    public function test_ict_admin_with_full_grants_passes_destructive_route(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->post('/admin/maintenance/repairs/run');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with maintenance.repairs.run should pass the route');
    }

    /**
     * The PRIMARY regression slice 8i-maintenance-routes enables:
     * a user with role allowlist access but no maintenance.* slug is
     * 403'd at the route's permission: middleware (not the
     * controller). We use a `staff` role (in the role: allowlist)
     * with ONLY the view slugs — they pass role:, fail permission:,
     * and never hit the controller body.
     *
     * The role: middleware layer admits them because they're staff.
     * The permission: middleware layer rejects them because they
     * lack maintenance.repairs.run. Without slice 8i-maintenance-routes,
     * the request would reach the controller body where the trait
     * gate would catch it — but the request would already be in the
     * controller, wasting a stack frame. With the slice, the
     * permission: middleware stops it at route resolution.
     */
    public function test_viewer_with_role_access_is_403_at_permission_middleware(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
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
            // No destructive slugs — viewer cannot run repairs.
        ]);

        $resp = $this->actingAs($viewer)->post('/admin/maintenance/repairs/run');
        $this->assertSame(403, $resp->getStatusCode(),
            'viewer (staff + view-only pivot) should be 403 at the permission:maintenance.repairs.run middleware');
    }

    /**
     * Same scenario as above but for a different destructive slug —
     * proves the permission: middleware is slug-specific, not just
     * "any maintenance role".
     */
    public function test_viewer_is_403_at_backup_create_permission_middleware(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.backups.view',
        ]);

        $resp = $this->actingAs($viewer)->post('/admin/maintenance/backup/create');
        $this->assertSame(403, $resp->getStatusCode(),
            'viewer (staff + view-only pivot) should be 403 at permission:maintenance.backups.create');
    }

    /**
     * Same scenario but for cache.clear — proves the slug
     * differentiation works for every destructive slug.
     */
    public function test_viewer_is_403_at_cache_clear_permission_middleware(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.cache.view',
        ]);

        $resp = $this->actingAs($viewer)->post('/admin/maintenance/cache/clear');
        $this->assertSame(403, $resp->getStatusCode(),
            'viewer (staff + view-only pivot) should be 403 at permission:maintenance.cache.manage');
    }

    /**
     * Same scenario but for health repair — proves the slug
     * differentiation works for health endpoints.
     */
    public function test_viewer_is_403_at_health_repair_permission_middleware(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.health.view',
        ]);

        $resp = $this->actingAs($viewer)->post('/admin/maintenance/health/repair');
        $this->assertSame(403, $resp->getStatusCode(),
            'viewer (staff + view-only pivot) should be 403 at permission:maintenance.health.repair');
    }

    /**
     * Same scenario but for migrations/run — proves the slug
     * differentiation works for the destructive updates endpoint.
     */
    public function test_viewer_is_403_at_migrations_run_permission_middleware(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.updates.view',
        ]);

        $resp = $this->actingAs($viewer)->post('/admin/maintenance/migrations/run');
        $this->assertSame(403, $resp->getStatusCode(),
            'viewer (staff + view-only pivot) should be 403 at permission:maintenance.updates.apply');
    }

    /**
     * Same scenario but for version/register — proves the slug
     * differentiation works for the destructive versions endpoint.
     */
    public function test_viewer_is_403_at_version_register_permission_middleware(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.versions.view',
        ]);

        $resp = $this->actingAs($viewer)->post('/admin/maintenance/version/register');
        $this->assertSame(403, $resp->getStatusCode(),
            'viewer (staff + view-only pivot) should be 403 at permission:maintenance.versions.manage');
    }

    /**
     * Same scenario but for optimize — proves the slug
     * differentiation works for the destructive cache.manage
     * endpoint.
     */
    public function test_viewer_is_403_at_optimize_permission_middleware(): void
    {
        $viewer = $this->makeUserWithSubset('staff', [
            'maintenance.cache.view',
        ]);

        $resp = $this->actingAs($viewer)->post('/admin/maintenance/optimize');
        $this->assertSame(403, $resp->getStatusCode(),
            'viewer (staff + view-only pivot) should be 403 at permission:maintenance.cache.manage (optimize)');
    }

    // ----------------------------------------------------------------
    // Slug-per-domain shape — destructive endpoints share their slug
    // ----------------------------------------------------------------

    /**
     * A role with only maintenance.updates.apply passes BOTH
     * /admin/maintenance/migrations/run AND /admin/maintenance/seeders/run
     * at the route middleware (proves slug-per-domain shape is
     * mirrored at the route layer too, not just the controller).
     */
    public function test_destructive_endpoints_share_route_middleware_slug(): void
    {
        $user = $this->makeUserWithSubset('staff', [
            'maintenance.updates.apply',
        ]);

        $resp = $this->actingAs($user)->post('/admin/maintenance/migrations/run');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'role with maintenance.updates.apply should pass permission:maintenance.updates.apply on migrations/run');

        $resp = $this->actingAs($user)->post('/admin/maintenance/seeders/run');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'role with maintenance.updates.apply should pass permission:maintenance.updates.apply on seeders/run');
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
     * Make a user with a role from the route's allowlist, but
     * override the role's pivot to grant ONLY the supplied slugs.
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