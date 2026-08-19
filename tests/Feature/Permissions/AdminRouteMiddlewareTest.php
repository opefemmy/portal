<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-admin-routes regression — every auth-admin route now
 * carries `permission:admin.X.manage` middleware in addition to the
 * `auth + role:super_admin,admin,ict_admin,staff` middleware already
 * on the parent group. This is the second layer of defence: the
 * route middleware fires BEFORE the controller trait gate, so a
 * user who passes auth + role: but lacks the slug is 403'd at route
 * resolution.
 *
 * Before slice 8i-admin-routes, the slug check lived only on the
 * controller trait gate (sub-slices 1-7 of 8i-admin). A future ops
 * bug that bypasses the controller gate (e.g. a new code path
 * hitting the route directly) would skip the slug check. This
 * slice closes that hole at the route level for ALL admin routes
 * that map to a single per-controller slug (the per-resource shape
 * the 8i-admin sub-slices adopted).
 *
 * Tests hit the routes through HTTP, not direct controller
 * invocation, because the `permission:slug` middleware lives on
 * the route and only fires via the route resolver.
 *
 * We don't exhaustively hit every one of the ~140 admin routes —
 * that would create a slow, brittle test. We sample representative
 * routes across all sub-slices (academic-structure, users,
 * students, academic-ops, fees, facilities, misc) to prove the
 * permission: middleware is wired and slug-specific everywhere.
 */
class AdminRouteMiddlewareTest extends TestCase
{
    /**
     * Sampled routes — one per controller slug, covering all 7
     * sub-slices of 8i-admin. The test ensures the route middleware
     * is wired and behaves per-slug, not per-bundle.
     */
    private const SAMPLE_ROUTES = [
        // Sub-slice 1: academic structure.
        ['GET',  '/admin/schools',                'admin.schools.manage'],
        ['GET',  '/admin/departments',            'admin.departments.manage'],
        ['GET',  '/admin/programmes',             'admin.programmes.manage'],
        ['GET',  '/admin/sessions',               'admin.sessions.manage'],
        ['GET',  '/admin/grades',                 'admin.grades.manage'],

        // Sub-slice 2: users.
        ['GET',  '/admin/users',                  'admin.users.manage'],
        ['GET',  '/admin/users/unlock',           'admin.user-unlocks.manage'],

        // Sub-slice 3: students.
        ['GET',  '/admin/students',               'admin.students.manage'],
        ['GET',  '/admin/students/import',        'admin.student-imports.manage'],
        ['GET',  '/admin/id-cards',               'admin.student-id-cards.manage'],
        ['GET',  '/admin/admission-centres',      'admin.admission-centres.manage'],

        // Sub-slice 4: academic ops.
        ['GET',  '/admin/courses',                'admin.courses.manage'],
        ['GET',  '/admin/course-assignments',     'admin.course-assignments.manage'],
        ['GET',  '/admin/course-registrations',   'admin.course-registrations.manage'],
        ['GET',  '/admin/timetable',              'admin.timetables.manage'],
        ['GET',  '/admin/results',                'admin.results.manage'],

        // Sub-slice 5: fees.
        ['GET',  '/admin/fees',                   'admin.fees.manage'],
        ['GET',  '/admin/payment-types',          'admin.payment-types.manage'],
        ['GET',  '/admin/admission/payment-flow', 'admin.payment-flows.manage'],

        // Sub-slice 6: facilities.
        ['GET',  '/admin/hostels',                'admin.hostels.manage'],
        ['GET',  '/admin/library/books',          'admin.libraries.manage'],

        // Sub-slice 7: misc.
        ['GET',  '/admin/staff',                  'admin.staff.manage'],
        ['GET',  '/admin/complaints',             'admin.complaints.manage'],
        ['GET',  '/admin/transcripts',            'admin.transcripts.manage'],
        ['GET',  '/admin/settings',               'admin.system-settings.manage'],
        ['GET',  '/admin/notifications',          'admin.notifications.manage'],
        ['GET',  '/admin/analytics',              'admin.analytics.manage'],
        ['GET',  '/admin/reports',                'admin.reports.manage'],
        ['GET',  '/admin/hospital-services',      'admin.hospital-services.manage'],
        ['GET',  '/admin/previous-results',       'admin.previous-results.manage'],
        ['GET',  '/admin/dashboard',              'admin.dashboard.manage'],
    ];

    private const catalogue = [
        'admin.schools.manage'              => 'admin',
        'admin.departments.manage'          => 'admin',
        'admin.programmes.manage'           => 'admin',
        'admin.sessions.manage'             => 'admin',
        'admin.grading.manage'              => 'admin',
        'admin.grades.manage'               => 'admin',
        'admin.users.manage'                => 'admin',
        'admin.user-roles.manage'           => 'admin',
        'admin.user-unlocks.manage'         => 'admin',
        'admin.students.manage'             => 'admin',
        'admin.student-imports.manage'      => 'admin',
        'admin.student-id-cards.manage'     => 'admin',
        'admin.admission-centres.manage'    => 'admin',
        'admin.courses.manage'              => 'admin',
        'admin.course-assignments.manage'   => 'admin',
        'admin.course-registrations.manage' => 'admin',
        'admin.exam-timetables.manage'      => 'admin',
        'admin.timetables.manage'           => 'admin',
        'admin.results.manage'              => 'admin',
        'admin.fees.manage'                 => 'admin',
        'admin.payment-types.manage'        => 'admin',
        'admin.payment-flows.manage'        => 'admin',
        'admin.hostels.manage'              => 'admin',
        'admin.libraries.manage'            => 'admin',
        'admin.staff.manage'                => 'admin',
        'admin.complaints.manage'           => 'admin',
        'admin.previous-results.manage'     => 'admin',
        'admin.transcripts.manage'          => 'admin',
        'admin.system-settings.manage'      => 'admin',
        'admin.notifications.manage'        => 'admin',
        'admin.analytics.manage'            => 'admin',
        'admin.reports.manage'              => 'admin',
        'admin.hospital-services.manage'    => 'admin',
        'admin.dashboard.manage'            => 'admin',
    ];

    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'ict_admin' => [
            'admin.schools.manage',
            'admin.departments.manage',
            'admin.programmes.manage',
            'admin.sessions.manage',
            'admin.grading.manage',
            'admin.grades.manage',
            'admin.users.manage',
            'admin.user-roles.manage',
            'admin.user-unlocks.manage',
            'admin.students.manage',
            'admin.student-imports.manage',
            'admin.student-id-cards.manage',
            'admin.admission-centres.manage',
            'admin.courses.manage',
            'admin.course-assignments.manage',
            'admin.course-registrations.manage',
            'admin.exam-timetables.manage',
            'admin.timetables.manage',
            'admin.results.manage',
            'admin.fees.manage',
            'admin.payment-types.manage',
            'admin.payment-flows.manage',
            'admin.hostels.manage',
            'admin.libraries.manage',
            'admin.staff.manage',
            'admin.complaints.manage',
            'admin.previous-results.manage',
            'admin.transcripts.manage',
            'admin.system-settings.manage',
            'admin.notifications.manage',
            'admin.analytics.manage',
            'admin.reports.manage',
            'admin.hospital-services.manage',
            'admin.dashboard.manage',
        ],
        'staff' => [
            'admin.schools.manage',
            'admin.departments.manage',
            'admin.programmes.manage',
            'admin.sessions.manage',
            'admin.grading.manage',
            'admin.grades.manage',
            'admin.users.manage',
            'admin.user-roles.manage',
            'admin.user-unlocks.manage',
            'admin.students.manage',
            'admin.student-imports.manage',
            'admin.student-id-cards.manage',
            'admin.admission-centres.manage',
            'admin.courses.manage',
            'admin.course-assignments.manage',
            'admin.course-registrations.manage',
            'admin.exam-timetables.manage',
            'admin.timetables.manage',
            'admin.results.manage',
            'admin.fees.manage',
            'admin.payment-types.manage',
            'admin.payment-flows.manage',
            'admin.hostels.manage',
            'admin.libraries.manage',
            'admin.staff.manage',
            'admin.complaints.manage',
            'admin.previous-results.manage',
            'admin.transcripts.manage',
            'admin.system-settings.manage',
            'admin.notifications.manage',
            'admin.analytics.manage',
            'admin.reports.manage',
            'admin.hospital-services.manage',
            'admin.dashboard.manage',
        ],

        // Bursar — wrong role, no admin slugs. The role: middleware
        // should 403 before the permission: middleware even runs.
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
        Schema::dropIfExists('settings');
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
        $resp = $this->get('/admin/dashboard');
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

        $resp = $this->actingAs($bursar)->get('/admin/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at the route role: middleware');
    }

    // ----------------------------------------------------------------
    // Route middleware — wildcard roles
    // ----------------------------------------------------------------

    /**
     * Super admin wildcard passes the permission: middleware on
     * the dashboard route.
     */
    public function test_super_admin_wildcard_passes_admin_route(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin should pass the permission:admin.dashboard.manage middleware');
    }

    /**
     * Admin wildcard passes the permission: middleware on a
     * destructive endpoint.
     */
    public function test_admin_wildcard_passes_destructive_admin_route(): void
    {
        $admin = $this->makeUser('admin');

        // POST on a destructive endpoint proves the wildcard covers
        // verbs beyond GET.
        $resp = $this->actingAs($admin)->post('/admin/results/release');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'admin should pass the permission:admin.results.manage middleware on POST');
    }

    // ----------------------------------------------------------------
    // Route middleware — ict_admin / staff (full grants mirror)
    // ----------------------------------------------------------------

    /**
     * ict_admin with the full admin grant set passes every
     * sampled admin route's permission: middleware. This is the
     * regression guard — without the explicit grants in
     * AdminPermissions, ict_admin would have been 403'd at the
     * permission: middleware even though the role: middleware
     * admits them.
     */
    public function test_ict_admin_with_full_grants_passes_sampled_routes(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        foreach (self::SAMPLE_ROUTES as [$method, $route, $slug]) {
            $resp = $this->actingAs($ictAdmin)->{$method}($route);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "ict_admin should pass permission:{$slug} on {$method} {$route}");
        }
    }

    /**
     * Staff with the full admin grant set passes every sampled
     * admin route's permission: middleware. Same regression guard
     * as ict_admin above.
     */
    public function test_staff_with_full_grants_passes_sampled_routes(): void
    {
        $staff = $this->makeUser('staff');

        foreach (self::SAMPLE_ROUTES as [$method, $route, $slug]) {
            $resp = $this->actingAs($staff)->{$method}($route);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "staff should pass permission:{$slug} on {$method} {$route}");
        }
    }

    // ----------------------------------------------------------------
    // Route middleware — slug-specific check (the new behaviour)
    // ----------------------------------------------------------------

    /**
     * PRIMARY REGRESSION GUARD for slice 8i-admin-routes:
     *
     * A user with role allowlist access but no admin.* slug is
     * 403'd at the route's permission: middleware (not the
     * controller). We use a `staff` role (in the role: allowlist)
     * with ZERO admin slugs — they pass role:, fail permission:
     * on every sampled admin route, and never hit the controller
     * body.
     *
     * The role: middleware layer admits them because they're staff.
     * The permission: middleware layer rejects them because they
     * lack every admin.* slug. Without slice 8i-admin-routes, the
     * request would reach the controller body where the trait gate
     * would catch it — but the request would already be in the
     * controller, wasting a stack frame. With the slice, the
     * permission: middleware stops it at route resolution.
     */
    public function test_role_allowlist_user_without_admin_slug_is_403_at_permission_middleware(): void
    {
        $noAdminSlug = $this->makeUserWithSubset('staff', [
            // Empty pivot — no admin.* slugs. The role: middleware
            // admits them (staff is in the allowlist), but the
            // permission: middleware 403s them at every admin route.
        ]);

        foreach (self::SAMPLE_ROUTES as [$method, $route, $slug]) {
            $resp = $this->actingAs($noAdminSlug)->{$method}($route);
            $this->assertSame(403, $resp->getStatusCode(),
                "staff with no admin slugs should be 403 at permission:{$slug} on {$method} {$route}");
        }
    }

    /**
     * Per-controller slug isolation — a staff user with one
     * admin slug passes that one route but is 403'd at all other
     * admin routes. Proves the per-controller slug shape works at
     * the route level too, not just the controller.
     */
    public function test_per_controller_slug_isolation_at_route_level(): void
    {
        $user = $this->makeUserWithSubset('staff', [
            'admin.schools.manage',
        ]);

        // Has admin.schools.manage — passes /admin/schools.
        $resp = $this->actingAs($user)->get('/admin/schools');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'user with admin.schools.manage should pass /admin/schools');

        // Does NOT have admin.departments.manage — 403 at the
        // route middleware (proves per-slug isolation, not
        // "any admin role").
        $resp = $this->actingAs($user)->get('/admin/departments');
        $this->assertSame(403, $resp->getStatusCode(),
            'user with admin.schools.manage but no admin.departments.manage should be 403 at /admin/departments');

        $resp = $this->actingAs($user)->get('/admin/users');
        $this->assertSame(403, $resp->getStatusCode(),
            'user with admin.schools.manage but no admin.users.manage should be 403 at /admin/users');
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

        // Library routes are wrapped in `library.access` middleware
        // (looks up `settings.library_access_code`). Without this
        // table the middleware 500s on a missing-table error and we
        // can't reach the permission: middleware that this test
        // exercises. Empty table is fine — the absence of an access
        // code skips the prompt and grants access.
        Schema::create('settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
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
     * Mirrors the helper in MaintenanceRouteMiddlewareTest.
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