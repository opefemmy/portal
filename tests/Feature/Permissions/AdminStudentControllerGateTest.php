<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-admin-students (sub-slice 3 of 8i-admin) regression —
 * the 4 student-management Admin\*.php controllers
 * (StudentController, StudentImportController,
 * StudentIdCardController, AdmissionCentreController) now call
 * `requirePermission()` at the top of every public method.
 *
 * 15+3+4+7 = 29 public methods = 29 gates. 4 unique per-controller
 * slugs:
 *
 *   admin.students.manage            (StudentController)
 *   admin.student-imports.manage     (StudentImportController)
 *   admin.student-id-cards.manage    (StudentIdCardController)
 *   admin.admission-centres.manage   (AdmissionCentreController)
 *
 * No dual-use routes in this group — every method is reachable
 * only via auth-admin routes, so all 29 methods are gated. This
 * is the simpler pattern (mirrors sub-slice 1, not sub-slice 2).
 *
 * Before this slice, the student-management routes used
 * `auth + role:super_admin,admin,ict_admin,staff` middleware, so
 * any user in that role-set reached every student-management
 * endpoint with no slug-level check. The trait gate added in this
 * slice closes that.
 */
class AdminStudentControllerGateTest extends TestCase
{
    private const catalogue = [
        // Sub-slice 3 (this slice).
        'admin.students.manage'         => 'admin',
        'admin.student-imports.manage'  => 'admin',
        'admin.student-id-cards.manage' => 'admin',
        'admin.admission-centres.manage' => 'admin',
        // Sub-slice 1 + 2 (needed because ict_admin / staff roles
        // mirror the full admin allowlist).
        'admin.schools.manage'          => 'admin',
        'admin.departments.manage'      => 'admin',
        'admin.programmes.manage'       => 'admin',
        'admin.sessions.manage'         => 'admin',
        'admin.grading.manage'          => 'admin',
        'admin.grades.manage'           => 'admin',
        'admin.users.manage'            => 'admin',
        'admin.user-roles.manage'       => 'admin',
        'admin.user-unlocks.manage'     => 'admin',
    ];

    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'ict_admin' => [
            // Sub-slice 3 (this slice).
            'admin.students.manage',
            'admin.student-imports.manage',
            'admin.student-id-cards.manage',
            'admin.admission-centres.manage',
        ],
        'staff' => [
            // Sub-slice 3 (this slice).
            'admin.students.manage',
            'admin.student-imports.manage',
            'admin.student-id-cards.manage',
            'admin.admission-centres.manage',
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

    public function test_guest_is_blocked_at_auth_middleware_students_index(): void
    {
        $resp = $this->get('/admin/students');
        $status = $resp->getStatusCode();
        $this->assertContains($status, [302, 403],
            "guest should be blocked at auth middleware (302 or 403), got {$status}");
        $this->assertNotSame(200, $status);
    }

    // ----------------------------------------------------------------
    // Route middleware — wrong role
    // ----------------------------------------------------------------

    public function test_bursar_is_403_at_role_middleware_students_index(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/admin/students');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at the route role: middleware');
    }

    // ----------------------------------------------------------------
    // Controller gate — wildcard roles
    // ----------------------------------------------------------------

    public function test_super_admin_wildcard_passes_students_index(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/students');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the StudentController gate');
    }

    public function test_super_admin_wildcard_passes_student_imports(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/students/import');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the StudentImportController gate');
    }

    public function test_super_admin_wildcard_passes_student_id_cards(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/id-cards');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the StudentIdCardController gate');
    }

    public function test_super_admin_wildcard_passes_admission_centres(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/admission-centres');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the AdmissionCentreController gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — ict_admin / staff (mirroring the route allowlist)
    // ----------------------------------------------------------------

    public function test_ict_admin_with_full_student_grants_passes_students_index(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->get('/admin/students');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.students.manage should pass the StudentController gate');
    }

    public function test_staff_with_full_student_grants_passes_students_index(): void
    {
        $staff = $this->makeUser('staff');

        $resp = $this->actingAs($staff)->get('/admin/students');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'staff with admin.students.manage should pass the StudentController gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — per-controller slug isolation
    // ----------------------------------------------------------------

    /**
     * PRIMARY regression slice 8i-admin-students closes: an
     * ict_admin with ONLY admin.students.manage (no other student
     * slug) is 403'd at a controller that requires a DIFFERENT
     * slug. Without this slice, the route's role: middleware
     * admitted ict_admin to every student-management endpoint
     * regardless of which slug the controller needed.
     */
    public function test_ict_admin_without_student_imports_slug_is_403_at_import(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.students.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/students/import');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.student-imports.manage should be 403 at /admin/students/import');
    }

    public function test_ict_admin_without_student_id_cards_slug_is_403_at_id_cards(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.students.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/id-cards');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.student-id-cards.manage should be 403 at /admin/id-cards');
    }

    public function test_ict_admin_without_admission_centres_slug_is_403_at_centres(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.students.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/admission-centres');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.admission-centres.manage should be 403 at /admin/admission-centres');
    }

    public function test_ict_admin_without_students_slug_is_403_at_students_index(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.admission-centres.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/students');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.students.manage should be 403 at /admin/students');
    }

    /**
     * ict_admin with all 4 student-management slugs reaches every
     * controller. Proves the wiring is symmetric.
     */
    public function test_ict_admin_with_all_student_slugs_passes_all_controllers(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $urls = [
            '/admin/students',
            '/admin/students/import',
            '/admin/id-cards',
            '/admin/admission-centres',
        ];
        foreach ($urls as $url) {
            $resp = $this->actingAs($ictAdmin)->get($url);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "ict_admin with all slugs should pass the controller gate at {$url}");
        }
    }

    /**
     * ict_admin with the full student slug set can POST (destructive
     * verb) on the StudentController resource. Proves the
     * per-resource slug covers all CRUD verbs, not just GET.
     */
    public function test_ict_admin_with_students_slug_passes_destructive_verbs(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        // POST /admin/students (create). Body 500s in test env
        // (missing students table) but the gate is what we care about.
        $resp = $this->actingAs($ictAdmin)->post('/admin/students', [
            'name'              => 'Test Student',
            'email'             => 's_' . uniqid('', true) . '@test.local',
            'matric_number'     => 'TEST/' . uniqid('', true),
            'school_id'         => 1,
            'department_id'     => 1,
            'programme_id'      => 1,
            'session_id'        => 1,
            'level'             => 1,
        ]);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.students.manage should pass the controller gate on POST');
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