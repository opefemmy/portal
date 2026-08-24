# Dean Role — Definition & Scope

The Dean is the academic head of a single **school** within the institution.
A Dean is the immediate superior of every HOD in their school and the
gatekeeper who signs off on results before they go to the Business Committee
and Academic Board for final approval.

## Who the Dean is

- One user row, role slug `dean`, with `users.school_id` set to the school
  they oversee.
- The school is the institution's primary academic subdivision (e.g.
  "School of Engineering", "School of Management"). A Dean cannot cross
  schools — every query, widget and form is scoped to their `school_id`.
- Each school contains multiple **departments**, each department runs
  multiple **programmes**, and a programme hosts students across
  multiple **levels** (100L → 400L in this institution).

## What a Dean can do

### 1. Result approval (the academic pipeline)

Dean sits at stage 2 of the result pipeline:

```
Lecturer → HOD → Dean → Business Committee → Academic Board → Final
pending_approval → approved → approved_by_dean → approved_by_business → approved_final
```

- **Inbox**: `/dean/results` lists `status = 'approved'` results from the
  HODs in their school, sorted by `latest()`.
- **Approve**: `PUT /dean/results/{result}/approve` → sets status to
  `approved_by_dean`, stamps `approved_by` / `approved_at`, then the
  Business Committee picks it up next.
- **Reject**: `PUT /dean/results/{result}/reject` → sets status to
  `rejected` (terminal — lecturer must re-upload).
- **Bulk approve / bulk reject**: `POST /dean/results/bulk-approve` /
  `bulk-reject` — same flow, many results at once.
- **Signing Page**: `GET /dean/results/signing-page` — printable list of
  results the Dean personally signed off, with the same six-role
  signing block (`_signing_block.blade.php`) used elsewhere.
- All routes are gated by `academic.results.view` / `approve` / `reject`
  permissions on `Dean/ResultController` and the corresponding route
  middleware.

### 2. Department & programme oversight

- `GET /dean/departments` — every department in the Dean's school with
  programme and student head-counts. Clickable counts jump straight to
  `/dean/students?department_id={id}`.
- The Dean's view of programmes is computed via departments — programmes
  exist only as children of departments in this institution, so the
  Dean sees the union of `programmes` over `departments where
  school_id = auth()->user()->school_id`.

### 3. Student roster

- `GET /dean/students` — paginated list of every student whose
  `students.school_id` matches the Dean's school.
- Filters: search (name / email / matric), department, programme,
  level. Filters compose and survive pagination via
  `withQueryString()`.
- Programme filter is **always** intersected with the school's
  departments so a Dean cannot probe a programme from another school by
  guessing its id.

### 4. Dashboard tiles (`/dean/dashboard`)

Five tiles, all scoped to the Dean's school:

| Tile | Source |
|---|---|
| My School | `schools.name` for `users.school_id` |
| Departments | `Department::where('school_id', auth()->user()->school_id)->count()` |
| Programmes | `Programme::whereIn('department_id', schoolDeptIds)->count()` |
| Students | `Student::where('school_id', auth()->user()->school_id)->count()` |
| Pending Results | `Result::whereIn('course_id', schoolCourseIds)->where('status', 'approved')->count()` |

When `users.school_id` is missing (misconfigured user), every tile
falls back to `0` (or `—` for the school name) so a Dean never sees
the institution-wide number.

## What a Dean cannot do

- **Cross schools**: any record (result, student, department,
  programme) outside their `school_id` returns 403 / 404.
- **Skip stages**: cannot act on `status = 'pending_approval'` (HOD
  hasn't signed) or `approved_by_dean` and beyond (BC/AB has taken
  ownership). `Dean\ResultController::assertCanActOn()` enforces
  this with HTTP 409.
- **Bypass permissions**: `EnforcesPermission::requirePermission()`
  is on every Dean action and `permission:…` middleware is on every
  Dean route. The Dean role holds no `*` wildcard — only the slugs
  listed in `AcademicPermissions::dean`:
  `academic.courses.view`, `academic.results.{view,approve,reject,export}`,
  `academic.timetables.{view,approve}`, `academic.departments.view`,
  `academic.lecturers.view`, `academic.students.view`,
  `academic.dashboard.{view,configure}`.

## Wiring summary

| Concern | Where |
|---|---|
| Role slug / pivot | `database/seeders/RolePermissionsSeeder.php` (reads `AcademicPermissions::dean`) |
| Login redirect | `app/Http/Controllers/Auth/LoginController.php:128` — `dean → /dean/dashboard` |
| Route prefix | `routes/web.php:1167-1195` — `prefix('dean')->middleware(['auth','role:dean'])` |
| Controller | `app/Http/Controllers/Dean/ResultController.php` (approve/reject/bulk/signing-page), `Dean/StudentController.php` (index), `Dean/DepartmentController.php` (index) |
| Widgets | `app/Providers/AppServiceProvider.php::registerDeanWidgets()` (5 scoped tiles) |
| Sidebar | `resources/views/layouts/sidebar.blade.php:943-973` — Dashboard, Results Approval, Departments, Students, Customize Dashboard |
| Views | `resources/views/dean/{dashboard,students,departments}.blade.php` + `resources/views/dean/results/{index,signing-page}.blade.php` |
| Signing block | shared partial `resources/views/admin/transcripts/_signing_block.blade.php` |