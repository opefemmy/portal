<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-admin-facilities (sub-slice 6 of 8i-admin) regression —
 * the 2 facilities Admin\*.php controllers (HostelController,
 * LibraryController) now call `requirePermission()` at the top
 * of every public method.
 *
 * 15+7 = 22 public methods = 22 gates. 2 unique per-controller
 * slugs:
 *
 *   admin.hostels.manage   (HostelController — 15 methods)
 *   admin.libraries.manage (LibraryController — 7 methods)
 *
 * No dual-use routes in this group — every method is reachable
 * only via auth-admin routes (the library routes are further
 * gated by `library.access` middleware that checks for the
 * access code, but the controller still runs `requirePermission`
 * for defence-in-depth). All 22 methods are gated.
 *
 * These two controllers together comprise the Facilities Admin
 * surface — they manage the hostel room/bed inventory and the
 * library book/loan catalogue. Before this slice, every
 * facilities endpoint was reachable by any user in the route's
 * `role:super_admin,admin,ict_admin,staff` allowlist with no
 * slug-level check. The trait gate added in this slice closes
 * that gap.
 */
class AdminFacilitiesControllerGateTest extends TestCase
{
    private const catalogue = [
        // Sub-slice 6 (this slice).
        'admin.hostels.manage'      => 'admin',
        'admin.libraries.manage'    => 'admin',
        // Sub-slices 1-5 (needed because ict_admin / staff roles
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
        'admin.fees.manage'               => 'admin',
        'admin.payment-types.manage'      => 'admin',
        'admin.payment-flows.manage'      => 'admin',
    ];

    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'ict_admin' => [
            // Sub-slice 6 (this slice).
            'admin.hostels.manage',
            'admin.libraries.manage',
        ],
        'staff' => [
            // Sub-slice 6 (this slice).
            'admin.hostels.manage',
            'admin.libraries.manage',
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
        Schema::dropIfExists('settings');
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

    public function test_guest_is_blocked_at_auth_middleware_hostels_index(): void
    {
        $resp = $this->get('/admin/hostels');
        $status = $resp->getStatusCode();
        $this->assertContains($status, [302, 403],
            "guest should be blocked at auth middleware (302 or 403), got {$status}");
        $this->assertNotSame(200, $status);
    }

    public function test_guest_is_blocked_at_auth_middleware_library_books(): void
    {
        $resp = $this->get('/admin/library/books');
        $status = $resp->getStatusCode();
        $this->assertContains($status, [302, 403],
            "guest should be blocked at auth middleware (302 or 403), got {$status}");
        $this->assertNotSame(200, $status);
    }

    // ----------------------------------------------------------------
    // Route middleware — wrong role
    // ----------------------------------------------------------------

    public function test_bursar_is_403_at_role_middleware_hostels_index(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/admin/hostels');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at the route role: middleware');
    }

    public function test_bursar_is_403_at_role_middleware_library_books(): void
    {
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($bursar)->get('/admin/library/books');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at the route role: middleware');
    }

    // ----------------------------------------------------------------
    // Controller gate — wildcard roles
    // ----------------------------------------------------------------

    public function test_super_admin_wildcard_passes_hostels_index(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/hostels');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the HostelController gate');
    }

    public function test_super_admin_wildcard_passes_library_books(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/library/books');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the LibraryController gate');
    }

    public function test_super_admin_wildcard_passes_hostel_allocations(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        // /admin/hostels/allocations is shadowed by the resource show
        // route (/hostels/{hostel}) which 500s binding. Use the create
        // route instead — it has no model binding and exercises the
        // HostelController::createAllocation gate.
        $resp = $this->actingAs($superAdmin)->get('/admin/hostels/allocations/create');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the HostelController createAllocation gate');
    }

    public function test_super_admin_wildcard_passes_library_loans(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/library/loans');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the LibraryController loans gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — ict_admin / staff (mirroring the route allowlist)
    // ----------------------------------------------------------------

    public function test_ict_admin_with_full_grants_passes_hostels_index(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->get('/admin/hostels');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.hostels.manage should pass the HostelController gate');
    }

    public function test_staff_with_full_grants_passes_library_books(): void
    {
        $staff = $this->makeUser('staff');

        $resp = $this->actingAs($staff)->get('/admin/library/books');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'staff with admin.libraries.manage should pass the LibraryController gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — per-controller slug isolation
    // ----------------------------------------------------------------

    /**
     * PRIMARY regression slice 8i-admin-facilities closes: an
     * ict_admin with ONLY admin.hostels.manage (no library slug)
     * is 403'd at a controller that requires a DIFFERENT slug.
     * Without this slice, the route's role: middleware admitted
     * ict_admin to every facilities endpoint regardless of which
     * slug the controller needed.
     */
    public function test_ict_admin_without_libraries_slug_is_403_at_library_books(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.hostels.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/library/books');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.libraries.manage should be 403 at /admin/library/books');
    }

    public function test_ict_admin_without_hostels_slug_is_403_at_hostels_index(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.libraries.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/hostels');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.hostels.manage should be 403 at /admin/hostels');
    }

    public function test_staff_without_hostels_slug_is_403_at_hostel_allocations(): void
    {
        $user = $this->makeUserWithSubset('staff', [
            'admin.libraries.manage',
        ]);

        // /admin/hostels/allocations is shadowed by the resource show
        // route (/hostels/{hostel}) which 500s in SubstituteBindings
        // when trying to resolve "allocations" as a Hostel id. Use the
        // createAllocation route instead — same controller, same slug,
        // no {hostel} model binding.
        $resp = $this->actingAs($user)->get('/admin/hostels/allocations/create');
        $this->assertSame(403, $resp->getStatusCode(),
            'staff without admin.hostels.manage should be 403 at HostelController::createAllocation');
    }

    /**
     * ict_admin with both facilities slugs reaches every controller.
     * Proves the wiring is symmetric.
     */
    public function test_ict_admin_with_all_facilities_slugs_passes_all_controllers(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        // URLs deliberately chosen to avoid the two pre-existing 500
        // triggers (library.access middleware on missing settings
        // table; route-model-binding shadow on /hostels/allocations):
        //
        //   /admin/hostels                  — HostelController::index (resource GET)
        //   /admin/hostels/allocations/create — HostelController::createAllocation
        //   /admin/library/books            — LibraryController::books
        //   /admin/library/loans            — LibraryController::loans
        //
        // The `settings` table is added in buildSchema() so the
        // library.access middleware can read Setting::get() without
        // 500-ing. The /hostels/allocations shadow issue means we
        // use /hostels/allocations/create instead.
        $urls = [
            '/admin/hostels',
            '/admin/hostels/allocations/create',
            '/admin/library/books',
            '/admin/library/loans',
        ];
        foreach ($urls as $url) {
            $resp = $this->actingAs($ictAdmin)->get($url);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "ict_admin with all slugs should pass the controller gate at {$url}");
        }
    }

    /**
     * ict_admin with admin.hostels.manage can POST the destructive
     * room-creation endpoint. Proves the per-resource slug covers
     * all verbs, not just GET.
     */
    public function test_ict_admin_with_hostels_slug_passes_destructive_verbs(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        // POST /admin/hostels/1/rooms (storeRoom). Body 500s in test
        // env (missing tables) but the gate is what we care about.
        $resp = $this->actingAs($ictAdmin)->post('/admin/hostels/1/rooms', [
            'room_number' => '101',
            'floor'       => 1,
            'capacity'    => 4,
            'type'        => 'standard',
        ]);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.hostels.manage should pass the controller gate on POST');
    }

    /**
     * ict_admin with admin.libraries.manage can POST the destructive
     * issue-book endpoint. Independent regression guard for
     * non-GET verbs on LibraryController.
     */
    public function test_ict_admin_with_libraries_slug_passes_issue_verb(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->post('/admin/library/loans/issue', [
            'book_id'    => 1,
            'student_id' => 1,
            'due_date'   => now()->addDays(7)->toDateString(),
        ]);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.libraries.manage should pass the issue route');
    }

    /**
     * ict_admin with admin.hostels.manage can hit the AJAX JSON
     * endpoint `getRooms`. The library access middleware would
     * normally 403 guests, but auth-admin ict_admin passes through
     * the route and into the controller, where the gate is the
     * regression target.
     */
    public function test_ict_admin_with_hostels_slug_passes_ajax_get_rooms(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->get('/admin/hostels/rooms/1/rooms');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.hostels.manage should pass the controller gate on AJAX getRooms');
    }

    /**
     * staff without admin.hostels.manage is 403 at an AJAX
     * endpoint too. Proves the gate fires regardless of whether
     * the response type is HTML view or JSON.
     */
    public function test_staff_without_hostels_slug_is_403_at_ajax_get_rooms(): void
    {
        $user = $this->makeUserWithSubset('staff', [
            'admin.libraries.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/hostels/rooms/1/rooms');
        $this->assertSame(403, $resp->getStatusCode(),
            'staff without admin.hostels.manage should be 403 at AJAX getRooms endpoint');
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

        // settings table — the `library.access` middleware queries it
        // via Setting::get('library_access_code'). Without this table
        // the middleware 500s on every library route before the
        // controller gate can run. Adding the empty table lets the
        // middleware pass through and the gate is reachable.
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
