<?php

namespace App\Services\Permissions;

use App\Services\Academic\AcademicPermissions;
use App\Services\Auditor\AuditorPermissions;
use App\Services\Bursar\BursarPermissions;
use App\Services\BusinessCommittee\BusinessCommitteePermissions;
use App\Services\Executive\ExecutivePermissions;
use App\Services\Finance\FinancePermissions;
use App\Services\Hospital\HospitalPermissions;
use App\Services\Librarian\LibrarianPermissions;
use App\Services\Registrar\RegistrarPermissions;

/**
 * The complete list of domain-specific `*Permissions` classes.
 *
 * The seeders iterate this list to populate the `permissions` and
 * `role_permissions` tables. Each class contributes its own
 * `ROLE_PERMISSIONS` constant; the seeder unions the contributions
 * per role slug so a role that appears in multiple domains (e.g.
 * `bursar` may show up in HospitalPermissions AND BursarPermissions)
 * ends up with the union of every permission the catalogue grants.
 *
 * `RolePermissionsSeeder::run()` is the single source of truth for
 * how this union happens — see its pseudocode in the slice plan.
 *
 * Order: `HospitalPermissions` stays FIRST because the hospital
 * permission layout is the canonical super_admin/admin mapping —
 * pre-slice, super_admin's grants were 100% hospital. Adding more
 * domains only widens the wildcard expansion, never shrinks it.
 */
final class PermissionCatalog
{
    /**
     * @return array<int, class-string>
     */
    public static function all(): array
    {
        return [
            HospitalPermissions::class,
            BursarPermissions::class,
            RegistrarPermissions::class,
            LibrarianPermissions::class,
            FinancePermissions::class,
            AcademicPermissions::class,
            AuditorPermissions::class,
            ExecutivePermissions::class,
            BusinessCommitteePermissions::class,
        ];
    }
}