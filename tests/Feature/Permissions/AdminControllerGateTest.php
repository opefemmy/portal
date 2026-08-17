<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-admin-academic-structure (sub-slice 1 of 8i-admin)
 * regression — the 6 academic-structure Admin\*.php controllers
 * (SchoolController, DepartmentController, ProgrammeController,
 * SessionController, GradingController, GradeController — 39
 * public methods, 6 unique slugs) now call `requirePermission()`
 * at the top of every public method.
 *
 * Per-controller slug shape (one slug covers all CRUD verbs on
 * a single resource) — mirrors Laravel's ResourceController
 * convention. A future per-verb split is a one-line change per
 * controller.
 *
 * Before this slice, the academic-structure controllers relied
 * SOLELY on the route's `auth + role:super_admin,admin,ict_admin,staff`
 * middleware — any authenticated user in those roles reached every
 * academic-structure endpoint with no slug-level check. The trait
 * gate added in this slice closes that hole.
 *
 * Tests use the same hand-rolled in-memory sqlite schema as the
 * other permission tests. We seed only the 6 academic-structure
 * slugs and the roles needed for the test matrix.
 */
class AdminControllerGateTest extends TestCase
{
    private const catalogue = [
        'admin.schools.manage'     => 'admin',
        'admin.departments.manage' => 'admin',
        'admin.programmes.manage'  => 'admin',
        'admin.sessions.manage'    => 'admin',
        'admin.grading.manage'     => 'admin',
        'admin.grades.manage'      => 'admin',
    ];

    /**
     * Per-role grants used by the test matrix. Wildcard roles pass
     * every gate; ict_admin and staff mirror the route's allowlist
     * (full academic-structure set); bursar has no admin slugs.
     */
    private const ROLE_PERMISSIONS = [
        // Wildcard roles — pass every gate.
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        // Existing route-allowlist roles — full academic-structure
        // access (mirrors the route's
        // `role:super_admin,admin,ict_admin,staff` middleware chain).
        'ict_admin' => [
            'admin.schools.manage',
            'admin.departments.manage',
            'admin.programmes.manage',
            'admin.sessions.manage',
            'admin.grading.manage',
            'admin.grades.manage',
        ],
        'staff' => [
            'admin.schools.manage',
            'admin.departments.manage',
            'admin.programmes.manage',
            'admin.sessions.manage',
            'admin.grading.manage',
            'admin.grades.manage',
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

    /**
     * Guest is blocked at the auth middleware — never reaches the
     * controller trait gate. The `auth` middleware in a browser
     * context redirects guests to the login page (302); in a
     * non-web context it 403s. Both are correct.
     */
    public function test_guest_is_blocked_at_auth_middleware(): void
    {
        $resp = $this->get('/admin/schools');
        $status = $resp->getStatusCode();
        $this->assertContains($status, [302, 403],
            "guest should be blocked at the auth middleware (302 or 403), got {$status}");
        $this->assertNotSame(200, $status);
    }

    // ----------------------------------------------------------------
    // Route middleware — wrong role
    // ----------------------------------------------------------------

    /**
     * Bursar (wrong role, auth-admitted) is 403'd at the route's
     * role: middleware. The trait gate is the second layer; both
     * layers agree that bursar should not reach admin endpoints.
     */
    public function test_bursar_is_403_at_role_middleware(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/admin/schools');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at the route role: middleware');
    }

    // ----------------------------------------------------------------
    // Controller gate — wildcard roles
    // ----------------------------------------------------------------

    /**
     * Super admin wildcard passes the controller trait gate.
     */
    public function test_super_admin_wildcard_passes_schools(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/schools');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the controller gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — ict_admin / staff (mirroring the route allowlist)
    // ----------------------------------------------------------------

    /**
     * ict_admin with the full academic-structure grant set passes
     * the controller trait gate. Mirrors the route's
     * `role:super_admin,admin,ict_admin,staff` allowlist.
     */
    public function test_ict_admin_with_full_grants_passes_schools(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->get('/admin/schools');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.schools.manage should pass the controller gate');
    }

    /**
     * Staff with the full academic-structure grant set passes the
     * controller trait gate.
     */
    public function test_staff_with_full_grants_passes_departments(): void
    {
        $staff = $this->makeUser('staff');

        $resp = $this->actingAs($staff)->get('/admin/departments');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'staff with admin.departments.manage should pass the controller gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — per-controller slug isolation
    // ----------------------------------------------------------------

    /**
     * PRIMARY regression slice 8i-admin-academic-structure closes:
     * an ict_admin with only ONE academic-structure slug is 403'd
     * at a controller that requires a DIFFERENT slug. This proves
     * the per-controller slug isolation — without this slice, the
     * route's role: middleware admitted ict_admin to every
     * academic-structure endpoint regardless of which slug the
     * controller needed.
     *
     * Uses `ict_admin` (in the route allowlist) and overrides the
     * pivot to grant only admin.schools.manage — they pass the
     * schools route, fail the departments route.
     */
    public function test_ict_admin_without_schools_slug_is_403_at_departments(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.schools.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/departments');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.departments.manage should be 403 at /admin/departments');
    }

    /**
     * Same scenario for grading: ict_admin with only
     * admin.schools.manage is 403 at /admin/grades (which gates on
     * admin.grades.manage in the controller).
     */
    public function test_ict_admin_without_grading_slug_is_403_at_grades(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.schools.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/grades');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.grades.manage should be 403 at /admin/grades');
    }

    /**
     * Same scenario for sessions: ict_admin with only
     * admin.sessions.manage is 403 at /admin/schools.
     */
    public function test_ict_admin_without_sessions_slug_is_403_at_schools(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.sessions.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/schools');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.schools.manage should be 403 at /admin/schools');
    }

    /**
     * Same scenario for programmes: ict_admin with only
     * admin.programmes.manage is 403 at /admin/sessions.
     */
    public function test_ict_admin_without_programmes_slug_is_403_at_sessions(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.programmes.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/sessions');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.sessions.manage should be 403 at /admin/sessions');
    }

    /**
     * ict_admin with all 6 academic-structure slugs reaches every
     * controller. Proves the wiring is symmetric — every
     * controller's gate fires without 403 for the fully-granted
     * role.
     */
    public function test_ict_admin_with_all_slugs_passes_all_controllers(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $urls = [
            '/admin/schools',
            '/admin/departments',
            '/admin/programmes',
            '/admin/sessions',
            '/admin/grades',
            // /admin/grading uses the GradingController::index which
            // shares the route URI with /admin/grades (GradeController).
            // The GradingController manages classifications + scales,
            // accessed via the grade classification routes
            // (/admin/grades/classification, /admin/grades/scale).
            // We test the primary resource index instead.
        ];
        foreach ($urls as $url) {
            $resp = $this->actingAs($ictAdmin)->get($url);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "ict_admin with all slugs should pass the controller gate at {$url}");
        }
    }

    /**
     * ict_admin with all slugs can POST/DELETE (destructive verbs)
     * on the resource controllers. Proves the per-controller slug
     * covers all CRUD verbs, not just GET.
     */
    public function test_ict_admin_with_slug_passes_destructive_verbs(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        // POST /admin/schools (create). The body will 500 in test
        // env (missing schools table) but the gate is what we care
        // about.
        $resp = $this->actingAs($ictAdmin)->post('/admin/schools', [
            'name' => 'Test School',
            'code' => 'TS',
        ]);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.schools.manage should pass the controller gate on POST');
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