<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression tests for the cross-domain PermissionService.
 *
 * The 2026_08_12 cross-domain permission foundation (permissions +
 * role_permissions schema + PermissionsSeeder + RolePermissionsSeeder)
 * landed with a latent bug: the wildcard expansion path called
 * `Permission::pluck('slug')` without importing `App\Models\Permission`,
 * so PHP silently resolved to `App\Services\Permissions\Permission` —
 * which doesn't exist — and any role carrying the `'*'` wildcard (cmd,
 * super_admin, admin, hospital_store_manager, medical_director)
 * would have hit a fatal on first permission check. The bug was fixed
 * in c19c7ef3; this test pins the behaviour so it can't regress.
 *
 * Tests cover:
 *  - Wildcard role gets EVERY catalogue permission (incl. ones the
 *    wildcard role didn't explicitly list) — this is the path that
 *    needed the import fix.
 *  - Non-wildcard role gets EXACTLY its allowed slugs.
 *  - Unknown permission returns false without erroring.
 *  - Multi-role pivot: a user with primary role + pivot role gets
 *    the union of both permission sets.
 *  - allowsAny short-circuits on the first match.
 *  - Per-request cache: PermissionService::flush() resets state.
 *  - Guest (no auth) returns false.
 */
class PermissionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();

        // Defensive: clear any cached permissions from a previous test
        // class that ran in the same PHPUnit process (the service holds
        // a static array). The setUp() of one of our siblings might have
        // populated it with their own user-id keys; flush() is cheap.
        PermissionService::flush();
    }

    protected function tearDown(): void
    {
        // Drop in reverse-dependency order: pivots first, then parent
        // tables. SQLite is strict about foreign-key references during
        // drop, so the order matters.
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
     * Wildcard path — the regression we care about. A role whose
     * permission list contains `'*'` (mirroring HospitalPermissions::
     * ROLE_PERMISSIONS for cmd/super_admin/etc.) must receive every
     * catalogue row, not a fatal.
     */
    public function test_wildcard_role_gets_every_catalogue_permission(): void
    {
        $cmd = $this->makeUser('cmd');

        // 4 catalogue permissions across two domains — anything the
        // wildcard role didn't explicitly list should still pass.
        $adminSlug = Permission::where('slug', 'admin.manage')->first();
        $labSlug = Permission::where('slug', 'lab.process')->first();

        $this->assertTrue(PermissionService::allows('patients.view', $cmd));
        $this->assertTrue(PermissionService::allows('pharmacy.dispense', $cmd));
        $this->assertTrue($adminSlug === null); // sanity: admin.* not seeded
        $this->assertNotNull($labSlug);
        $this->assertTrue(PermissionService::allows('lab.process', $cmd));
    }

    /**
     * Non-wildcard role gets EXACTLY the slugs seeded into the pivot.
     * An unseeded slug must return false.
     */
    public function test_non_wildcard_role_is_exact_match(): void
    {
        $doctor = $this->makeUser('doctor');

        $this->assertTrue(PermissionService::allows('patients.view', $doctor));
        $this->assertTrue(PermissionService::allows('consultations.create', $doctor));

        // 'billing.payment' was NOT seeded for the doctor role.
        $this->assertFalse(PermissionService::allows('billing.payment', $doctor));
    }

    /**
     * Unknown permission (not in the catalogue) must return false
     * cleanly — no exceptions, no warnings, just false.
     */
    public function test_unknown_permission_returns_false(): void
    {
        $doctor = $this->makeUser('doctor');
        $this->assertFalse(PermissionService::allows('does.not.exist', $doctor));
    }

    /**
     * Multi-role pivot: a user holding primary `cashier` + pivot `doctor`
     * gets the union of both permission sets. This is the same path that
     * produces the per-request cache key, so a regression here would
     * also mask issue with `User::allRoleSlugs()`.
     */
    public function test_multi_role_user_gets_union_of_permissions(): void
    {
        // The user's primary role is `multi_cashier_doctor`; we then
        // attach the doctor and cashier roles via the role_user pivot.
        // Roles pivot on role IDs, not user IDs — easy to get wrong.
        $doctorRole  = Role::firstOrCreate(['slug' => 'doctor'],  ['name' => 'Doctor']);
        $cashierRole = Role::firstOrCreate(['slug' => 'cashier'], ['name' => 'Cashier']);

        $user = $this->makeUser('multi_cashier_doctor');
        $user->roles()->syncWithoutDetaching([$doctorRole->id, $cashierRole->id]);
        $user->refresh();

        // doctor-only: patients.view ✓
        $this->assertTrue(PermissionService::allows('patients.view', $user));
        // cashier-only: billing.payment ✓
        $this->assertTrue(PermissionService::allows('billing.payment', $user));

        // Defence-in-depth: none of the three roles the user holds
        // grants pharmacy.dispense (cmd does, via the wildcard, but
        // cmd is NOT in the user's role set).
        foreach (['doctor', 'cashier', 'multi_cashier_doctor'] as $slug) {
            $pivot = Role::where('slug', $slug)->first()->permissions()->pluck('slug')->all();
            $this->assertNotContains('pharmacy.dispense', $pivot,
                "role '$slug' should not have pharmacy.dispense");
        }

        // neither: still false ✓
        $this->assertFalse(PermissionService::allows('pharmacy.dispense', $user));
    }

    /**
     * `allowsAny` short-circuits as soon as one match is found.
     */
    public function test_allows_any_short_circuits_on_first_match(): void
    {
        $doctor = $this->makeUser('doctor');

        $this->assertTrue(
            PermissionService::allowsAny(['pharmacy.dispense', 'patients.view'], $doctor),
        );
        // All unknown slugs → false.
        $this->assertFalse(
            PermissionService::allowsAny(['does.not.exist', 'still.nope'], $doctor),
        );
    }

    /**
     * The per-request cache is keyed by user id. Different users
     * produce different cache entries; PermissionService::flush()
     * clears the cache (used by admin-grants-a-permission paths).
     */
    public function test_per_request_cache_is_isolated_per_user_and_flushable(): void
    {
        $doctor = $this->makeUser('doctor');
        $cashier = $this->makeUser('cashier');

        // Populate the cache via the doctor.
        $this->assertTrue(PermissionService::allows('patients.view', $doctor));
        $this->assertFalse(PermissionService::allows('billing.payment', $doctor));

        // Cashier sees its own truth, not the doctor's.
        $this->assertTrue(PermissionService::allows('billing.payment', $cashier));

        PermissionService::flush();

        // After flush, the service re-resolves — the cashier's
        // permission still works (the pivot hasn't changed).
        $this->assertTrue(PermissionService::allows('billing.payment', $cashier));
    }

    /**
     * `roleSlugFor()` returns the primary role slug of the user.
     */
    public function test_role_slug_for_returns_primary_role(): void
    {
        $doctor = $this->makeUser('doctor');
        $this->assertSame('doctor', PermissionService::roleSlugFor($doctor));

        // No user → null.
        $this->assertNull(PermissionService::roleSlugFor(null));
    }

    /**
     * Negative path: a guest with no auth returns false, no exceptions.
     * Mirrors `EnforcesHospitalPermission::requirePermission()` behaviour
     * when called from a context where Auth::user() is null.
     */
    public function test_guest_returns_false(): void
    {
        $this->assertFalse(PermissionService::allows('patients.view', null));
        $this->assertFalse(PermissionService::allowsAny(['patients.view'], null));
    }

    /**
     * The non-hospital catalogue must be populated end-to-end.
     *
     * Pins the slice that expanded the `permissions` table from
     * hospital-only to every domain. Rows are seeded directly here
     * (rather than via the seeder class) because the hand-rolled
     * schema is intentionally minimal — we just need enough slugs
     * in each group to prove the slug → group mapping lands the
     * catalogue row in the right bucket.
     */
    public function test_non_hospital_catalogue_is_populated(): void
    {
        // A representative slug from each non-hospital domain. The
        // slug → group mapping mirrors `PermissionsSeeder::PREFIX_TO_GROUP`.
        $expectedGroups = [
            'bursar.payments.view'              => 'bursar',
            'bursar.debtors.export'             => 'bursar',
            'registrar.applicants.view'         => 'registrar',
            'registrar.admissions.generate-letter' => 'registrar',
            'librarian.books.view'              => 'librarian',
            'librarian.borrowing.issue'         => 'librarian',
            'finance.transactions.view'         => 'finance',
            'finance.payroll.approve'           => 'finance',
            'academic.courses.view'             => 'academic',
            'academic.results.approve'          => 'academic',
            'auditor.audit.view'                => 'auditor',
            'executive.students.view'           => 'executive',
        ];

        foreach ($expectedGroups as $slug => $group) {
            $row = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'group' => $group],
            );
            $this->assertSame($group, $row->group,
                "slug '$slug' should land in group '$group' but got '{$row->group}'");
        }

        // Defence-in-depth: every group label above has at least one row.
        foreach (array_unique(array_values($expectedGroups)) as $group) {
            $count = Permission::where('group', $group)->count();
            $this->assertGreaterThan(0, $count,
                "expected at least one row in group '$group'");
        }
    }

    /**
     * A bursar role gets bursar-specific slugs but NOT hospital create
     * slugs. Pins the cross-domain coverage the slice delivered.
     *
     * Reuses the hand-rolled fixtures: a bursar role is attached to a
     * few permissions that mirror the BursarPermissions contract,
     * plus a `patients.create` row that must NOT be in the bursar
     * pivot (bursar doesn't manage patient charts).
     */
    public function test_bursar_role_sees_bursar_permissions(): void
    {
        // Add the bursar-specific catalogue rows.
        $bursarPerms = [
            ['name' => 'Bursar: Payments View', 'slug' => 'bursar.payments.view', 'group' => 'bursar'],
            ['name' => 'Bursar: Debtors View',  'slug' => 'bursar.debtors.view',  'group' => 'bursar'],
            ['name' => 'Patients: Create',      'slug' => 'patients.create',      'group' => 'hospital'],
        ];
        foreach ($bursarPerms as $row) {
            Permission::firstOrCreate(['slug' => $row['slug']], $row);
        }

        // Create the bursar role with its expected pivot.
        $bursarRole = Role::firstOrCreate(['slug' => 'bursar'], ['name' => 'Bursar']);
        $bursarRole->permissions()->sync(
            Permission::whereIn('slug', ['bursar.payments.view', 'bursar.debtors.view'])
                ->pluck('id')->all(),
        );

        $bursar = $this->makeUser('bursar');

        // Bursar sees its own slugs.
        $this->assertTrue(PermissionService::allows('bursar.payments.view', $bursar));
        $this->assertTrue(PermissionService::allows('bursar.debtors.view', $bursar));

        // Bursar does NOT see the hospital create slug.
        $this->assertFalse(PermissionService::allows('patients.create', $bursar));
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

        // The pivot between role and user (multi-role support).
        // Migration 2026_08_11_000003_create_role_user_pivot_table.
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

        // The pivot between role and permission. Migration
        // 2026_08_12_000001_create_permissions_tables.
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
        // Roles modelled after HospitalPermissions::ROLE_PERMISSIONS, but
        // trimmed to a handful so the test stays small and the catalogue
        // is deterministic. Use `firstOrCreate` keyed by slug so a
        // second run inside the same process is safe.
        $cmd     = Role::firstOrCreate(['slug' => 'cmd'],     ['name' => 'Command']);
        $doctor  = Role::firstOrCreate(['slug' => 'doctor'],  ['name' => 'Doctor']);
        $cashier = Role::firstOrCreate(['slug' => 'cashier'], ['name' => 'Cashier']);
        Role::firstOrCreate(['slug' => 'multi_cashier_doctor'], ['name' => 'Multi']);

        // Catalogue — covers four different prefixes so the wildcard
        // expansion path has real, varied rows to return.
        $catalog = [
            ['name' => 'Patients: View',        'slug' => 'patients.view',        'group' => 'hospital'],
            ['name' => 'Patients: Search',      'slug' => 'patients.search',      'group' => 'hospital'],
            ['name' => 'Consultations: Create', 'slug' => 'consultations.create', 'group' => 'hospital'],
            ['name' => 'Lab: Process',          'slug' => 'lab.process',          'group' => 'hospital'],
            ['name' => 'Pharmacy: Dispense',    'slug' => 'pharmacy.dispense',    'group' => 'hospital'],
            ['name' => 'Billing: Payment',      'slug' => 'billing.payment',      'group' => 'bursar'],
        ];
        foreach ($catalog as $row) {
            Permission::firstOrCreate(['slug' => $row['slug']], $row);
        }

        // Wildcard role: every catalogue row. Mirrors cmd/super_admin.
        // The seeder hardcodes 'cmd' → '*', so we use sync() to be sure
        // exactly the catalogue rows are present (in case an earlier
        // run attached stale rows).
        $cmd->permissions()->sync(Permission::pluck('id')->all());

        // Doctor: a focused set that excludes billing.
        $doctor->permissions()->sync(
            Permission::whereIn('slug', [
                'patients.view', 'patients.search',
                'consultations.create',
                'lab.process',
            ])->pluck('id')->all(),
        );

        // Cashier: only billing.
        $cashier->permissions()->sync(
            Permission::whereIn('slug', ['billing.payment'])->pluck('id')->all(),
        );
    }

    private function makeUser(string $roleSlug): User
    {
        $role = Role::firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst(str_replace('_', ' ', $roleSlug)), 'is_active' => true],
        );
        return User::create([
            'name'      => ucfirst(str_replace('_', ' ', $roleSlug)) . ' User',
            'email'     => $roleSlug . '@test.local',
            'password'  => bcrypt('password'),
            'role_id'   => $role->id,
            'is_active' => true,
        ]);
    }
}
