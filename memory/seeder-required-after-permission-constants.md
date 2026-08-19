---
name: seeder-required-after-permission-constants
description: After editing *Permissions::ROLE_PERMISSIONS constants you MUST run PermissionsSeeder + RolePermissionsSeeder — the catalogue constants are source-of-truth, DB pivot is derived
metadata:
  type: feedback
---

## Run seeders after every catalogue change

`App\Services\*\*Permissions::ROLE_PERMISSIONS` is the source
of truth. The `permissions` table and `role_permissions` pivot
are derived from those constants via `PermissionsSeeder` and
`RolePermissionsSeeder`.

If you add a slug to any `*Permissions::ROLE_PERMISSIONS`
constant but don't run both seeders on the live DB, the
permission: middleware and trait gates will 403 the role at
every endpoint they should reach. The constants look correct
in code; the live DB stays out of sync.

**Symptom**: an `ict_admin` / `staff` / `admin` / etc. user hits
a 403 on an endpoint they should reach. The role: middleware
passes them (correct), but the permission: middleware 403s
them because their role_permissions pivot has 0 entries.

**Fix**:

```bash
php artisan db:seed --class=PermissionsSeeder --force
php artisan db:seed --class=RolePermissionsSeeder --force
```

Both are idempotent. `PermissionsSeeder` does `firstOrCreate`
keyed on slug. `RolePermissionsSeeder` does `sync()` per role,
which means **admin-UI-granted perms not in the catalogue are
lost** — confirm with the user before running.

**Why**: the 8j-audit slice (commit 40372254) added 12 read-only
bursar/finance slugs to ict_admin's grant list. The constants
were correct, but the live DB still had 0 entries for ict_admin
because the seeders weren't re-run after every previous 8i-admin
sub-slice either. The 403 on /admin/dashboard surfaced for any
ict_admin/staff user.

**How to apply**: after every commit that touches a
`*Permissions::ROLE_PERMISSIONS` constant, immediately run
both seeders. Better still: add this to the slice plan
verification checklist so it doesn't get skipped.

Related: [[permissionservice-catalogue-expansion]],
[[over-broad-grant-audit]]
