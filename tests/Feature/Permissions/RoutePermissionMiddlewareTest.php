<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8f regression — the route-level `permission:slug` middleware
 * fires before the controller method runs.
 *
 * Before slice 8f, every route under finance/executive/hospital was
 * gated only by `auth + role:...`. The `role:` chain admits a wide
 * role list (e.g. `bursar`'s group admits 14 roles); a user could
 * reach a route regardless of which slugs their `role_permissions`
 * pivot actually held. The trait-side `requirePermission()` call
 * inside the controller would still 403 them — but only after the
 * method body started executing, and only for routes where the
 * controller method actually gated.
 *
 * The 18 hospital dashboards (`/hospital/dashboard`, `/hospital/doctor/dashboard`,
 * `/hospital/nurse/dashboard`, etc.) didn't gate at all in the
 * controller — any authenticated user could reach them. Slice 8f
 * closes that leak and adds a `permission:` chain to every other
 * route in finance/executive/hospital.
 *
 * This test pins the new behaviour:
 *  - Users with the right slug get past the route middleware
 *    (we expect a 200 from a deliberately-empty route stub or a 500
 *    from the underlying controller view, NOT a 403).
 *  - Users without the slug are 403'd at the middleware — never
 *    reaches the controller.
 *
 * Tests run against the same hand-rolled schema as PermissionServiceTest
 * (in-memory sqlite), so we don't need a live DB. We seed just the
 * slugs the matrix needs.
 */
class RoutePermissionMiddlewareTest extends TestCase
{
    /**
     * Catalogue — slug => group. Minimal set covering every route
     * the matrix below exercises. Mirrors `PermissionsSeeder::PREFIX_TO_GROUP`
     * so a future seeder change is the only thing that needs to stay
     * in sync with this fixture map.
     */
    private const CATALOG = [
        'finance.dashboard.view'             => 'finance',
        'finance.invoices.create'            => 'finance',
        'finance.payroll.view'               => 'finance',
        'finance.receipts.view'              => 'finance',
        'executive.dashboard.view'           => 'executive',
        'executive.finance.revenue.view'     => 'executive',
        'auditor.audit.logs'                 => 'auditor',
        'patients.view'                      => 'hospital',
        'wards.view'                         => 'hospital',
        'pharmacy.view'                      => 'hospital',
        'lab.view'                           => 'hospital',
        'pharmacy.dispense'                  => 'hospital',
    ];

    /**
     * Per-role pivot — slug => true (role has it).
     * Mirrors HospitalPermissions / BursarPermissions / FinancePermissions
     * contracts for the roles the matrix uses.
     */
    private const ROLE_PERMISSIONS = [
        // Finance officer — full finance module access for tests.
        'finance_officer' => [
            'finance.dashboard.view', 'finance.invoices.create',
            'finance.payroll.view', 'finance.receipts.view',
        ],
        // Accountant — IS in the finance role chain (so it passes the
        // `role:` middleware on finance routes) but holds NONE of the
        // finance slugs. Used to isolate the `permission:` gate from the
        // `role:` gate.
        'accountant' => [],
        // Bursar — full bursar module access; explicit NO finance.invoices.create
        // (bursar doesn't write into the finance module).
        'bursar' => [
            // We only need ONE cross-check here: that the bursar user
            // is 403'd on a finance route they don't have permission for.
            // No slugs from finance.* are granted.
        ],
        // Rector — executive only.
        'rector' => [
            'executive.dashboard.view', 'executive.finance.revenue.view',
        ],
        // Auditor — audit module only.
        'auditor' => [
            'auditor.audit.logs',
        ],
        // Cmd — wildcard (gets every catalogue slug via the wildcard path).
        'cmd' => ['*'],
        // Doctor — has patients.view via the doctor contract.
        'doctor' => ['patients.view'],
        // Nurse — has wards.view.
        'nurse'  => ['wards.view'],
        // Pharmacist — has pharmacy.view AND pharmacy.dispense.
        'pharmacist' => ['pharmacy.view', 'pharmacy.dispense'],
        // Lab scientist — has lab.view.
        'lab_scientist' => ['lab.view'],
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
     * Finance dashboard is gated by `permission:finance.dashboard.view`.
     *  - rector + finance_officer → permission passes (200-or-other, NOT 403)
     *  - bursar → permission denies → 403
     *
     * We assert on the status code, not the response body, because the
     * underlying controller may 500 in the test env if it needs DB
     * tables we don't seed. The point of this test is the route-layer
     * gate, not the controller body.
     */
    public function test_finance_dashboard_route_gates_on_slug(): void
    {
        // finance_officer has the slug
        $finance = $this->makeUser('finance_officer');
        // bursar does NOT
        $bursar = $this->makeUser('bursar');

        // finance_officer — passes the gate (controller may still 500, but it's NOT 403)
        $resp = $this->actingAs($finance)->get('/finance/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'finance_officer with finance.dashboard.view should pass the route gate');

        // bursar — fails the gate
        $resp = $this->actingAs($bursar)->get('/finance/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar without finance.dashboard.view should be 403 at the route layer');
    }

    /**
     * Finance invoices create is gated by `permission:finance.invoices.create`.
     * finance_officer has it; bursar doesn't.
     */
    public function test_finance_invoices_create_route_gates_on_slug(): void
    {
        $finance = $this->makeUser('finance_officer');
        $bursar  = $this->makeUser('bursar');

        // finance_officer — passes the gate
        $resp = $this->actingAs($finance)->get('/finance/invoices/create');
        $this->assertNotSame(403, $resp->getStatusCode());

        // bursar — denied
        $resp = $this->actingAs($bursar)->get('/finance/invoices/create');
        $this->assertSame(403, $resp->getStatusCode());
    }

    /**
     * Executive dashboard is gated by `permission:executive.dashboard.view`.
     * Only rector has it; bursar + finance_officer don't.
     */
    public function test_executive_dashboard_route_gates_on_slug(): void
    {
        $rector  = $this->makeUser('rector');
        $bursar  = $this->makeUser('bursar');

        $resp = $this->actingAs($rector)->get('/executive/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode());

        $resp = $this->actingAs($bursar)->get('/executive/dashboard');
        $this->assertSame(403, $resp->getStatusCode());
    }

    /**
     * Audit logs route is gated by `permission:auditor.audit.logs`.
     * Auditor has it; rector/bursar don't.
     */
    public function test_auditor_audit_logs_route_gates_on_slug(): void
    {
        $auditor = $this->makeUser('auditor');
        $bursar  = $this->makeUser('bursar');

        $resp = $this->actingAs($auditor)->get('/auditor/audit-logs');
        $this->assertNotSame(403, $resp->getStatusCode());

        $resp = $this->actingAs($bursar)->get('/auditor/audit-logs');
        $this->assertSame(403, $resp->getStatusCode());
    }

    /**
     * The 18 hospital dashboards had NO middleware at all before
     * slice 8f — any authenticated user could reach them. After slice
     * 8f, the outer `prefix('hospital')` group carries `auth` and the
     * dashboard routes carry a per-audience `permission:` chain.
     *
     * Pick the cmd dashboard (the cross-cutting one): gated by
     * `patients.view`. cmd has it via the wildcard; bursar doesn't.
     */
    public function test_hospital_dashboard_route_gates_on_slug(): void
    {
        $cmd   = $this->makeUser('cmd');
        $bursar = $this->makeUser('bursar');

        // cmd — passes (has patients.view via wildcard)
        $resp = $this->actingAs($cmd)->get('/hospital/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'cmd should pass the hospital/dashboard route gate');

        // bursar — denied (no patients.view)
        $resp = $this->actingAs($bursar)->get('/hospital/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at the hospital/dashboard route');
    }

    /**
     * /hospital/doctor/dashboard gated by `patients.view`.
     * Doctor has it (per the doctor contract); bursar doesn't.
     */
    public function test_hospital_doctor_dashboard_route_gates_on_slug(): void
    {
        $doctor = $this->makeUser('doctor');
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($doctor)->get('/hospital/doctor/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'doctor should pass /hospital/doctor/dashboard route gate');

        $resp = $this->actingAs($bursar)->get('/hospital/doctor/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at /hospital/doctor/dashboard');
    }

    /**
     * /hospital/nurse/dashboard gated by `wards.view`. Nurse has it.
     */
    public function test_hospital_nurse_dashboard_route_gates_on_slug(): void
    {
        $nurse  = $this->makeUser('nurse');
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($nurse)->get('/hospital/nurse/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'nurse should pass /hospital/nurse/dashboard route gate');

        $resp = $this->actingAs($bursar)->get('/hospital/nurse/dashboard');
        $this->assertSame(403, $resp->getStatusCode());
    }

    /**
     * /hospital/pharmacy/dashboard gated by `pharmacy.view`.
     * Pharmacist has it; nurse doesn't.
     */
    public function test_hospital_pharmacy_dashboard_route_gates_on_slug(): void
    {
        $pharmacist = $this->makeUser('pharmacist');
        $nurse      = $this->makeUser('nurse');

        $resp = $this->actingAs($pharmacist)->get('/hospital/pharmacy/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'pharmacist should pass /hospital/pharmacy/dashboard route gate');

        $resp = $this->actingAs($nurse)->get('/hospital/pharmacy/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'nurse should be 403 at /hospital/pharmacy/dashboard');
    }

    /**
     * /hospital/lab/dashboard gated by `lab.view`.
     * Lab scientist has it; bursar doesn't.
     */
    public function test_hospital_lab_dashboard_route_gates_on_slug(): void
    {
        $lab    = $this->makeUser('lab_scientist');
        $bursar = $this->makeUser('bursar');

        $resp = $this->actingAs($lab)->get('/hospital/lab/dashboard');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'lab_scientist should pass /hospital/lab/dashboard route gate');

        $resp = $this->actingAs($bursar)->get('/hospital/lab/dashboard');
        $this->assertSame(403, $resp->getStatusCode(),
            'bursar should be 403 at /hospital/lab/dashboard');
    }

    /**
     * Guest (no auth) on a hospital route that previously had no
     * middleware should now redirect to /login (auth middleware
     * short-circuits before the permission check).
     */
    public function test_hospital_dashboard_redirects_guest_to_login(): void
    {
        $resp = $this->get('/hospital/dashboard');
        // Either 302 redirect to /login, or directly 403 if the test
        // environment skips the redirect. Accept either — what we
        // really care about is that the guest does NOT reach the
        // controller.
        $this->assertContains(
            $resp->getStatusCode(),
            [302, 403],
            'guest should not reach /hospital/dashboard after slice 8f',
        );
    }

    /**
     * Finance payroll {payroll} route is gated by `finance.payroll.view`.
     * finance_officer has it; bursar doesn't.
     *
     * The {payroll} parameter is irrelevant to the middleware — we
     * pick an arbitrary id. The middleware denies before the model
     * binding resolves, so a missing id 403s at the gate, not at the
     * model layer.
     *
     * Note: we use `accountant` (which IS in the finance role chain but
     * lacks `finance.payroll.view`) rather than `bursar` (which is NOT
     * in the finance role chain). The point of this test is the
     * `permission:` middleware, not the `role:` middleware — and using
     * a role that's already past the role gate isolates the slug gate.
     */
    public function test_finance_payroll_show_route_gates_on_slug(): void
    {
        // Use the payroll INDEX route (`GET /finance/payroll`), not the SHOW
        // route (`GET /finance/payroll/{payroll}`). The show route has
        // route-model binding to `FinancePayroll::find($id)` which throws
        // a `ModelNotFoundException` BEFORE `permission:` middleware
        // runs (Laravel 11 puts `SubstituteBindings` early in the web
        // middleware group). That would mask the slug gate.
        //
        // Index has the same `permission:finance.payroll.view` slug —
        // we just hit a different verb of the same controller.
        $finance   = $this->makeUser('finance_officer');
        $accountant = $this->makeUser('accountant');

        $resp = $this->actingAs($finance)->get('/finance/payroll');
        $this->assertNotSame(403, $resp->getStatusCode(),
            'finance_officer should pass the /finance/payroll route gate');

        $resp = $this->actingAs($accountant)->get('/finance/payroll');
        $this->assertSame(403, $resp->getStatusCode(),
            'accountant (in finance role chain but lacking finance.payroll.view) '
            . 'should be 403 at /finance/payroll');
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
        // Catalogue rows — every slug the matrix asks about must exist.
        foreach (self::CATALOG as $slug => $group) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'group' => $group],
            );
        }

        // Roles + their pivot rows.
        foreach (self::ROLE_PERMISSIONS as $roleSlug => $slugs) {
            $role = Role::firstOrCreate(
                ['slug' => $roleSlug],
                ['name' => ucfirst(str_replace('_', ' ', $roleSlug))],
            );

            if ($slugs === ['*']) {
                // Wildcard — sync everything in the catalogue. Mirrors
                // HospitalPermissions::ROLE_PERMISSIONS['cmd'] = ['*'].
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
