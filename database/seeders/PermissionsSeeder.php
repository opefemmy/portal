<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Services\Permissions\PermissionCatalog;
use Illuminate\Database\Seeder;

/**
 * Populate the `permissions` table from every domain's
 * `*Permissions::ROLE_PERMISSIONS` constant.
 *
 * Iterates `PermissionCatalog::all()` so every domain — hospital,
 * bursar, registrar, librarian, finance, academic, auditor,
 * executive — contributes its permission slugs to the catalogue.
 * The wildcard `'*'` marker is NOT stored as a permission row; it's
 * a role-level flag honoured by `PermissionService::allPermissionsFor()`.
 *
 * Idempotent: every permission is created with `firstOrCreate` keyed
 * on the slug, so re-running the seeder is a no-op.
 *
 * Group inference: every permission slug has a dotted prefix that
 * maps to a domain ("patients." → "hospital", "bursar." → "bursar").
 * New domains only need an entry in the `PREFIX_TO_GROUP` map below.
 */
class PermissionsSeeder extends Seeder
{
    /**
     * Permission-slug prefix → group label.
     *
     * The first matching prefix wins. Keep this map small and
     * obvious — anything not matched falls through to `null` and
     * ends up in the ungrouped bucket. New domains added by
     * `PermissionCatalog::all()` must extend this map.
     */
    private const PREFIX_TO_GROUP = [
        // Hospital prefixes (existing).
        'patients'           => 'hospital',
        'external-patients'  => 'hospital',
        'appointments'       => 'hospital',
        'consultations'      => 'hospital',
        'pharmacy'           => 'hospital',
        'lab'                => 'hospital',
        'radiology'          => 'hospital',
        'prescriptions'      => 'hospital',
        'referrals'          => 'hospital',
        'visits'             => 'hospital',
        'vitals'             => 'hospital',
        'wards'              => 'hospital',
        'beds'               => 'hospital',
        'monitoring'         => 'hospital',
        'medications'        => 'hospital',
        'discharge'          => 'hospital',
        'timeline'           => 'hospital',
        'records'            => 'hospital',
        'billing'            => 'hospital',
        'staff'              => 'hospital',
        'duty'               => 'hospital',
        'inventory'          => 'hospital',
        'attendance'         => 'hospital',
        'dispensations'      => 'hospital',   // typo-safety for reports.dispensation
        'search'             => 'hospital',
        'reports'            => 'hospital',
        'audit'              => 'hospital',

        // Bursar prefixes.
        'payments'           => 'bursar',
        'fees'               => 'bursar',
        'debtors'            => 'bursar',
        'regimes'            => 'bursar',
        'bursar'             => 'bursar',     // explicit catch-all for bursar-prefixed slugs

        // Registrar prefixes.
        'applicants'         => 'registrar',
        'admissions'         => 'registrar',
        'registrar'          => 'registrar',  // explicit catch-all

        // Librarian prefixes.
        'books'              => 'librarian',
        'borrowing'          => 'librarian',
        'members'            => 'librarian',
        'library'            => 'librarian',
        'librarian'          => 'librarian',  // explicit catch-all

        // Finance prefixes.
        'transactions'       => 'finance',
        'invoices'           => 'finance',
        'receipts'           => 'finance',
        'budgets'            => 'finance',
        'vendors'            => 'finance',
        'payroll'            => 'finance',
        'finance'            => 'finance',    // explicit catch-all

        // Academic prefixes.
        'students'           => 'academic',
        'courses'            => 'academic',
        'results'            => 'academic',
        'timetables'         => 'academic',
        'departments'        => 'academic',
        'lecturers'          => 'academic',
        'programs'           => 'academic',
        'academic'           => 'academic',   // explicit catch-all

        // Executive prefixes.
        'executive'          => 'executive',

        // Auditor prefixes (the auditor.* family — note that the
        // generic 'audit' prefix above stays in 'hospital' for
        // backwards-compatibility with HospitalPermissions slugs
        // like `audit.view`, `audit.logs`).
        'auditor'            => 'auditor',

        // Business committee prefixes (slice 8e).
        'business_committee' => 'business_committee',

        // Cross-cutting prefixes (slice 8i-controller). The admin and
        // student audiences sit at the top of the academic flow but
        // don't fit any single domain's bucket — give them their own
        // group label so `permissions.group` is non-null on rows that
        // only ever appear in their respective audience's role grants.
        'admin'              => 'admin',
        'student'            => 'student',
        // Applicant prefix (slice 8i-applicant). The applicant audience
        // is its own thing — separate from the registrar side which
        // reviews applicant data, this is the applicant-self-service
        // surface.
        'applicant'          => 'applicant',

        // Maintenance prefix (slice 8i-maintenance). The system
        // maintenance surface (run-migrations, clear-cache, create-backup,
        // etc.) gets its own group label so the permission-scanner UI
        // can filter by audience.
        'maintenance'        => 'maintenance',
    ];

    public function run(): void
    {
        $byGroup = [];

        // Iterate every domain's *Permissions class via the catalog
        // and dedupe slugs into group buckets.
        foreach (PermissionCatalog::all() as $permissionsClass) {
            foreach ($permissionsClass::ROLE_PERMISSIONS as $roleSlug => $perms) {
                foreach ($perms as $slug) {
                    if ($slug === '*') {
                        continue;
                    }
                    $group = $this->inferGroup($slug);
                    // Use the slug as the key to dedupe — multiple
                    // roles may claim the same permission.
                    $byGroup[$group][$slug] = [
                        'name' => $slug,
                        'slug' => $slug,
                        'group' => $group,
                    ];
                }
            }
        }

        foreach ($byGroup as $rows) {
            foreach ($rows as $r) {
                Permission::firstOrCreate(['slug' => $r['slug']], $r);
            }
        }
    }

    /**
     * Map a permission slug to a domain group. Uses the dotted prefix
     * (`bursar.payments.view` → `bursar` → `bursar`). Unknown prefixes
     * land in the `null` bucket — they're still seeded, just without
     * a group label.
     */
    private function inferGroup(string $slug): ?string
    {
        $prefix = strstr($slug, '.', true);
        if ($prefix === false) {
            return null;
        }
        return self::PREFIX_TO_GROUP[$prefix] ?? null;
    }
}