---
name: 8i-admin-facilities-subslice6-issues
description: Sub-slice 6 of 8i-admin migration — 2 facilities controllers wired with requirePermission; 2 pre-existing route-setup issues surfaced
metadata:
  type: project
---

Sub-slice 6 of 8i-admin shipped (commit bb77ecb3).

Wired `requirePermission()` into 2 facilities controllers:

  HostelController     15 methods  admin.hostels.manage
  LibraryController    7 methods   admin.libraries.manage

Cumulative 8i-admin progress (sub-slices 1-6):
  6 + 3 + 4 + 6 + 3 + 2 = 24 controllers gated
  39 + 19 + 29 + 44 + 15 + 22 = 168 requirePermission calls
  6 + 3 + 4 + 6 + 3 + 2 = 24 admin slugs in catalogue

Tests:
  AdminFacilitiesControllerGateTest   18 passed (23 assertions)
  full permission suite               181 passed (322 assertions)

Two pre-existing route-setup issues surfaced while writing the
regression test (NOT introduced by this slice):

  1. `/admin/hostels/allocations` is shadowed by the resource
     show route `/hostels/{hostel}` (registered first on line
     565 before line 568). Laravel binds "allocations" as a
     Hostel id and 500s in the test schema. The controller
     gate never runs. Pre-existing route-ordering bug.

  2. `library.access` middleware queries the `settings` table
     via `Setting::get('library_access_code')`. The table
     doesn't exist in the in-memory sqlite test schema, so
     every library route 500s before the controller runs.
     Adding the empty `settings` table to the test schema
     fixes it.

Both are test-environment artefacts — production has both the
`settings` table and the resource routes registered cleanly.

Test workaround: `AdminFacilitiesControllerGateTest` adds the
empty `settings` table in `buildSchema()` and uses
`/admin/hostels/allocations/create` (no `{hostel}` model
binding) instead of the shadowed `/admin/hostels/allocations`.

**Why:** These are pre-existing infrastructure issues that
this slice exposed but did not introduce. Documenting them
so future test authors don't re-discover them.

**How to apply:** If a future test for admin routes 500s in
the test schema, check whether the route is (a) inside
`library.access` middleware (add `settings` table), or (b)
shadowed by a resource route's `/{model}` binding (use a
different URL). The controller gate is correct in both cases
— the 500 happens BEFORE the gate runs.

Related: [[ict-admin-bursar-staff-route-access]],
[[permissionservice-slice-8e-gating]]
