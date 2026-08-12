<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Services\Hospital\HospitalPermissions;
use Illuminate\Database\Seeder;

/**
 * Populate the `permissions` table from the existing
 * `HospitalPermissions::ROLE_PERMISSIONS` constant.
 *
 * Idempotent: every permission is created with `firstOrCreate` keyed
 * on the slug, so re-running the seeder is a no-op. The wildcard
 * `'*'` marker is NOT stored as a permission row — it's a role-level
 * flag honoured by PermissionService.
 *
 * Group inference: every permission slug has a dotted prefix that
 * maps to a domain ("patients." → "hospital", "finance." → "bursar").
 * New domains (e.g. "registrar") only need an entry in the
 * `PREFIX_TO_GROUP` map below.
 */
class PermissionsSeeder extends Seeder
{
    /**
     * Permission-slug prefix → group label.
     *
     * The first matching prefix wins. Keep this map small and
     * obvious — anything not matched falls through to `null` and
     * ends up in the ungrouped bucket.
     */
    private const PREFIX_TO_GROUP = [
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
    ];

    public function run(): void
    {
        $byGroup = [];

        foreach (HospitalPermissions::ROLE_PERMISSIONS as $roleSlug => $perms) {
            foreach ($perms as $slug) {
                if ($slug === '*') {
                    continue;
                }
                $group = $this->inferGroup($slug);
                // Use the slug as the key to dedupe — multiple roles
                // may claim the same permission.
                $byGroup[$group][$slug] = [
                    'name' => $slug,
                    'slug' => $slug,
                    'group' => $group,
                ];
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
     * (`patients.create` → `patients` → `hospital`). Unknown prefixes
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
