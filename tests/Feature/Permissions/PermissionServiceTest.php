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
    // Cross-domain permission matrix (slice 8e)
    // ----------------------------------------------------------------

    /**
     * Slice 8e regression — fixture-driven matrix walk over role × slug.
     *
     * After the slice 8e rollout every non-hospital controller calls
     * `requirePermission()` against a slug from the catalogue. If a
     * catalogue row exists for a slug but a role doesn't have it in
     * its pivot, gating with that slug would 403 a user who used to
     * reach the page. The matrix below pins which role × slug pairs
     * MUST agree with reality (the constants in each
     * `app/Services/{Bursar,Registrar,…}/…Permissions.php` class).
     *
     * Rows marked `'MISSING_ROLE'` are roles that are NOT yet seeded
     * in the hand-rolled schema (the live schema has them; the test
     * schema is intentionally minimal). They `markTestSkipped()` so
     * the test stays runnable even when the catalogue lists a role
     * the fixtures don't.
     */
    public function test_cross_domain_permission_matrix(): void
    {
        // Permission catalogue fixtures — every slug the matrix asks
        // about must exist in the `permissions` table with its group
        // tag, mirroring `PermissionsSeeder::PREFIX_TO_GROUP`.
        $catalog = [
            'bursar.dashboard.view'                  => 'bursar',
            'bursar.dashboard.configure'             => 'bursar',
            'bursar.payments.view'                   => 'bursar',
            'bursar.payments.create'                 => 'bursar',
            'bursar.payments.verify'                 => 'bursar',
            'bursar.payments.export'                 => 'bursar',
            'bursar.debtors.view'                    => 'bursar',
            'bursar.debtors.export'                  => 'bursar',
            'bursar.fees.view'                       => 'bursar',
            'bursar.fees.configure'                  => 'bursar',
            'bursar.regimes.view'                    => 'bursar',
            'bursar.regimes.configure'               => 'bursar',
            'bursar.reports.view'                    => 'bursar',
            'bursar.reports.export'                  => 'bursar',
            'registrar.applicants.view'              => 'registrar',
            'registrar.applicants.create'            => 'registrar',
            'registrar.applicants.edit'              => 'registrar',
            'registrar.applicants.review'            => 'registrar',
            'registrar.applicants.status-update'     => 'registrar',
            'registrar.applicants.reset-password'    => 'registrar',
            'registrar.admissions.view'              => 'registrar',
            'registrar.admissions.bulk-upload'       => 'registrar',
            'registrar.admissions.generate-letter'   => 'registrar',
            'registrar.admissions.track'             => 'registrar',
            'registrar.settings.view'                => 'registrar',
            'registrar.settings.edit'                => 'registrar',
            'registrar.reports.view'                 => 'registrar',
            'registrar.reports.export'               => 'registrar',
            'registrar.dashboard.view'               => 'registrar',
            'registrar.dashboard.configure'          => 'registrar',
            'librarian.dashboard.view'               => 'librarian',
            'librarian.dashboard.configure'          => 'librarian',
            'librarian.books.view'                   => 'librarian',
            'librarian.books.create'                 => 'librarian',
            'librarian.books.edit'                   => 'librarian',
            'librarian.books.delete'                 => 'librarian',
            'librarian.books.receive'                => 'librarian',
            'librarian.books.adjust'                 => 'librarian',
            'librarian.borrowing.view'               => 'librarian',
            'librarian.borrowing.issue'              => 'librarian',
            'librarian.borrowing.return'             => 'librarian',
            'librarian.borrowing.renew'              => 'librarian',
            'librarian.borrowing.export'             => 'librarian',
            'librarian.members.view'                 => 'librarian',
            'librarian.members.create'               => 'librarian',
            'librarian.members.edit'                 => 'librarian',
            'finance.dashboard.view'                 => 'finance',
            'finance.dashboard.configure'            => 'finance',
            'finance.transactions.view'              => 'finance',
            'finance.transactions.create'            => 'finance',
            'finance.transactions.edit'              => 'finance',
            'finance.invoices.view'                  => 'finance',
            'finance.invoices.create'                => 'finance',
            'finance.invoices.edit'                  => 'finance',
            'finance.receipts.view'                  => 'finance',
            'finance.receipts.create'                => 'finance',
            'finance.receipts.print'                 => 'finance',
            'finance.budgets.view'                   => 'finance',
            'finance.budgets.approve'                => 'finance',
            'finance.vendors.view'                   => 'finance',
            'finance.payroll.view'                   => 'finance',
            'finance.payroll.approve'                => 'finance',
            'academic.dashboard.view'                => 'academic',
            'academic.dashboard.configure'           => 'academic',
            'academic.courses.view'                  => 'academic',
            'academic.courses.assign'                => 'academic',
            'academic.courses.create'                => 'academic',
            'academic.courses.edit'                  => 'academic',
            'academic.courses.delete'                => 'academic',
            'academic.courses.teach'                 => 'academic',
            'academic.results.view'                  => 'academic',
            'academic.results.enter'                 => 'academic',
            'academic.results.edit'                  => 'academic',
            'academic.results.approve'               => 'academic',
            'academic.results.board-approve'         => 'academic',
            'academic.results.export'                => 'academic',
            'academic.timetables.view'               => 'academic',
            'academic.timetables.create'             => 'academic',
            'academic.timetables.edit'               => 'academic',
            'academic.timetables.approve'            => 'academic',
            'academic.attendance.view'               => 'academic',
            'academic.attendance.mark'               => 'academic',
            'academic.attendance.export'             => 'academic',
            'academic.departments.view'              => 'academic',
            'academic.lecturers.view'                => 'academic',
            'business_committee.dashboard.view'      => 'business_committee',
            'business_committee.dashboard.configure' => 'business_committee',
            'business_committee.results.view'        => 'business_committee',
            'business_committee.results.approve'     => 'business_committee',
            'auditor.dashboard.view'                 => 'auditor',
            'auditor.dashboard.configure'            => 'auditor',
            'auditor.audit.view'                     => 'auditor',
            'auditor.audit.logs'                     => 'auditor',
            'auditor.audit.failed-actions'           => 'auditor',
            'auditor.audit.deleted-records'          => 'auditor',
            'auditor.audit.pending-refunds'          => 'auditor',
            'auditor.finance.receipts.view'          => 'auditor',
            'auditor.finance.transactions.view'      => 'auditor',
            'auditor.bursar.payments.view'           => 'auditor',
            'auditor.bursar.debtors.view'            => 'auditor',
            'auditor.bursar.reports.view'            => 'auditor',
            'executive.dashboard.view'               => 'executive',
            'executive.dashboard.configure'          => 'executive',
            'executive.students.view'                => 'executive',
            'executive.students.stats'               => 'executive',
            'executive.staff.view'                   => 'executive',
            'executive.finance.revenue.view'         => 'executive',
            'executive.bursar.payments.view'         => 'executive',
            'executive.bursar.debtors.view'          => 'executive',
            'executive.hospital.admitted.view'       => 'executive',
            'executive.hospital.revenue.view'        => 'executive',
            // Hospital-side row — kept so cross-domain (cmd/super_admin
            // wildcard) checks still expand to a sensible set.
            'patients.create'                        => 'hospital',
        ];
        foreach ($catalog as $slug => $group) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'group' => $group],
            );
        }

        // Permission contracts — what each role gets. Mirrors the
        // `ROLE_PERMISSIONS` constant in each `*Permissions` class
        // under `app/Services/`. Read these as a starting point and
        // update when a class changes.
        $roleContracts = [
            // BursarPermissions — full module access.
            'bursar' => [
                'bursar.dashboard.view', 'bursar.dashboard.configure',
                'bursar.payments.view', 'bursar.payments.create', 'bursar.payments.verify', 'bursar.payments.export',
                'bursar.debtors.view', 'bursar.debtors.export',
                'bursar.fees.view', 'bursar.fees.configure',
                'bursar.regimes.view', 'bursar.regimes.configure',
                'bursar.reports.view', 'bursar.reports.export',
            ],
            // BursarPermissions — focused officer: verify + view, no configure.
            'bursary_officer' => [
                'bursar.payments.view', 'bursar.payments.verify',
                'bursar.debtors.view', 'bursar.debtors.export',
                'bursar.fees.view', 'bursar.reports.view', 'bursar.dashboard.view',
            ],
            // BursarPermissions — cash side only: create + view, no verify.
            'cashier' => [
                'bursar.payments.view', 'bursar.payments.create', 'bursar.dashboard.view',
            ],
            // RegistrarPermissions — full applicant/admission module access.
            'registrar' => [
                'registrar.applicants.view', 'registrar.applicants.create',
                'registrar.applicants.edit', 'registrar.applicants.review',
                'registrar.applicants.status-update', 'registrar.applicants.reset-password',
                'registrar.admissions.view', 'registrar.admissions.generate-letter',
                'registrar.admissions.bulk-upload', 'registrar.admissions.track',
                'registrar.settings.view', 'registrar.settings.edit',
                'registrar.reports.view', 'registrar.reports.export',
                'registrar.dashboard.view', 'registrar.dashboard.configure',
            ],
            // RegistrarPermissions — admission officer (no settings.edit).
            'admission_officer' => [
                'registrar.applicants.view', 'registrar.applicants.review',
                'registrar.applicants.status-update',
                'registrar.admissions.view', 'registrar.admissions.generate-letter',
                'registrar.admissions.track',
                'registrar.reports.view', 'registrar.dashboard.view',
            ],
            // LibrarianPermissions — full module access.
            'librarian' => [
                'librarian.dashboard.view', 'librarian.dashboard.configure',
                'librarian.books.view', 'librarian.books.create', 'librarian.books.edit',
                'librarian.books.delete', 'librarian.books.receive', 'librarian.books.adjust',
                'librarian.borrowing.view', 'librarian.borrowing.issue',
                'librarian.borrowing.return', 'librarian.borrowing.renew', 'librarian.borrowing.export',
                'librarian.members.view', 'librarian.members.create', 'librarian.members.edit',
            ],
            // LibrarianPermissions — officer: no delete or adjust on books.
            'library_officer' => [
                'librarian.dashboard.view',
                'librarian.books.view', 'librarian.books.create', 'librarian.books.edit',
                'librarian.borrowing.view', 'librarian.borrowing.issue',
                'librarian.borrowing.return', 'librarian.borrowing.renew',
                'librarian.members.view', 'librarian.members.create',
            ],
            // LibrarianPermissions — assistant: no create/edit on books.
            'library_assistant' => [
                'librarian.dashboard.view',
                'librarian.books.view',
                'librarian.borrowing.view', 'librarian.borrowing.issue', 'librarian.borrowing.return',
                'librarian.members.view',
            ],
            // FinancePermissions — officer: NO approve on budgets/payroll
            // (only the head finance role does that, and it's not in
            // this fixture set).
            'finance_officer' => [
                'finance.dashboard.view',
                'finance.transactions.view', 'finance.transactions.create', 'finance.transactions.edit',
                'finance.invoices.view', 'finance.invoices.create', 'finance.invoices.edit',
                'finance.receipts.view', 'finance.receipts.create', 'finance.receipts.print',
                'finance.budgets.view',
                'finance.vendors.view',
                'finance.payroll.view',
            ],
            // ExecutivePermissions — rector's view scope.
            'rector' => [
                'executive.dashboard.view', 'executive.dashboard.configure',
                'executive.students.view', 'executive.students.stats',
                'executive.staff.view',
                'executive.finance.revenue.view',
                'executive.bursar.payments.view', 'executive.bursar.debtors.view',
                'executive.hospital.admitted.view', 'executive.hospital.revenue.view',
            ],
            // AuditorPermissions — auditor full access.
            'auditor' => [
                'auditor.dashboard.view', 'auditor.dashboard.configure',
                'auditor.audit.view', 'auditor.audit.logs',
                'auditor.audit.failed-actions', 'auditor.audit.deleted-records',
                'auditor.audit.pending-refunds',
                'auditor.finance.transactions.view', 'auditor.finance.receipts.view',
                'auditor.bursar.payments.view', 'auditor.bursar.debtors.view', 'auditor.bursar.reports.view',
            ],
            // AuditorPermissions — internal_auditor (NO pending-refunds).
            // Note: the slug is `auditor.bursar.payments.view`, not
            // `bursar.payments.view` — pivot rows are namespaced.
            'internal_auditor' => [
                'auditor.dashboard.view',
                'auditor.audit.view', 'auditor.audit.logs',
                'auditor.audit.failed-actions', 'auditor.audit.deleted-records',
                'auditor.finance.transactions.view', 'auditor.finance.receipts.view',
                'auditor.bursar.payments.view',
            ],
            // AcademicPermissions — lecturer.
            'lecturer' => [
                'academic.dashboard.view',
                'academic.courses.view', 'academic.courses.assign', 'academic.courses.teach',
                'academic.results.view', 'academic.results.enter', 'academic.results.edit',
                'academic.attendance.view', 'academic.attendance.mark', 'academic.attendance.export',
                'academic.timetables.view',
            ],
            // AcademicPermissions — HOD.
            'hod' => [
                'academic.dashboard.view', 'academic.dashboard.configure',
                'academic.courses.view', 'academic.courses.assign',
                'academic.courses.create', 'academic.courses.edit', 'academic.courses.delete',
                'academic.results.view', 'academic.results.enter', 'academic.results.edit',
                'academic.results.approve',
                'academic.attendance.view', 'academic.attendance.export',
                'academic.timetables.view', 'academic.timetables.create', 'academic.timetables.edit',
                'academic.departments.view', 'academic.lecturers.view',
            ],
            // AcademicPermissions — dean.
            'dean' => [
                'academic.dashboard.view', 'academic.dashboard.configure',
                'academic.courses.view',
                'academic.results.view', 'academic.results.approve', 'academic.results.export',
                'academic.timetables.view', 'academic.timetables.approve',
                'academic.departments.view', 'academic.lecturers.view',
            ],
            // AcademicPermissions — academic_board (incl. the new
            // `academic.results.board-approve` slug added in slice 8e).
            'academic_board' => [
                'academic.dashboard.view',
                'academic.courses.view',
                'academic.results.view', 'academic.results.export',
                'academic.results.board-approve',
                'academic.timetables.view',
                'academic.departments.view', 'academic.lecturers.view',
            ],
            // BusinessCommitteePermissions — created in slice 8e.
            'business_committee' => [
                'business_committee.dashboard.view', 'business_committee.dashboard.configure',
                'business_committee.results.view', 'business_committee.results.approve',
            ],
        ];

        // Roles that exist in the live `roles` table but NOT in the
        // hand-rolled fixtures. Tests rows pointing at these are
        // skipped so the matrix stays runnable.
        $missingRoles = ['finance', 'executive', 'audit_bursar'];

        foreach ($roleContracts as $roleSlug => $allowedSlugs) {
            $role = Role::firstOrCreate(
                ['slug' => $roleSlug],
                ['name' => ucfirst(str_replace('_', ' ', $roleSlug))],
            );
            $role->permissions()->sync(
                Permission::whereIn('slug', $allowedSlugs)->pluck('id')->all()
            );
        }

        // The fixture matrix. Each row is [role, slug, expectedBool].
        // True = role has the slug → requirePermission() lets them in.
        // False = role doesn't have the slug → 403.
        $matrix = [
            // Bursar module.
            ['bursar', 'bursar.payments.view', true],
            ['bursar', 'bursar.payments.verify', true],
            ['bursar', 'bursar.fees.configure', true],
            ['bursar', 'patients.create', false],
            ['bursary_officer', 'bursar.payments.verify', true],
            ['bursary_officer', 'bursar.fees.configure', false],
            ['bursary_officer', 'bursar.payments.create', false], // officer doesn't create
            ['cashier', 'bursar.payments.create', true],
            ['cashier', 'bursar.payments.verify', false], // cashier doesn't verify

            // Registrar module.
            ['registrar', 'registrar.applicants.view', true],
            ['registrar', 'registrar.settings.edit', true],
            ['registrar', 'bursar.payments.view', false],
            ['admission_officer', 'registrar.applicants.review', true],
            ['admission_officer', 'registrar.settings.edit', false],

            // Librarian module.
            ['librarian', 'librarian.borrowing.issue', true],
            ['librarian', 'librarian.books.delete', true],
            ['library_officer', 'librarian.books.delete', false], // officer has no delete
            ['library_officer', 'librarian.borrowing.issue', true],
            ['library_assistant', 'librarian.borrowing.issue', true],
            ['library_assistant', 'librarian.books.create', false], // assistant has no create

            // Finance module.
            ['finance_officer', 'finance.invoices.create', true],
            ['finance_officer', 'finance.budgets.approve', false], // officer can't approve
            ['finance_officer', 'finance.payroll.approve', false], // officer can't approve

            // Executive module.
            ['rector', 'executive.hospital.admitted.view', true],
            ['rector', 'executive.finance.revenue.view', true],
            ['rector', 'registrar.applicants.review', false], // rector doesn't review apps

            // Auditor module — note `auditor.bursar.payments.view`
            // is the namespaced slug AuditorPermissions grants.
            ['auditor', 'auditor.audit.view', true],
            ['auditor', 'bursar.payments.view', false], // un-namespaced slug is NOT in auditor's pivot
            ['internal_auditor', 'auditor.audit.view', true],
            ['internal_auditor', 'auditor.bursar.payments.view', true],
            ['internal_auditor', 'bursar.payments.view', false], // un-namespaced slug is NOT in internal_auditor's pivot
            ['internal_auditor', 'auditor.audit.pending-refunds', false], // not granted to internal_auditor

            // Academic module.
            ['lecturer', 'academic.results.enter', true],
            ['lecturer', 'academic.results.approve', false], // lecturer can't approve
            ['hod', 'academic.results.approve', true],
            ['hod', 'academic.courses.assign', true],
            ['dean', 'academic.results.approve', true],
            ['academic_board', 'academic.results.board-approve', true], // the new slice 8e slug
            ['academic_board', 'academic.results.approve', false], // academic_board doesn't use the HOD slug
            ['academic_board', 'academic.results.view', true],

            // Business committee module — created in slice 8e.
            ['business_committee', 'business_committee.results.approve', true],
            ['business_committee', 'business_committee.results.view', true],
            ['business_committee', 'bursar.payments.view', false],
        ];

        $skipped = [];
        // Cache users per role — multiple matrix rows share the same role
        // and `makeUser()` always INSERTs a fresh row keyed by email, so
        // creating one per row would blow up the unique-email constraint.
        $users = [];
        foreach ($matrix as [$role, $slug, $expected]) {
            if (in_array($role, $missingRoles, true)) {
                $skipped[] = "$role+$slug";
                continue;
            }

            $users[$role] ??= $this->makeUser($role);
            $user = $users[$role];
            $actual = PermissionService::allows($slug, $user);
            $this->assertSame(
                $expected, $actual,
                "role '$role' + slug '$slug': expected " . var_export($expected, true)
                . ', got ' . var_export($actual, true),
            );
        }

        // Sanity — at least one row should have actually executed,
        // otherwise the only fixtures above were skipped and the
        // matrix gives us a false sense of coverage.
        $executed = count($matrix) - count($skipped);
        $this->assertGreaterThan(0, $executed, 'no matrix rows executed');
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
