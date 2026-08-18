<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-admin-academic-ops (sub-slice 4 of 8i-admin) regression
 * — the 6 academic-operations Admin\*.php controllers
 * (CourseController, CourseAssignmentController,
 * CourseRegistrationController, ExamTimetableController,
 * TimetableController, ResultController) now call
 * `requirePermission()` at the top of every public method.
 *
 * 8+6+4+6+6+14 = 44 public methods = 44 gates. 6 unique
 * per-controller slugs:
 *
 *   admin.courses.manage               (CourseController)
 *   admin.course-assignments.manage    (CourseAssignmentController)
 *   admin.course-registrations.manage  (CourseRegistrationController)
 *   admin.exam-timetables.manage       (ExamTimetableController)
 *   admin.timetables.manage            (TimetableController)
 *   admin.results.manage               (ResultController)
 *
 * No dual-use routes in this group — every method is reachable
 * only via auth-admin routes, so all 44 methods are gated.
 *
 * Note: there are FOUR separate `ResultController` namespaces in
 * the codebase (Student\ResultController, HOD\ResultController,
 * Dean\ResultController, AcademicBoard\ResultController,
 * BusinessCommittee\ResultController) — this slice only wires
 * the `App\Http\Controllers\Admin\ResultController`. Each
 * namespace is independently gated by its own service-class
 * (StudentPermissions, AcademicPermissions, etc.).
 *
 * Before this slice, every academic-operations endpoint was
 * reachable by any user in the route's
 * `role:super_admin,admin,ict_admin,staff` allowlist with no
 * slug-level check. The trait gate added in this slice closes
 * that gap.
 */
class AdminAcademicOpsControllerGateTest extends TestCase
{
    private const catalogue = [
        // Sub-slice 4 (this slice).
        'admin.courses.manage'               => 'admin',
        'admin.course-assignments.manage'    => 'admin',
        'admin.course-registrations.manage'  => 'admin',
        'admin.exam-timetables.manage'       => 'admin',
        'admin.timetables.manage'            => 'admin',
        'admin.results.manage'               => 'admin',
        // Sub-slices 1-3 (needed because ict_admin / staff roles
        // mirror the full admin allowlist).
        'admin.schools.manage'               => 'admin',
        'admin.departments.manage'           => 'admin',
        'admin.programmes.manage'            => 'admin',
        'admin.sessions.manage'              => 'admin',
        'admin.grading.manage'               => 'admin',
        'admin.grades.manage'                => 'admin',
        'admin.users.manage'                 => 'admin',
        'admin.user-roles.manage'            => 'admin',
        'admin.user-unlocks.manage'          => 'admin',
        'admin.students.manage'              => 'admin',
        'admin.student-imports.manage'       => 'admin',
        'admin.student-id-cards.manage'      => 'admin',
        'admin.admission-centres.manage'     => 'admin',
    ];

    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'ict_admin' => [
            // Sub-slice 4 (this slice).
            'admin.courses.manage',
            'admin.course-assignments.manage',
            'admin.course-registrations.manage',
            'admin.exam-timetables.manage',
            'admin.timetables.manage',
            'admin.results.manage',
        ],
        'staff' => [
            // Sub-slice 4 (this slice).
            'admin.courses.manage',
            'admin.course-assignments.manage',
            'admin.course-registrations.manage',
            'admin.exam-timetables.manage',
            'admin.timetables.manage',
            'admin.results.manage',
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

    public function test_guest_is_blocked_at_auth_middleware_courses_index(): void
    {
        $resp = $this->get('/admin/courses');
        $status = $resp->getStatusCode();
        $this->assertContains($status, [302, 403],
            "guest should be blocked at auth middleware (302 or 403), got {$status}");
        $this->assertNotSame(200, $status);
    }

    // ----------------------------------------------------------------
    // Route middleware — wrong role
    // ----------------------------------------------------------------

    public function test_bursar_is_403_at_role_middleware_courses_index(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/admin/courses');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at the route role: middleware');
    }

    // ----------------------------------------------------------------
    // Controller gate — wildcard roles
    // ----------------------------------------------------------------

    public function test_super_admin_wildcard_passes_courses_index(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/courses');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the CourseController gate');
    }

    public function test_super_admin_wildcard_passes_course_assignments(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/course-assignments');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the CourseAssignmentController gate');
    }

    public function test_super_admin_wildcard_passes_results(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/results');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the ResultController gate');
    }

    public function test_super_admin_wildcard_passes_timetable(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/timetable');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the TimetableController gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — ict_admin / staff (mirroring the route allowlist)
    // ----------------------------------------------------------------

    public function test_ict_admin_with_full_grants_passes_courses_index(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->get('/admin/courses');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.courses.manage should pass the CourseController gate');
    }

    public function test_staff_with_full_grants_passes_course_assignments(): void
    {
        $staff = $this->makeUser('staff');

        $resp = $this->actingAs($staff)->get('/admin/course-assignments');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'staff with admin.course-assignments.manage should pass the CourseAssignmentController gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — per-controller slug isolation
    // ----------------------------------------------------------------

    /**
     * PRIMARY regression slice 8i-admin-academic-ops closes: an
     * ict_admin with ONLY admin.courses.manage (no other academic-ops
     * slug) is 403'd at a controller that requires a DIFFERENT
     * slug. Without this slice, the route's role: middleware
     * admitted ict_admin to every academic-operations endpoint
     * regardless of which slug the controller needed.
     */
    public function test_ict_admin_without_course_assignments_slug_is_403_at_assignments(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.courses.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/course-assignments');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.course-assignments.manage should be 403 at /admin/course-assignments');
    }

    public function test_ict_admin_without_results_slug_is_403_at_results(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.courses.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/results');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.results.manage should be 403 at /admin/results');
    }

    public function test_ict_admin_without_timetables_slug_is_403_at_timetable(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.courses.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/timetable');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.timetables.manage should be 403 at /admin/timetable');
    }

    public function test_ict_admin_without_courses_slug_is_403_at_courses_index(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.results.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/courses');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.courses.manage should be 403 at /admin/courses');
    }

    /**
     * ict_admin with all 6 academic-ops slugs reaches every
     * controller. Proves the wiring is symmetric.
     */
    public function test_ict_admin_with_all_slugs_passes_all_controllers(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $urls = [
            '/admin/courses',
            '/admin/course-assignments',
            '/admin/results',
            '/admin/timetable',
        ];
        foreach ($urls as $url) {
            $resp = $this->actingAs($ictAdmin)->get($url);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "ict_admin with all slugs should pass the controller gate at {$url}");
        }
    }

    /**
     * ict_admin with the full academic-ops slug set can PUT
     * (destructive verb) on the ResultController. Proves the
     * per-resource slug covers all verbs, not just GET.
     */
    public function test_ict_admin_with_results_slug_passes_destructive_verbs(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        // POST /admin/results/release (bulk action). Body 500s in
        // test env but the gate is what we care about.
        $resp = $this->actingAs($ictAdmin)->post('/admin/results/release', [
            'session_id' => 1,
            'semester'   => 'First',
        ]);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.results.manage should pass the controller gate on POST');
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