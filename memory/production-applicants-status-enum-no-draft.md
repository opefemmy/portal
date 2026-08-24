---
name: production-applicants-status-enum-no-draft
description: Production applicants.status ENUM is (pending, reviewing, admitted, rejected) — no 'draft'. The four FK columns school_id/department_id/programme_id/session_id AND application_number are non-nullable in the repo migration. Signup-time firstOrCreate must leave them NULL.
metadata:
  type: project
---

Production `applicants.status` is `ENUM('pending','reviewing','admitted','rejected')`. The repo migration `2024_01_01_000008` declares `school_id`, `department_id`, `programme_id`, `session_id` as non-nullable foreign keys and `application_number` as non-nullable UNIQUE varchar.

**Why:** `Auth\RegisterController::registerApplicant()` seeds an Applicant row at signup time, before the applicant has picked a programme or submitted the application form. Writing `status='draft'` or omitting the four FKs / `application_number` 500s with `Field 'X' doesn't have a default value` / `Data truncated for column 'status'`.

**How to apply:** The canonical "not submitted yet" sentinel is `application_number IS NULL` — never write `status='draft'`. Signup-time seeding (`firstOrCreate`) must:
- Leave `application_number` NULL
- Leave the four programme FKs NULL
- Write `status='pending'` (which is the schema default anyway)

`ApplicationController::submitApplication()` then generates + assigns `application_number` and the four FKs on first real submission. The 2026_08_24_000001 migration relaxes the four FKs + `application_number` to NULLable (production is a no-op via `down()`). All `status === 'draft'` / `status !== 'draft'` / `in_array(..., ['draft', ...])` references across `ApplicationController` + `applicant/dashboard.blade.php` + `applicant/application.blade.php` were rewritten to use `empty($applicant->application_number)` instead.

If you add a new "draft" sentinel to a future flow, use a separate column (e.g. `is_draft` boolean) — the status ENUM is locked.
