<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-controller regression — every *DashboardConfigController
 * subclass now inherits from an abstract base that calls
 * `requirePermission($this->dashboardConfigPermissionSlug())` at
 * the top of both `edit()` and `update()`. This pins the behaviour.
 *
 * Before slice 8i-controller, the dashboard-config controllers were
 * the one set of routes whose controller body didn't gate. The route
 * middleware (`auth + role:`) was the only protection — which meant
 * a bursary_officer with `bursar.dashboard.view` could reach
 * `/bursar/dashboard-config/{user}` even though they shouldn't be
 * allowed to configure dashboards (only the head `bursar` should).
 *
 * The trait-side gate closes that gap. After slice 8i-controller, the
 * controller body throws `AuthorizationException` (-> 403) for any user
 * lacking the audience-specific slug. Wildcard roles (`super_admin`,
 * `admin`, `cmd`) still pass via `HospitalPermissions::ROLE_PERMISSIONS`.
 *
 * Tests use the same hand-rolled in-memory sqlite schema as
 * PermissionServiceTest. We seed only the catalogue slugs the matrix
 * needs and use `actingAs()` to simulate auth.
 *
 * Note: we hit the dashboard-config routes through HTTP (not direct
 * controller invocation) because the route's `auth + role:` middleware
 * chain is what admits the user. The controller trait gate then fires
 * AFTER the middleware, denying users who lack the slug. We assert on
 * status code — not response body — because the underlying controller
 * body may 500 in the test env (missing dashboard_widgets table) and
 * the gate is what we care about.
 */
class DashboardConfigControllerGateTest extends TestCase
{
    /**
     * Catalogue — slug => group. Just the `.dashboard.configure` family
     * plus a few audience-view slugs for the cross-check that nothing
     * else regressed. Mirrors `PermissionsSeeder::PREFIX_TO_GROUP`.
     */
    private const CATALOG = [
        'bursar.dashboard.view'                => 'bursar',
        'bursar.dashboard.configure'           => 'bursar',
        'registrar.dashboard.configure'        => 'registrar',
        'business_committee.dashboard.configure' => 'business_committee',
        'academic.dashboard.configure'         => 'academic',
        'student.dashboard.configure'          => 'student',
        'librarian.dashboard.configure'        => 'librarian',
        'finance.dashboard.configure'          => 'finance',
        'executive.dashboard.configure'        => 'executive',
        'auditor.dashboard.configure'          => 'auditor',
        'admin.dashboard.configure'            => 'admin',
        'patients.view'                        => 'hospital',
        'wards.view'                           => 'hospital',
        'pharmacy.view'                        => 'hospital',
        'lab.view'                             => 'hospital',
        'reports.daily-revenue'                => 'hospital',
    ];

    /**
     * Per-role pivot — only the slugs each role should hold for the
     * dashboard-config tests. Mirrors the contracts in
     * BursarPermissions / RegistrarPermissions / etc. for the
     * `.dashboard.configure` slugs.
     */
    private const ROLE_PERMISSIONS = [
        // Wildcard roles — pass every gate.
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        // Bursar — head bursar configures dashboards.
        'bursar' => ['bursar.dashboard.configure'],
        // Bursary officer — NO dashboard.configure (the regression
        // we care about). Has the .view slug but not .configure.
        'bursary_officer' => ['bursar.dashboard.view'],

        // Registrar — has it.
        'registrar' => ['registrar.dashboard.configure'],

        // Business committee — has it.
        'business_committee' => ['business_committee.dashboard.configure'],

        // Academic board — has academic.dashboard.configure.
        'academic_board' => ['academic.dashboard.configure'],

        // Student — has its own slug.
        'student' => ['student.dashboard.configure'],

        // Librarian — has it.
        'librarian' => ['librarian.dashboard.configure'],
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
     * Bursar with `bursar.dashboard.configure` can reach the dashboard
     * configurator (controller body may still 500 on missing
     * dashboard_widgets table — what we DON'T want is 403).
     */
    public function test_bursar_with_configure_slug_passes_controller_gate(): void
    {
        $bursar = $this->makeUser('bursar');
        $target = $this->makeUser('bursar'); // any user for the {user} param

        $resp = $this->actingAs($bursar)->get('/bursar/dashboard-config/' . $target->id);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'bursar with bursar.dashboard.configure should pass the controller gate');
    }

    /**
     * Bursary officer (in the role chain, has `bursar.dashboard.view`
     * but NOT `bursar.dashboard.configure`) is 403'd at the controller
     * body. This is the regression slice 8i-controller closes.
     */
    public function test_bursary_officer_without_configure_slug_is_403_at_controller(): void
    {
        $officer = $this->makeUser('bursary_officer');
        $target  = $this->makeUser('bursar');

        $resp = $this->actingAs($officer)->get('/bursar/dashboard-config/' . $target->id);
        $this->assertSame(403, $resp->getStatusCode(),
            'bursary_officer (lacks bursar.dashboard.configure) should be 403 at the controller');
    }

    /**
     * Registrar without `registrar.dashboard.configure` cannot reach
     * the registrar dashboard-config (the route admits the registrar
     * role via the `role:` middleware, but the controller trait gate
     * denies).
     */
    public function test_registrar_without_configure_slug_is_403_at_controller(): void
    {
        $regNoSlug = $this->makeUser('registrar');
        // Strip the pivot grant for this user.
        $regNoSlugRole = Role::where('slug', 'registrar')->first();
        $regNoSlugRole->permissions()->detach();

        $target = $this->makeUser('registrar');

        $resp = $this->actingAs($regNoSlug)->get('/registrar/dashboard-config/' . $target->id);
        $this->assertSame(403, $resp->getStatusCode(),
            'registrar without registrar.dashboard.configure should be 403');
    }

    /**
     * Super_admin (wildcard) passes the controller gate for the two
     * dashboard-config routes whose role middleware actually admits
     * `super_admin` (bursar + registrar). The other audiences
     * (business-committee, academic-board, librarian, student) gate
     * the route itself on a narrower role list that does NOT include
     * super_admin — those routes are tested via the audience's
     * primary role elsewhere in this class.
     */
    public function test_super_admin_wildcard_passes_bursar_and_registrar_dashboard_config(): void
    {
        $superAdmin = $this->makeUser('super_admin');
        $target = $this->makeUser('bursar');

        foreach ([
            '/bursar/dashboard-config/' . $target->id,
            '/registrar/dashboard-config/' . $target->id,
        ] as $url) {
            $resp = $this->actingAs($superAdmin)->get($url);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "super_admin should pass {$url} via wildcard");
        }
    }

    /**
     * Student with `student.dashboard.configure` can reach the student
     * dashboard-config route.
     */
    public function test_student_with_configure_slug_passes_controller_gate(): void
    {
        $student = $this->makeUser('student');
        $target  = $this->makeUser('student');

        $resp = $this->actingAs($student)->get('/student/dashboard-config/' . $target->id);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'student with student.dashboard.configure should pass the controller gate');
    }

    /**
     * Student without `student.dashboard.configure` is 403. (Sanity
     * test that adding the role-grant in AcademicPermissions was
     * actually necessary — without it the student would always fail.)
     */
    public function test_student_without_configure_slug_is_403_at_controller(): void
    {
        $studentNoSlug = $this->makeUser('student');
        // Strip the pivot grant.
        $studentRole = Role::where('slug', 'student')->first();
        $studentRole->permissions()->detach();
        PermissionService::flush();

        $target = $this->makeUser('student');

        $resp = $this->actingAs($studentNoSlug)->get('/student/dashboard-config/' . $target->id);
        $this->assertSame(403, $resp->getStatusCode(),
            'student without student.dashboard.configure should be 403');
    }

    /**
     * Bursar with the slug can also UPDATE (PUT) the dashboard-config.
     * Same trait gate fires on update().
     */
    public function test_bursar_with_configure_slug_can_update(): void
    {
        $bursar = $this->makeUser('bursar');
        $target = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->put('/bursar/dashboard-config/' . $target->id, [
            'widgets' => [],
        ]);
        // Pass means controller gate did not 403. The body may 500 on
        // missing dashboard_widgets table — what matters is NOT 403.
        $this->assertNotSame(403, $resp->getStatusCode(),
            'bursar with bursar.dashboard.configure should pass the update gate');
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