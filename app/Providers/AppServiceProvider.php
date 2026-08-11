<?php

namespace App\Providers;

use App\Services\Dashboard\WidgetDefinition;
use App\Services\Dashboard\WidgetRegistry;
use App\Services\Hospital\HospitalPermissions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the catalogue of dashboard widgets. Resolved per-user
        // by App\Services\Dashboard\DashboardResolver, which uses these
        // definitions unless the user has overridden them via
        // `dashboard_widgets`.
        $this->registerDashboardWidgets();

        // Optimize model relationships - eager load by default
        Model::preventLazyLoading(false);

        // The {consultation} route param resolves to HospitalMedicalRecord.
        Route::bind('consultation', function ($value) {
            return \App\Models\Hospital\HospitalMedicalRecord::findOrFail($value);
        });

        Response::macro('download_csv', function ($data, $filename, $headers = []) {
            $csv = implode(',', $headers) . "\n";
            foreach ($data as $row) {
                $csv .= implode(',', array_map(function ($item) {
                    return '"' . str_replace('"', '""', $item) . '"';
                }, $row)) . "\n";
            }

            return Response::make($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        });

        // @permission('patients.create') ... @endpermission
        // Renders the block only if the current user has the named permission.
        Blade::directive('permission', function (string $expression) {
            return "<?php if (\\App\\Services\\Hospital\\HospitalPermissions::allows({$expression})): ?>";
        });
        Blade::directive('endpermission', function () {
            return '<?php endif; ?>';
        });

        // @anypermission(['patients.view','pharmacy.view']) ... @endanypermission
        // Renders the block if the user has any of the listed permissions.
        Blade::directive('anypermission', function (string $expression) {
            return "<?php if (\\App\\Services\\Hospital\\HospitalPermissions::allowsAny([{$expression}])): ?>";
        });
        Blade::directive('endanypermission', function () {
            return '<?php endif; ?>';
        });
    }

    /**
     * Register every dashboard widget the system ships with.
     *
     * Each closure runs when a widget is resolved for rendering, so
     * database access is deferred until the dashboard actually needs it.
     * `appliesToRoles` mirrors the role list on the middleware that
     * gates the admin dashboard — if we add a widget the admin route
     * allows but the registry doesn't list for this role, it'll be
     * invisible to that user by default.
     */
    private function registerDashboardWidgets(): void
    {
        $adminRoles = ['super_admin', 'admin', 'ict_admin', 'staff'];

        // --- Stat tiles -------------------------------------------------
        WidgetRegistry::register(new WidgetDefinition(
            'admin.total_students', 'Total Students', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Student::count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-user-graduate',
                'href' => route('admin.students.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.total_applicants', 'Total Applicants', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Applicant::count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-user-plus',
                'href' => route('registrar.applicants'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.total_courses', 'Total Courses', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Course::count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-book',
                'href' => route('admin.courses.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.total_schools', 'Total Schools', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\School::count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-school',
                'href' => route('admin.schools.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.total_departments', 'Total Departments', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Department::count(),
                'format' => 'number',
                'color' => 'secondary',
                'icon' => 'fas fa-building-columns',
                'href' => route('admin.departments.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.total_users', 'Total Users', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\User::count(),
                'format' => 'number',
                'color' => 'dark',
                'icon' => 'fas fa-users',
                'href' => route('admin.users.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.total_staff', 'Total Staff', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\User::whereHas('role', function ($q) {
                    $q->whereIn('slug', ['lecturer', 'hod', 'dean', 'registrar', 'bursar', 'admin', 'staff']);
                })->count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-users-cog',
                'href' => route('admin.staff.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.pending_applications', 'Pending Applications', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Applicant::where('status', 'pending')->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-clock',
                'href' => route('registrar.applications.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.admitted_students', 'Admitted Students', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Applicant::where('status', 'admitted')->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-user-check',
                'href' => route('registrar.admission'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.registered_courses', 'Registered Courses', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\StudentCourse::where('status', 'registered')->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-book-open',
                'href' => route('admin.course-registrations.index'),
            ],
            'widgets.stat-card'
        ));

        // --- Money widgets (depend on the current session) --------------
        WidgetRegistry::register(new WidgetDefinition(
            'admin.total_expected_fees', 'Total Expected Fees', 'stat', $adminRoles,
            function () {
                $sessionId = \App\Models\Session::getCurrentSession()?->id;
                return [
                    'value' => \App\Models\Fee::where('session_id', $sessionId)->sum('amount'),
                    'format' => 'currency',
                    'color' => 'warning',
                    'icon' => 'fas fa-calculator',
                    'href' => route('admin.fees.index'),
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.total_payments', 'Total Payments', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Payment::whereIn('status', ['completed', 'verified'])->sum('amount'),
                'format' => 'currency',
                'color' => 'success',
                'icon' => 'fas fa-dollar-sign',
                'href' => route('bursar.payments'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.outstanding_fees', 'Outstanding Fees', 'stat', $adminRoles,
            function () {
                $sessionId = \App\Models\Session::getCurrentSession()?->id;
                $expected = \App\Models\Fee::where('session_id', $sessionId)->sum('amount');
                $paid = \App\Models\Payment::whereIn('status', ['completed', 'verified'])->sum('amount');
                return [
                    'value' => $expected - $paid,
                    'format' => 'currency',
                    'color' => 'danger',
                    'icon' => 'fas fa-exclamation-circle',
                    'href' => route('bursar.payments'),
                ];
            },
            'widgets.stat-card'
        ));

        // --- Tables ----------------------------------------------------
        WidgetRegistry::register(new WidgetDefinition(
            'admin.recent_applicants', 'Recent Applications', 'table', $adminRoles,
            fn() => [
                'title' => 'Recent Applications',
                'icon' => 'fas fa-user-plus',
                'headers' => ['Name', 'Department', 'Status', 'Date'],
                'rows' => \App\Models\Applicant::with(['user', 'department'])
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(fn($a) => [
                        $a->user->name ?? 'N/A',
                        $a->department->code ?? 'N/A',
                        $a->status,
                        optional($a->created_at)->format('d M Y') ?? 'N/A',
                    ])
                    ->all(),
                'colspan' => 4,
                'empty_message' => 'No recent applications',
            ],
            'widgets.table-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.recent_payments', 'Recent Payments', 'table', $adminRoles,
            fn() => [
                'title' => 'Recent Payments',
                'icon' => 'fas fa-dollar-sign',
                'headers' => ['Student', 'Fee', 'Amount', 'Status'],
                'rows' => \App\Models\Payment::with(['student.user', 'fee'])
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function ($p) {
                        $student = $p->student && $p->student->user
                            ? $p->student->user->name
                            : ($p->student_type === 'applicant'
                                ? ($p->payer_name ?? 'Applicant')
                                : ($p->payer_name ?? 'N/A'));
                        $fee = $p->fee->name
                            ?? $p->fee_type
                            ?? $p->payment_purpose
                            ?? ($p->payer_id ? 'Payment' : 'N/A');
                        return [
                            $student,
                            $fee,
                            '₦' . number_format($p->amount, 2),
                            $p->status,
                        ];
                    })
                    ->all(),
                'colspan' => 4,
                'empty_message' => 'No recent payments',
            ],
            'widgets.table-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.students_by_level', 'Students by Level', 'table', $adminRoles,
            fn() => [
                'title' => 'Students by Level',
                'icon' => 'fas fa-layer-group',
                'headers' => ['Level', 'Count'],
                'rows' => \App\Models\Student::select('level', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                    ->groupBy('level')
                    ->get()
                    ->map(fn($r) => ['Level ' . ($r->level ?? 'N/A'), $r->count])
                    ->all(),
                'colspan' => 2,
                'empty_message' => 'No levels recorded',
            ],
            'widgets.table-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'admin.payments_by_status', 'Payments by Status', 'table', $adminRoles,
            fn() => [
                'title' => 'Payments by Status',
                'icon' => 'fas fa-chart-pie',
                'headers' => ['Status', 'Count', 'Total'],
                'rows' => \App\Models\Payment::select(
                        'status',
                        \Illuminate\Support\Facades\DB::raw('count(*) as count'),
                        \Illuminate\Support\Facades\DB::raw('sum(amount) as total')
                    )
                    ->groupBy('status')
                    ->get()
                    ->map(fn($r) => [ucfirst($r->status), $r->count, '₦' . number_format($r->total ?? 0, 2)])
                    ->all(),
                'colspan' => 3,
                'empty_message' => 'No payments recorded',
            ],
            'widgets.table-card'
        ));
    }
}
