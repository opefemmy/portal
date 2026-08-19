<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8i-admin-misc (sub-slice 7 of 8i-admin) regression —
 * the 10 misc Admin\*.php controllers now call
 * `requirePermission()` at the top of every public method.
 *
 * 4+6+1+5+5+8+3+12+2+1 = 47 public methods = 47 gates.
 * 10 unique per-controller slugs:
 *
 *   admin.staff.manage           (StaffController          — 8 methods)
 *   admin.complaints.manage      (ComplaintController     — 4 methods)
 *   admin.previous-results.manage (PreviousResultController — 6 methods)
 *   admin.transcripts.manage     (TranscriptController    — 3 methods)
 *   admin.system-settings.manage (SystemSettingController — 12 methods)
 *   admin.notifications.manage   (NotificationController  — 2 methods)
 *   admin.analytics.manage       (AnalyticsController     — 1 method)
 *   admin.reports.manage         (ReportController        — 5 methods)
 *   admin.hospital-services.manage (HospitalServiceController — 5 methods)
 *   admin.dashboard.manage       (DashboardController     — 1 method)
 *
 * No dual-use routes in this group — every method is reachable
 * only via auth-admin routes, so all 47 methods are gated.
 *
 * These 10 controllers together comprise the catch-all Admin
 * surface — staff directory + complaints + previous-result
 * importer + transcripts + system-settings (the heaviest one —
 * 12 methods covering portal toggles + payment-gateway config +
 * branding + asset download/delete) + notifications + analytics
 * + reports + hospital-services (admin-side catalogue of
 * hospital service types) + the /admin/dashboard landing page.
 */
class AdminMiscControllerGateTest extends TestCase
{
    private const catalogue = [
        // Sub-slice 7 (this slice).
        'admin.staff.manage'              => 'admin',
        'admin.complaints.manage'         => 'admin',
        'admin.previous-results.manage'   => 'admin',
        'admin.transcripts.manage'        => 'admin',
        'admin.system-settings.manage'    => 'admin',
        'admin.notifications.manage'      => 'admin',
        'admin.analytics.manage'          => 'admin',
        'admin.reports.manage'            => 'admin',
        'admin.hospital-services.manage'  => 'admin',
        'admin.dashboard.manage'          => 'admin',
        // Sub-slices 1-6 (needed because ict_admin / staff roles
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
        'admin.hostels.manage'            => 'admin',
        'admin.libraries.manage'          => 'admin',
    ];

    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'ict_admin' => [
            // Sub-slice 7 (this slice).
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
            // Sub-slice 7 (this slice).
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

    public function test_guest_is_blocked_at_auth_middleware(): void
    {
        foreach (['/admin/dashboard', '/admin/staff', '/admin/reports'] as $url) {
            $resp = $this->get($url);
            $status = $resp->getStatusCode();
            $this->assertContains($status, [302, 403],
                "guest should be blocked at auth middleware (302 or 403) at {$url}, got {$status}");
            $this->assertNotSame(200, $status);
        }
    }

    // ----------------------------------------------------------------
    // Route middleware — wrong role
    // ----------------------------------------------------------------

    public function test_bursar_is_403_at_role_middleware(): void
    {
        $bursar = $this->makeUser('bursar');

        foreach (['/admin/dashboard', '/admin/staff', '/admin/reports'] as $url) {
            $resp = $this->actingAs($bursar)->get($url);
            $this->assertSame(403, $resp->getStatusCode(),
                "bursar should be 403 at the route role: middleware for {$url}");
        }
    }

    // ----------------------------------------------------------------
    // Controller gate — wildcard roles (sample of each controller)
    // ----------------------------------------------------------------

    public function test_super_admin_wildcard_passes_staff(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/staff');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the StaffController gate');
    }

    public function test_super_admin_wildcard_passes_complaints(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/complaints');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the ComplaintController gate');
    }

    public function test_super_admin_wildcard_passes_previous_results(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/previous-results');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the PreviousResultController gate');
    }

    public function test_super_admin_wildcard_passes_transcripts(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/transcripts');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the TranscriptController gate');
    }

    public function test_super_admin_wildcard_passes_system_settings(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/settings');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the SystemSettingController gate');
    }

    public function test_super_admin_wildcard_passes_notifications(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/notifications');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the NotificationController gate');
    }

    public function test_super_admin_wildcard_passes_analytics(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/analytics');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the AnalyticsController gate');
    }

    public function test_super_admin_wildcard_passes_reports(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/reports');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the ReportController gate');
    }

    public function test_super_admin_wildcard_passes_hospital_services(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/hospital-services');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the HospitalServiceController gate');
    }

    public function test_super_admin_wildcard_passes_admin_dashboard(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $resp = $this->actingAs($superAdmin)->get('/admin/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'super_admin with wildcard should pass the DashboardController gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — ict_admin / staff
    // ----------------------------------------------------------------

    public function test_ict_admin_with_full_grants_passes_staff(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->get('/admin/staff');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.staff.manage should pass the StaffController gate');
    }

    public function test_staff_with_full_grants_passes_notifications(): void
    {
        $staff = $this->makeUser('staff');

        $resp = $this->actingAs($staff)->get('/admin/notifications');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'staff with admin.notifications.manage should pass the NotificationController gate');
    }

    // ----------------------------------------------------------------
    // Controller gate — per-controller slug isolation
    // ----------------------------------------------------------------

    /**
     * PRIMARY regression slice 8i-admin-misc closes: an ict_admin
     * with ONLY one misc slug is 403'd at a controller that
     * requires a DIFFERENT slug. Without this slice, the route's
     * role: middleware admitted ict_admin to every misc endpoint
     * regardless of which slug the controller needed.
     */
    public function test_ict_admin_without_staff_slug_is_403_at_staff(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.complaints.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/staff');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.staff.manage should be 403 at /admin/staff');
    }

    public function test_ict_admin_without_complaints_slug_is_403_at_complaints(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.staff.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/complaints');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.complaints.manage should be 403 at /admin/complaints');
    }

    public function test_staff_without_settings_slug_is_403_at_settings(): void
    {
        $user = $this->makeUserWithSubset('staff', [
            'admin.notifications.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/settings');
        $this->assertSame(403, $resp->getStatusCode(),
            'staff without admin.system-settings.manage should be 403 at /admin/settings');
    }

    public function test_ict_admin_without_dashboard_slug_is_403_at_admin_dashboard(): void
    {
        $user = $this->makeUserWithSubset('ict_admin', [
            'admin.staff.manage',
        ]);

        $resp = $this->actingAs($user)->get('/admin/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'ict_admin without admin.dashboard.manage should be 403 at /admin/dashboard');
    }

    /**
     * ict_admin with all 10 misc slugs reaches every controller.
     * Proves the wiring is symmetric.
     */
    public function test_ict_admin_with_all_misc_slugs_passes_all_controllers(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $urls = [
            '/admin/dashboard',
            '/admin/staff',
            '/admin/complaints',
            '/admin/previous-results',
            '/admin/transcripts',
            '/admin/settings',
            '/admin/notifications',
            '/admin/analytics',
            '/admin/reports',
            '/admin/hospital-services',
        ];
        foreach ($urls as $url) {
            $resp = $this->actingAs($ictAdmin)->get($url);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "ict_admin with all slugs should pass the controller gate at {$url}");
        }
    }

    /**
     * ict_admin with admin.staff.manage can POST the destructive
     * reset-password endpoint. Proves the per-resource slug covers
     * all verbs, not just GET.
     */
    public function test_ict_admin_with_staff_slug_passes_destructive_verbs(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->post('/admin/staff/1/reset-password', [
            'new_password' => 'whatever',
        ]);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.staff.manage should pass the controller gate on POST');
    }

    /**
     * ict_admin with admin.hospital-services.manage can POST the
     * destructive toggle endpoint. Independent regression guard
     * for the toggle method on HospitalServiceController.
     */
    public function test_ict_admin_with_hospital_services_slug_passes_toggle(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->post('/admin/hospital-services/1/toggle');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.hospital-services.manage should pass the toggle route');
    }

    /**
     * ict_admin with admin.system-settings.manage can PUT the
     * destructive settings update endpoint.
     */
    public function test_ict_admin_with_settings_slug_passes_update_settings(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->put('/admin/settings', [
            '_token' => 'test',
        ]);
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.system-settings.manage should pass PUT /admin/settings');
    }

    /**
     * ict_admin with admin.previous-results.manage can DELETE a
     * previous-result row. Proves the per-resource slug covers
     * the destroy verb too.
     */
    public function test_ict_admin_with_previous_results_slug_passes_destroy(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        $resp = $this->actingAs($ictAdmin)->delete('/admin/previous-results/1');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'ict_admin with admin.previous-results.manage should pass DELETE /admin/previous-results/1');
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
