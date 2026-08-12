<?php

namespace App\Providers;

use App\Services\Dashboard\WidgetDefinition;
use App\Services\Dashboard\WidgetRegistry;
use App\Services\Permissions\PermissionService;
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
        // Resolves via the cross-domain PermissionService so hospital,
        // bursar, registrar, admin, etc. all work the same way.
        Blade::directive('permission', function (string $expression) {
            return "<?php if (\\App\\Services\\Permissions\\PermissionService::allows({$expression})): ?>";
        });
        Blade::directive('endpermission', function () {
            return '<?php endif; ?>';
        });

        // @anypermission(['patients.view','pharmacy.view']) ... @endanypermission
        // Renders the block if the user has any of the listed permissions.
        Blade::directive('anypermission', function (string $expression) {
            return "<?php if (\\App\\Services\\Permissions\\PermissionService::allowsAny([{$expression}])): ?>";
        });
        Blade::directive('endanypermission', function () {
            return '<?php endif; ?>';
        });
    }

    /**
     * Dispatcher: calls every per-audience registration method.
     *
     * Each per-audience method registers widgets whose
     * `appliesToRoles` matches one role bucket. New audiences add a
     * new method here — keep them in this order: admin, bursar,
     * registrar, student, ... (alphabetical beyond that).
     */
    private function registerDashboardWidgets(): void
    {
        $this->registerAdminWidgets();
        $this->registerBursarWidgets();
        $this->registerRegistrarWidgets();
        $this->registerStudentWidgets();
    }

    /**
     * Register every dashboard widget the system ships with for the
     * admin audience (super_admin, admin, ict_admin, staff).
     *
     * Each closure runs when a widget is resolved for rendering, so
     * database access is deferred until the dashboard actually needs
     * it. `appliesToRoles` mirrors the role list on the middleware
     * that gates the admin dashboard — if we add a widget the admin
     * route allows but the registry doesn't list for this role, it'll
     * be invisible to that user by default.
     */
    private function registerAdminWidgets(): void
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

    /**
     * Register bursar-audience dashboard widgets.
     *
     * Roles: bursar, accountant, cashier, audit_bursar, finance,
     * business_committee, audit. The dashboard's filter form
     * (`?school_id=N`) is chrome — it lives in the view and only
     * filters the paginated lists below the widget grid. Widgets
     * always show session-wide totals.
     */
    private function registerBursarWidgets(): void
    {
        $bursarRoles = ['bursar', 'accountant', 'cashier', 'audit_bursar', 'finance', 'business_committee', 'audit'];

        // Mirror of Bursar/DashboardController::PAID_STATUSES. The
        // verify() flow writes 'completed'; some legacy seed rows are
        // 'verified'. Both count as paid.
        $paidStatuses = ['completed', 'verified'];

        // --- Stat tiles -------------------------------------------------

        WidgetRegistry::register(new WidgetDefinition(
            'bursar.total_expected', 'Total Expected Fees', 'stat', $bursarRoles,
            function () use ($paidStatuses) {
                $sessionId = \App\Models\Session::getCurrentSession()?->id;
                return [
                    'value' => \App\Models\Fee::where('session_id', $sessionId)->sum('amount'),
                    'format' => 'currency',
                    'color' => 'warning',
                    'icon' => 'fas fa-calculator',
                    'href' => route('bursar.payments'),
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'bursar.total_paid', 'Total Paid', 'stat', $bursarRoles,
            function () use ($paidStatuses) {
                $sessionId = \App\Models\Session::getCurrentSession()?->id;
                return [
                    'value' => \App\Models\Payment::whereHas('student', function ($q) use ($sessionId) {
                            $q->where('session_id', $sessionId);
                        })
                        ->whereIn('status', $paidStatuses)
                        ->sum('amount'),
                    'format' => 'currency',
                    'color' => 'success',
                    'icon' => 'fas fa-dollar-sign',
                    'href' => route('bursar.paid-students'),
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'bursar.outstanding', 'Outstanding Fees', 'stat', $bursarRoles,
            function () use ($paidStatuses) {
                $sessionId = \App\Models\Session::getCurrentSession()?->id;
                $expected = \App\Models\Fee::where('session_id', $sessionId)->sum('amount');
                $paid = \App\Models\Payment::whereHas('student', function ($q) use ($sessionId) {
                        $q->where('session_id', $sessionId);
                    })
                    ->whereIn('status', $paidStatuses)
                    ->sum('amount');
                return [
                    'value' => $expected - $paid,
                    'format' => 'currency',
                    'color' => 'danger',
                    'icon' => 'fas fa-exclamation-circle',
                    'href' => route('bursar.debtors'),
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'bursar.debtors_count', 'Debtors', 'stat', $bursarRoles,
            function () use ($paidStatuses) {
                // Replicates Bursar\DashboardController::debtorQuery()
                // for the canonical "who is a debtor?" definition.
                // NOT EXISTS is used (not NOT IN) because the payments
                // table has nullable fee_id rows — NOT IN with NULL on
                // the right-hand side returns UNKNOWN.
                $sessionId = \App\Models\Session::getCurrentSession()?->id;
                $requiredFeeIds = \App\Models\Fee::where('is_active', true)
                    ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
                    ->pluck('id')
                    ->all();
                if (empty($requiredFeeIds)) {
                    return [
                        'value' => 0,
                        'format' => 'number',
                        'color' => 'warning',
                        'icon' => 'fas fa-user-clock',
                        'href' => route('bursar.debtors'),
                    ];
                }
                $feeIdList = implode(',', array_map('intval', $requiredFeeIds));
                $statusList = implode(',', array_map(
                    fn($s) => "'" . addslashes($s) . "'",
                    $paidStatuses
                ));
                $count = \App\Models\Student::query()
                    ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
                    ->whereExists(function ($sub) use ($feeIdList, $statusList) {
                        $sub->selectRaw('1')
                            ->from('fees')
                            ->whereRaw("fees.id IN ({$feeIdList})")
                            ->whereNotExists(function ($paySub) use ($statusList) {
                                $paySub->selectRaw('1')
                                    ->from('payments')
                                    ->whereRaw('payments.student_id = students.id')
                                    ->whereRaw("payments.status IN ({$statusList})")
                                    ->whereRaw('payments.fee_id = fees.id');
                            });
                    })
                    ->count();
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'warning',
                    'icon' => 'fas fa-user-clock',
                    'href' => route('bursar.debtors'),
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'bursar.paid_count', 'Paid Students', 'stat', $bursarRoles,
            function () use ($paidStatuses) {
                $sessionId = \App\Models\Session::getCurrentSession()?->id;
                return [
                    'value' => \App\Models\Payment::whereIn('status', $paidStatuses)
                        ->whereHas('student', function ($q) use ($sessionId) {
                            $q->where('session_id', $sessionId);
                        })
                        ->count(),
                    'format' => 'number',
                    'color' => 'success',
                    'icon' => 'fas fa-user-check',
                    'href' => route('bursar.paid-students'),
                ];
            },
            'widgets.stat-card'
        ));

        // --- Tables ----------------------------------------------------

        WidgetRegistry::register(new WidgetDefinition(
            'bursar.recent_payments', 'Recent Payments', 'table', $bursarRoles,
            function () use ($paidStatuses) {
                $sessionId = \App\Models\Session::getCurrentSession()?->id;
                return [
                    'title' => 'Recent Payments',
                    'icon' => 'fas fa-dollar-sign',
                    'headers' => ['Student', 'Fee', 'Amount', 'Status'],
                    'rows' => \App\Models\Payment::with(['student.user', 'fee'])
                        ->whereIn('status', $paidStatuses)
                        ->whereHas('student', function ($q) use ($sessionId) {
                            $q->where('session_id', $sessionId);
                        })
                        ->latest()
                        ->take(5)
                        ->get()
                        ->map(function ($p) {
                            $student = $p->student && $p->student->user
                                ? $p->student->user->name
                                : ($p->payer_name ?? 'N/A');
                            $fee = $p->fee->name
                                ?? $p->fee_type
                                ?? $p->payment_purpose
                                ?? 'Payment';
                            return [
                                $student,
                                $fee,
                                '₦' . number_format($p->amount, 2),
                                ucfirst($p->status),
                            ];
                        })
                        ->all(),
                    'colspan' => 4,
                    'empty_message' => 'No recent payments',
                ];
            },
            'widgets.table-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'bursar.recent_debtors', 'Recent Debtors', 'table', $bursarRoles,
            function () use ($paidStatuses) {
                $sessionId = \App\Models\Session::getCurrentSession()?->id;
                $requiredFeeIds = \App\Models\Fee::where('is_active', true)
                    ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
                    ->pluck('id')
                    ->all();
                $rows = [];
                if (!empty($requiredFeeIds)) {
                    $feeIdList = implode(',', array_map('intval', $requiredFeeIds));
                    $statusList = implode(',', array_map(
                        fn($s) => "'" . addslashes($s) . "'",
                        $paidStatuses
                    ));
                    $rows = \App\Models\Student::query()
                        ->with(['user', 'department'])
                        ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
                        ->whereExists(function ($sub) use ($feeIdList, $statusList) {
                            $sub->selectRaw('1')
                                ->from('fees')
                                ->whereRaw("fees.id IN ({$feeIdList})")
                                ->whereNotExists(function ($paySub) use ($statusList) {
                                    $paySub->selectRaw('1')
                                        ->from('payments')
                                        ->whereRaw('payments.student_id = students.id')
                                        ->whereRaw("payments.status IN ({$statusList})")
                                        ->whereRaw('payments.fee_id = fees.id');
                                });
                        })
                        ->orderBy('matric_number')
                        ->take(5)
                        ->get()
                        ->map(fn($s) => [
                            $s->user->name ?? 'N/A',
                            $s->matric_number,
                            $s->department->name ?? 'N/A',
                        ])
                        ->all();
                }
                return [
                    'title' => 'Recent Debtors',
                    'icon' => 'fas fa-user-clock',
                    'headers' => ['Name', 'Matric', 'Department'],
                    'rows' => $rows,
                    'colspan' => 3,
                    'empty_message' => 'No debtors',
                ];
            },
            'widgets.table-card'
        ));
    }

    /**
     * Register registrar-audience dashboard widgets.
     *
     * Roles: registrar, admission_officer, ict_admin. The
     * "Pending → Screening → Approved → Admitted" pipeline flow strip
     * stays in the view's chrome — it's a domain-specific visual
     * idiom that doesn't fit the generic stat / table partials.
     *
     * The Pending Review and Admitted tiles carry a coloured `cta`
     * button that exercises the extended `widgets.stat-card` partial.
     * The two recent-applications / recent-admissions tables stay in
     * chrome because they render coloured status badges from an
     * inline `match` expression — table-card extension is its own
     * follow-up slice.
     */
    private function registerRegistrarWidgets(): void
    {
        $registrarRoles = ['registrar', 'admission_officer', 'ict_admin'];

        // --- Stat tiles -------------------------------------------------

        WidgetRegistry::register(new WidgetDefinition(
            'registrar.total_applicants', 'Total Applicants', 'stat', $registrarRoles,
            fn() => [
                'value' => \App\Models\Applicant::count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-user-plus',
                'href' => route('registrar.applicants'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'registrar.pending', 'Pending Review', 'stat', $registrarRoles,
            fn() => [
                'value' => \App\Models\Applicant::where('status', 'pending')->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-clock',
                'cta' => [
                    'label' => 'Review',
                    'icon' => 'fas fa-list',
                    'href' => route('registrar.applications.index'),
                    'color' => 'warning',
                ],
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'registrar.admitted', 'Admitted', 'stat', $registrarRoles,
            fn() => [
                'value' => \App\Models\Applicant::where('status', 'admitted')->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-user-graduate',
                'cta' => [
                    'label' => 'View',
                    'icon' => 'fas fa-user-graduate',
                    'href' => route('registrar.applications.admitted'),
                    'color' => 'success',
                ],
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'registrar.rejected', 'Rejected', 'stat', $registrarRoles,
            fn() => [
                'value' => \App\Models\Applicant::where('status', 'rejected')->count(),
                'format' => 'number',
                'color' => 'danger',
                'icon' => 'fas fa-user-times',
            ],
            'widgets.stat-card'
        ));

        // --- Tables ----------------------------------------------------

        WidgetRegistry::register(new WidgetDefinition(
            'registrar.recent_apps', 'Recent Applications', 'table', $registrarRoles,
            fn() => [
                'title' => 'Recent Applications',
                'icon' => 'fas fa-file-alt',
                'headers' => ['Application #', 'Name', 'School', 'Status', 'Submitted'],
                'rows' => \App\Models\Applicant::with(['user', 'school'])
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(fn($a) => [
                        $a->application_number,
                        $a->full_name ?? optional($a->user)->name ?? 'N/A',
                        optional($a->school)->name ?? 'N/A',
                        ucfirst($a->status ?? 'pending'),
                        optional($a->created_at)->diffForHumans() ?? 'N/A',
                    ])
                    ->all(),
                'colspan' => 5,
                'empty_message' => 'No applicants yet',
            ],
            'widgets.table-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'registrar.recent_admissions', 'Recent Admissions', 'table', $registrarRoles,
            fn() => [
                'title' => 'Recent Admissions',
                'icon' => 'fas fa-user-graduate',
                'headers' => ['Application #', 'Name', 'Programme'],
                'rows' => \App\Models\Applicant::with(['user', 'programme'])
                    ->where('status', 'admitted')
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(fn($a) => [
                        $a->application_number,
                        $a->full_name ?? optional($a->user)->name ?? 'N/A',
                        optional($a->programme)->name ?? 'N/A',
                    ])
                    ->all(),
                'colspan' => 3,
                'empty_message' => 'No admissions yet',
            ],
            'widgets.table-card'
        ));
    }

    /**
     * Register student-audience dashboard widgets.
     *
     * Role: student. Personal-data stats scoped to the auth'd user
     * via `$request->user()->student`. Each closure uses the request
     * because WidgetDefinition's `data` is a Closure with no
     * arguments — we close over the request through the service
     * container's `auth()` helper at render time.
     *
     * The welcome banner, profile-warning, fees-to-pay table,
     * payment badge, Quick Actions grid, and post-login popup modal
     * all stay in the view's chrome.
     */
    private function registerStudentWidgets(): void
    {
        $studentRoles = ['student'];
        $paidStatuses = ['completed', 'verified'];

        // --- Stat tiles -------------------------------------------------

        WidgetRegistry::register(new WidgetDefinition(
            'student.registered_courses', 'Registered Courses', 'stat', $studentRoles,
            function () {
                $student = auth()->user()?->student;
                if (!$student) {
                    return ['value' => 0, 'format' => 'number', 'color' => 'success', 'icon' => 'fas fa-book'];
                }
                return [
                    'value' => \App\Models\StudentCourse::where('student_id', $student->id)
                        ->where('status', 'registered')
                        ->count(),
                    'format' => 'number',
                    'color' => 'success',
                    'icon' => 'fas fa-book',
                    'href' => route('student.courses'),
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'student.total_payments', 'Total Payments', 'stat', $studentRoles,
            function () use ($paidStatuses) {
                $student = auth()->user()?->student;
                if (!$student) {
                    return ['value' => 0, 'format' => 'number', 'color' => 'info', 'icon' => 'fas fa-credit-card'];
                }
                return [
                    'value' => \App\Models\Payment::where('student_id', $student->id)
                        ->whereIn('status', $paidStatuses)
                        ->count(),
                    'format' => 'number',
                    'color' => 'info',
                    'icon' => 'fas fa-credit-card',
                    'href' => route('student.payments'),
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'student.total_fees', 'Total Fees', 'stat', $studentRoles,
            function () {
                $student = auth()->user()?->student;
                if (!$student) {
                    return ['value' => 0, 'format' => 'number', 'color' => 'warning', 'icon' => 'fas fa-money-bill'];
                }
                return [
                    'value' => \App\Models\Fee::where('session_id', $student->session_id)
                        ->count(),
                    'format' => 'number',
                    'color' => 'warning',
                    'icon' => 'fas fa-money-bill',
                    'href' => route('student.payments'),
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'student.unpaid_fees', 'Unpaid Fees', 'stat', $studentRoles,
            function () use ($paidStatuses) {
                $student = auth()->user()?->student;
                if (!$student) {
                    return ['value' => 0, 'format' => 'number', 'color' => 'danger', 'icon' => 'fas fa-exclamation-circle'];
                }
                $sessionId = $student->session_id;
                $feeIds = \App\Models\Fee::where('session_id', $sessionId)->pluck('id')->all();
                if (empty($feeIds)) {
                    return ['value' => 0, 'format' => 'number', 'color' => 'danger', 'icon' => 'fas fa-exclamation-circle'];
                }
                $feeIdList = implode(',', array_map('intval', $feeIds));
                $statusList = implode(',', array_map(
                    fn($s) => "'" . addslashes($s) . "'",
                    $paidStatuses
                ));
                $count = \Illuminate\Support\Facades\DB::table('fees')
                    ->whereRaw("fees.id IN ({$feeIdList})")
                    ->whereNotExists(function ($paySub) use ($student, $statusList) {
                        $paySub->selectRaw('1')
                            ->from('payments')
                            ->whereRaw('payments.student_id = ' . (int) $student->id)
                            ->whereRaw("payments.status IN ({$statusList})")
                            ->whereRaw('payments.fee_id = fees.id');
                    })
                    ->count();
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'danger',
                    'icon' => 'fas fa-exclamation-circle',
                    'href' => route('student.payments'),
                ];
            },
            'widgets.stat-card'
        ));
    }
}