---
name: over-broad-grant-audit
description: Audit findings — ict_admin and staff grant lists vs route allowlists, cross-role reach gaps
metadata:
  type: project
---

# Over-broad grant audit (slice 8j-audit)

## Critical finding: ict_admin reaches /bursar/* and /finance/* but has 0 slugs there

`ict_admin` is listed in the role: middleware for two route groups
but has no slug grants for either:

- `routes/web.php:1336` — `/bursar/*` group lists ict_admin
- `routes/finance.php:23` — `/finance/*` group lists ict_admin
  (comment lines 13-16 says "ICT admin... need to be able to open
   finance screens for read-only reconciliation work")

But `AdminPermissions::ROLE_PERMISSIONS['ict_admin']` only has
admin.* + maintenance.* slugs — no `bursar.*` or `finance.*`.

**Result:** any ICT admin who clicks a bursar/finance route link
is 403'd at the controller trait gate (defence-in-depth fires
correctly), but they were admitted by the route's role: middleware.
The comment at routes/finance.php:13-16 implies read-only
reconciliation access was the intent.

**Two options:**
1. Add ict_admin → read-only bursar.* + finance.* slugs
   (matches comment intent, no route change needed)
2. Remove ict_admin from /bursar/* and /finance/* role: lists
   (matches current grant list, route middleware becomes the gate)

Recommend option 1 — the comment at finance.php:13-16 is
documented intent. Need to enumerate which read-only slugs.

**Why:** ICT admins historically had read access to bursar/finance
screens for reconciliation. The 8i-controller + 8i-routes work
wired `permission:` middleware and trait gates everywhere, but
didn't back-fill the catalogue grants. Result: the catalogue says
ict_admin has 0 finance/bursar slugs, but the route says they
should be able to enter the route group.

**How to apply:** before shipping any further 8i-* slice, decide
which audience is correct — either grant the slugs or remove
from the role: lists. Don't leave the inconsistency.

## Other findings (lower-impact)

- `staff` is NOT in /bursar/* or /finance/* role: lists — correct.
- `ict_admin` reaches /admin/* (122 bindings) and has all 34 admin
  slugs — correct.
- `hospital_admin` (HospitalPermissions) reaches pharmacy/lab/
  appointments/* routes (read-only intent) but only has view slugs.
  Trait gate 403s on action routes — correct.
- `store_keeper` vs `inventory_officer` overlap: both reach
  /hospital/pharmacy/* (line 255). inventory_officer lacks
  pharmacy.receive/adjust/expire — they're 403'd on those routes
  by the trait gate. Probably correct (inventory_officer is more
  of an auditor), but worth a confirm.
- Academic roles (lecturer/HOD/dean/academic_board) — all slugs
  match their route groups correctly.

## Cross-references

- AdminPermissions: app/Services/Admin/AdminPermissions.php
- MaintenancePermissions: app/Services/Admin/MaintenancePermissions.php
- FinancePermissions: app/Services/Finance/FinancePermissions.php
- BursarPermissions: app/Services/Bursar/BursarPermissions.php
- HospitalPermissions: app/Services/Hospital/HospitalPermissions.php
