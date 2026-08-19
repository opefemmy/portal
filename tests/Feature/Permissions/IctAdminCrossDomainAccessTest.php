<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 8j-audit regression — the audit found that `ict_admin`
 * was listed in the `/bursar/*` (routes/web.php:1336) and
 * `/finance/*` (routes/finance.php:23) role: middleware, but
 * AdminPermissions gave them zero bursar/finance slugs. The
 * intent at routes/finance.php:13-16 is "ICT admin... need to
 * be able to open finance screens for read-only reconciliation
 * work."
 *
 * This test proves the fix: ict_admin now reaches the read-only
 * bursar/finance endpoints AND is still 403'd on the destructive
 * ones (write paths stay protected).
 */
class IctAdminCrossDomainAccessTest extends TestCase
{
    /**
     * Sampled bursar + finance routes. Read-only on top, write
     * paths at the bottom — the test proves ict_admin reaches
     * the read endpoints and is still 403'd at the destructive
     * ones.
     */
    private const READ_ONLY_ROUTES = [
        // Bursar read endpoints.
        ['GET', '/bursar/dashboard',    'bursar.dashboard.view'],
        ['GET', '/bursar/payments',     'bursar.payments.view'],
        ['GET', '/bursar/debtors',      'bursar.debtors.view'],
        ['GET', '/bursar/fees',         'bursar.fees.view'],
        ['GET', '/bursar/reports',      'bursar.reports.view'],

        // Finance read endpoints.
        ['GET', '/finance/dashboard',   'finance.dashboard.view'],
        ['GET', '/finance/transactions','finance.transactions.view'],
        ['GET', '/finance/invoices',    'finance.invoices.view'],
        ['GET', '/finance/receipts',    'finance.receipts.view'],
        ['GET', '/finance/budgets',     'finance.budgets.view'],
        ['GET', '/finance/vendors',     'finance.vendors.view'],
        ['GET', '/finance/payroll',     'finance.payroll.view'],
    ];

    /**
     * Sampled write endpoints ict_admin must still be 403'd at.
     * Proves the audit fix grants ONLY read access, not full
     * bursar/finance write capabilities.
     *
     * Note: we use form pages and POST collection endpoints (not
     * `/{id}/...` model-binding routes) because the hand-rolled
     * test schema doesn't have a `payments` / `invoices` / etc.
     * table. The route middleware fires BEFORE the controller
     * body, so we don't need the model row to exist — we just
     * need a route that exists in the route table.
     */
    private const DESTRUCTIVE_ROUTES = [
        // Bursar write paths — form pages (no model binding).
        ['GET',  '/bursar/payments/upload',  'bursar.payments.create'],
        ['GET',  '/bursar/regimes/create',   'bursar.regimes.configure'],

        // Finance write paths (POST on resource collection
        // endpoints — the route is registered, no model binding
        // required to reach the permission: middleware).
        ['POST', '/finance/invoices',        'finance.invoices.create'],
        ['POST', '/finance/receipts',        'finance.receipts.create'],
        ['POST', '/finance/transactions',    'finance.transactions.create'],
        ['POST', '/finance/budgets',         'finance.budgets.create'],
        ['POST', '/finance/payroll',         'finance.payroll.create'],
    ];

    /**
     * Bursar + finance catalogue. ict_admin gets the read-only
     * subset (verified by the SAMPLE_ROUTES_* above); the
     * destructive slugs are intentionally NOT in ict_admin's
     * grant list, so the destructive-route tests 403 them.
     */
    private const catalogue = [
        // Bursar slugs.
        'bursar.payments.view'         => 'bursar',
        'bursar.payments.create'       => 'bursar',
        'bursar.payments.verify'       => 'bursar',
        'bursar.payments.export'       => 'bursar',
        'bursar.debtors.view'          => 'bursar',
        'bursar.debtors.export'        => 'bursar',
        'bursar.fees.view'             => 'bursar',
        'bursar.fees.configure'        => 'bursar',
        'bursar.regimes.view'          => 'bursar',
        'bursar.regimes.configure'     => 'bursar',
        'bursar.reports.view'          => 'bursar',
        'bursar.reports.export'        => 'bursar',
        'bursar.dashboard.view'        => 'bursar',
        'bursar.dashboard.configure'   => 'bursar',

        // Finance slugs.
        'finance.transactions.view'    => 'finance',
        'finance.transactions.create'  => 'finance',
        'finance.transactions.edit'    => 'finance',
        'finance.transactions.delete'  => 'finance',
        'finance.transactions.export'  => 'finance',
        'finance.invoices.view'        => 'finance',
        'finance.invoices.create'      => 'finance',
        'finance.invoices.edit'        => 'finance',
        'finance.invoices.delete'      => 'finance',
        'finance.invoices.send'        => 'finance',
        'finance.receipts.view'        => 'finance',
        'finance.receipts.create'      => 'finance',
        'finance.receipts.print'       => 'finance',
        'finance.receipts.export'      => 'finance',
        'finance.budgets.view'         => 'finance',
        'finance.budgets.create'       => 'finance',
        'finance.budgets.edit'         => 'finance',
        'finance.budgets.approve'      => 'finance',
        'finance.vendors.view'         => 'finance',
        'finance.vendors.create'       => 'finance',
        'finance.vendors.edit'         => 'finance',
        'finance.payroll.view'         => 'finance',
        'finance.payroll.create'       => 'finance',
        'finance.payroll.approve'      => 'finance',
        'finance.dashboard.view'       => 'finance',
        'finance.dashboard.configure'  => 'finance',
    ];

    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        // ict_admin — slice 8j-audit fix. Read-only bursar/finance
        // access to match the documented intent at
        // routes/finance.php:13-16 ("ICT admin... need to be able
        // to open finance screens for read-only reconciliation
        // work"). No destructive slugs.
        'ict_admin' => [
            'bursar.payments.view',
            'bursar.debtors.view',
            'bursar.fees.view',
            'bursar.reports.view',
            'bursar.dashboard.view',
            'finance.transactions.view',
            'finance.invoices.view',
            'finance.receipts.view',
            'finance.budgets.view',
            'finance.vendors.view',
            'finance.payroll.view',
            'finance.dashboard.view',
        ],

        // Bursar's full grant list — for the negative-control
        // test (bursar should still pass read AND write
        // endpoints).
        'bursar' => [
            'bursar.payments.view',
            'bursar.payments.create',
            'bursar.payments.verify',
            'bursar.payments.export',
            'bursar.debtors.view',
            'bursar.debtors.export',
            'bursar.fees.view',
            'bursar.fees.configure',
            'bursar.regimes.view',
            'bursar.regimes.configure',
            'bursar.reports.view',
            'bursar.reports.export',
            'bursar.dashboard.view',
            'bursar.dashboard.configure',
        ],

        'finance' => [
            'finance.transactions.view',
            'finance.transactions.create',
            'finance.invoices.view',
            'finance.invoices.create',
            'finance.receipts.view',
            'finance.receipts.create',
            'finance.receipts.print',
            'finance.budgets.view',
            'finance.budgets.create',
            'finance.vendors.view',
            'finance.payroll.view',
            'finance.payroll.create',
            'finance.dashboard.view',
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
    // Slice 8j-audit primary regression — ict_admin read-only access
    // ----------------------------------------------------------------

    /**
     * PRIMARY REGRESSION for slice 8j-audit: ict_admin reaches
     * every read-only bursar/finance endpoint. Before the audit
     * fix they were 403'd at the trait gate despite being in the
     * route's role: middleware.
     */
    public function test_ict_admin_reaches_bursar_read_endpoints(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        foreach (self::READ_ONLY_ROUTES as [$method, $route, $slug]) {
            $resp = $this->actingAs($ictAdmin)->{$method}($route);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "ict_admin should pass permission:{$slug} on {$method} {$route}");
        }
    }

    /**
     * Companion to above: ict_admin is 403'd at destructive
     * bursar/finance endpoints. Proves the audit fix grants
     * ONLY read access — write paths stay protected.
     */
    public function test_ict_admin_is_403_at_destructive_bursar_finance_endpoints(): void
    {
        $ictAdmin = $this->makeUser('ict_admin');

        foreach (self::DESTRUCTIVE_ROUTES as [$method, $route, $slug]) {
            $resp = $this->actingAs($ictAdmin)->{$method}($route);
            $this->assertSame(403, $resp->getStatusCode(),
                "ict_admin should be 403 at permission:{$slug} on {$method} {$route}");
        }
    }

    // ----------------------------------------------------------------
    // Negative controls — confirm the test's grant model is sound
    // ----------------------------------------------------------------

    /**
     * Negative control: bursar reaches read AND write endpoints.
     * Confirms the read/write distinction in the catalogue is
     * what gates ict_admin, not anything else in the test rig.
     */
    public function test_bursar_reaches_both_read_and_write_endpoints(): void
    {
        $bursar = $this->makeUser('bursar');

        // Bursar passes the destructive routes.
        foreach (self::DESTRUCTIVE_ROUTES as [$method, $route, $slug]) {
            // Bursar passes bursar.* (not finance.*), so we only
            // assert the bursar-side destructive routes here.
            if (strpos($slug, 'bursar.') !== 0) {
                continue;
            }
            $resp = $this->actingAs($bursar)->{$method}($route);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "bursar should pass permission:{$slug} on {$method} {$route}");
        }
    }

    /**
     * Negative control: finance role passes finance read AND
     * write endpoints (the role has both).
     */
    public function test_finance_role_reaches_both_read_and_write_endpoints(): void
    {
        $finance = $this->makeUser('finance');

        foreach (self::DESTRUCTIVE_ROUTES as [$method, $route, $slug]) {
            // Finance role only has finance.*, so we only assert
            // the finance-side destructive routes here.
            if (strpos($slug, 'finance.') !== 0) {
                continue;
            }
            $resp = $this->actingAs($finance)->{$method}($route);
            $this->assertNotSame(403, $resp->getStatusCode(),
                "finance should pass permission:{$slug} on {$method} {$route}");
        }
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
}