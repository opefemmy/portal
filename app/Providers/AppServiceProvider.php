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
        $this->registerAuditorWidgets();
        $this->registerBusinessCommitteeWidgets();
        $this->registerAcademicBoardWidgets();
        $this->registerExecutiveWidgets();
        $this->registerLibrarianWidgets();
        $this->registerLecturerWidgets();
        $this->registerHodWidgets();
        $this->registerDeanWidgets();
        $this->registerHospitalWidgets();
        $this->registerDoctorWidgets();
        $this->registerNurseWidgets();
        $this->registerReceptionistWidgets();
        $this->registerPharmacistWidgets();
        $this->registerLabWidgets();
        $this->registerMatronWidgets();
        $this->registerHospitalAdminWidgets();
        $this->registerFinanceWidgets();
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
     * audit. The dashboard's filter form (`?school_id=N`) is chrome
     * — it lives in the view and only filters the paginated lists
     * below the widget grid. Widgets always show session-wide totals.
     */
    private function registerBursarWidgets(): void
    {
        // NOTE: `business_committee` is intentionally NOT here. The
        // /bursar/* route middleware (routes/web.php:790) gates that
        // dashboard separately, and a `business_committee` user who
        // has no bursar-side visit path has no business seeing
        // bursar-shaped widgets.
        $bursarRoles = ['bursar', 'accountant', 'cashier', 'audit_bursar', 'finance', 'audit'];

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

        // --- Currency totals (Part C of the multi-area plan) -------
        // The four count-based widgets above answer "how many". These
        // three answer "how much", which is what the student actually
        // asks when they look at their dashboard. `format: currency`
        // is honoured by widgets/stat-card.blade.php (same precedent
        // as bursar.total_paid at line 430-447).

        WidgetRegistry::register(new WidgetDefinition(
            'student.registered_courses_count', 'Registered Courses', 'stat', $studentRoles,
            function () {
                $student = auth()->user()?->student;
                $value = $student
                    ? \App\Models\StudentCourse::where('student_id', $student->id)
                        ->where('status', 'registered')->count()
                    : 0;
                return [
                    'value' => $value,
                    'format' => 'number',
                    'color' => 'success',
                    'icon'  => 'fas fa-book',
                    'href'  => $student ? route('student.courses') : null,
                    'label_extra' => 'this session',
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'student.fee_paid', 'Fee Paid', 'stat', $studentRoles,
            function () use ($paidStatuses) {
                $student = auth()->user()?->student;
                $sum = 0;
                if ($student) {
                    $sum = (float) \App\Models\Payment::where('student_id', $student->id)
                        ->whereIn('status', $paidStatuses)
                        ->sum('amount');
                }
                return [
                    'value' => $sum,
                    'format' => 'currency',
                    'color' => 'success',
                    'icon'  => 'fas fa-coins',
                    'href'  => $student ? route('student.payments') : null,
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'student.fee_outstanding', 'Fee Outstanding', 'stat', $studentRoles,
            function () use ($paidStatuses) {
                $student = auth()->user()?->student;
                if (!$student) {
                    return [
                        'value' => 0, 'format' => 'currency', 'color' => 'danger',
                        'icon' => 'fas fa-exclamation-circle',
                    ];
                }
                $sessionId = $student->session_id;
                $expected = (float) \App\Models\Fee::where('session_id', $sessionId)->sum('amount');
                $paid = (float) \App\Models\Payment::where('student_id', $student->id)
                    ->whereIn('status', $paidStatuses)
                    ->sum('amount');
                $outstanding = max(0, $expected - $paid);
                return [
                    'value'  => $outstanding,
                    'format' => 'currency',
                    'color'  => $outstanding > 0 ? 'danger' : 'success',
                    'icon'   => 'fas fa-exclamation-circle',
                    'href'   => route('student.payments'),
                ];
            },
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the auditor audience (auditor,
     * internal_auditor, external_auditor). Audit logs, deleted
     * records, finance transaction totals, and pending refunds.
     *
     * The recent audit logs and failed-actions tables stay in the
     * view's chrome (they carry user/date joins that don't fit the
     * generic widget data shape).
     */
    private function registerAuditorWidgets(): void
    {
        $auditorRoles = ['auditor', 'internal_auditor', 'external_auditor', 'super_admin', 'admin'];

        WidgetRegistry::register(new WidgetDefinition(
            'auditor.total_transactions', 'Total Transactions', 'stat', $auditorRoles,
            fn() => [
                'value' => \App\Models\Finance\FinanceTransaction::count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-exchange-alt',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'auditor.total_receipts', 'Total Receipts', 'stat', $auditorRoles,
            fn() => [
                'value' => \App\Models\Finance\FinanceReceipt::count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-receipt',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'auditor.deleted_records', 'Deleted Records', 'stat', $auditorRoles,
            fn() => [
                'value' => \App\Models\DeletedRecord::count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-trash-restore',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'auditor.audit_logs', 'Audit Logs', 'stat', $auditorRoles,
            fn() => [
                'value' => \App\Models\AuditLog::withTrashed()->count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-clipboard-list',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'auditor.failed_actions', 'Failed Actions', 'stat', $auditorRoles,
            fn() => [
                'value' => \App\Models\AuditLog::withTrashed()->where('status', 'failed')->count(),
                'format' => 'number',
                'color' => 'danger',
                'icon' => 'fas fa-exclamation-triangle',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'auditor.pending_refunds', 'Pending Refunds', 'stat', $auditorRoles,
            fn() => [
                'value' => \App\Models\Finance\FinanceRefund::where('status', 'pending')->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-undo',
            ],
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the result-approval business_committee audience.
     *
     * The committee sees the inbound `approved_by_dean` queue plus the
     * count of results it has already advanced to
     * `approved_by_business`. The "View Results" call-to-action card
     * with intro paragraph stays in the view's chrome.
     */
    private function registerBusinessCommitteeWidgets(): void
    {
        $roles = ['business_committee', 'super_admin', 'admin'];

        WidgetRegistry::register(new WidgetDefinition(
            'business_committee.pending', 'Pending Results', 'stat', $roles,
            fn() => [
                'value' => \App\Models\Result::where('status', 'approved_by_dean')->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-clock',
                'href' => route('business-committee.results'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'business_committee.approved', 'Approved Results', 'stat', $roles,
            fn() => [
                'value' => \App\Models\Result::where('status', 'approved_by_business')->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-clipboard-check',
            ],
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the final-approval academic_board audience.
     *
     * The board sees the `approved_by_business` queue plus the count of
     * results it has elevated to `approved_final`.
     */
    private function registerAcademicBoardWidgets(): void
    {
        $roles = ['academic_board', 'super_admin', 'admin'];

        WidgetRegistry::register(new WidgetDefinition(
            'academic_board.pending', 'Pending Final Approval', 'stat', $roles,
            fn() => [
                'value' => \App\Models\Result::where('status', 'approved_by_business')->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-gavel',
                'href' => route('academic-board.results'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'academic_board.final_approved', 'Final Approved Results', 'stat', $roles,
            fn() => [
                'value' => \App\Models\Result::where('status', 'approved_final')->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-graduation-cap',
            ],
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the executive audience (rector, super_admin).
     *
     * Cross-domain roll-up: students, staff, finance, hospital. The
     * dashboard tiles mirror the four cards the old hand-built
     * controller produced (total students, total staff, today revenue,
     * monthly revenue) plus a few derived ones (active students, new
     * this month, outstanding balance, admitted patients, today's
     * appointments) so the executive can see a snapshot of the whole
     * institution at a glance.
     *
     * Top-departments table and recent-receipts table stay in the
     * view's chrome because they need column joins.
     */
    private function registerExecutiveWidgets(): void
    {
        $roles = ['rector', 'super_admin', 'admin'];

        WidgetRegistry::register(new WidgetDefinition(
            'executive.total_students', 'Total Students', 'stat', $roles,
            fn() => [
                'value' => \App\Models\User::whereHas('role', fn($q) => $q->where('slug', 'student'))->count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-user-graduate',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'executive.active_students', 'Active Students', 'stat', $roles,
            fn() => [
                'value' => \App\Models\User::whereHas('role', fn($q) => $q->where('slug', 'student'))
                    ->where('is_active', true)->count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-user-check',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'executive.new_students_this_month', 'New This Month', 'stat', $roles,
            fn() => [
                'value' => \App\Models\User::whereHas('role', fn($q) => $q->where('slug', 'student'))
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-user-plus',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'executive.total_staff', 'Total Staff', 'stat', $roles,
            fn() => [
                'value' => \App\Models\User::whereHas('role',
                    fn($q) => $q->whereNotIn('slug', ['student', 'applicant'])
                )->count(),
                'format' => 'number',
                'color' => 'secondary',
                'icon' => 'fas fa-user-tie',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'executive.today_revenue', "Today's Revenue", 'stat', $roles,
            fn() => [
                'value' => (float) \App\Models\Finance\FinanceReceipt::whereDate('payment_date', today())->sum('amount'),
                'format' => 'currency',
                'color' => 'success',
                'icon' => 'fas fa-cash-register',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'executive.monthly_revenue', 'Monthly Revenue', 'stat', $roles,
            fn() => [
                'value' => (float) \App\Models\Finance\FinanceReceipt::whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year)
                    ->sum('amount'),
                'format' => 'currency',
                'color' => 'primary',
                'icon' => 'fas fa-chart-line',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'executive.outstanding_balance', 'Outstanding Balance', 'stat', $roles,
            fn() => [
                'value' => (float) \App\Models\Finance\FinanceInvoice::whereIn('status', ['pending', 'partial'])->sum('balance'),
                'format' => 'currency',
                'color' => 'warning',
                'icon' => 'fas fa-balance-scale',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'executive.admitted_patients', 'Admitted Patients', 'stat', $roles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAdmission::where('status', 'admitted')->count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-procedures',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'executive.today_appointments', "Today's Appointments", 'stat', $roles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAppointment::whereDate('appointment_date', today())->count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-calendar-check',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'executive.recent_receipts', 'Recent Receipts', 'table', $roles,
            fn() => [
                'title'   => 'Recent Receipts',
                'icon'    => 'fas fa-receipt',
                'headers' => ['Date', 'Student', 'Amount', 'Method'],
                'rows'    => \App\Models\Finance\FinanceReceipt::with('student')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(fn($r) => [
                        optional($r->created_at)->format('d M Y') ?? 'N/A',
                        optional($r->student)->name ?? 'N/A',
                        '₦' . number_format((float) $r->amount, 2),
                        ucfirst((string) $r->payment_method),
                    ])
                    ->all(),
                'colspan' => 4,
                'empty_message' => 'No recent receipts',
            ],
            'widgets.table-card'
        ));
    }

    /**
     * Register widgets for the librarian audience.
     *
     * Four stat tiles (total books, available, borrowed, overdue) —
     * each corresponds to a single Book / BookLoan query. The
     * Overdue Loans tile carries a coloured CTA linking to
     * /librarian/loans since that's the actionable destination.
     * Quick Actions card and the Overdue Books callout (which
     * switches copy based on count) stay in the view's chrome.
     */
    private function registerLibrarianWidgets(): void
    {
        $librarianRoles = ['librarian'];

        WidgetRegistry::register(new WidgetDefinition(
            'librarian.total_books', 'Total Books', 'stat', $librarianRoles,
            fn() => [
                'value' => \App\Models\Book::count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-book',
                'href' => route('librarian.books'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'librarian.available_books', 'Available Books', 'stat', $librarianRoles,
            fn() => [
                // NOTE: pre-existing hand-built controller used
                // `where('status', 'available')` but the `books`
                // table has no `status` column — it has `available`
                // (int count) + `is_active` (bool). Use `available > 0`
                // so the dashboard actually loads.
                'value' => \App\Models\Book::where('available', '>', 0)->count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-check-circle',
                'href' => route('librarian.books'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'librarian.borrowed_books', 'Borrowed Books', 'stat', $librarianRoles,
            fn() => [
                'value' => \App\Models\BookLoan::where('status', 'borrowed')->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-exchange-alt',
                'href' => route('librarian.loans'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'librarian.overdue_loans', 'Overdue Loans', 'stat', $librarianRoles,
            function () {
                $count = \App\Models\BookLoan::where('status', 'borrowed')
                    ->where('due_date', '<', now())
                    ->count();
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'danger',
                    'icon' => 'fas fa-exclamation-triangle',
                    'href' => route('librarian.loans'),
                    'cta' => $count > 0 ? [
                        'label' => 'View Overdue',
                        'icon' => 'fas fa-list',
                        'href' => route('librarian.loans'),
                        'color' => 'danger',
                    ] : null,
                ];
            },
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the lecturer audience.
     *
     * Role: lecturer. All three tiles are scoped to the auth'd
     * lecturer's own course assignments. Closures run at render time
     * and read `auth()->user()` directly so the user-scoping matches
     * the per-user widget row in `dashboard_widgets` when the user
     * later customises their dashboard.
     *
     * The "My Courses" table below the widget grid stays in the view's
     * chrome — it carries per-row action buttons (Students / Enter
     * Results / Template) that don't fit the generic table-card
     * partial, which renders plain text cells.
     */
    private function registerLecturerWidgets(): void
    {
        $lecturerRoles = ['lecturer'];

        WidgetRegistry::register(new WidgetDefinition(
            'lecturer.assigned_courses', 'Assigned Courses', 'stat', $lecturerRoles,
            function () {
                $userId = auth()->id();
                $count = $userId
                    ? \App\Models\CourseAssignment::where('lecturer_id', $userId)->count()
                    : 0;
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'success',
                    'icon' => 'fas fa-chalkboard-teacher',
                    'href' => $userId ? route('lecturer.courses') : null,
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'lecturer.total_students', 'Total Students', 'stat', $lecturerRoles,
            function () {
                $userId = auth()->id();
                if (!$userId) {
                    return ['value' => 0, 'format' => 'number', 'color' => 'info', 'icon' => 'fas fa-user-graduate'];
                }
                // Sum of studentCourses across all of this lecturer's
                // course assignments. Mirrors the original hand-built
                // controller: $totalStudents += $assignment->studentCourses->count().
                $count = \App\Models\StudentCourse::whereIn(
                        'course_id',
                        \App\Models\CourseAssignment::where('lecturer_id', $userId)->pluck('course_id')
                    )
                    ->count();
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'info',
                    'icon' => 'fas fa-user-graduate',
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'lecturer.pending_results', 'Pending Results', 'stat', $lecturerRoles,
            function () {
                $userId = auth()->id();
                if (!$userId) {
                    return ['value' => 0, 'format' => 'number', 'color' => 'warning', 'icon' => 'fas fa-clipboard-check'];
                }
                // Results awaiting approval across the lecturer's
                // assigned courses. Matches the original controller's
                // Result::where('course_id', ...)->where('status',
                // 'pending_approval') per-assignment sum.
                $count = \App\Models\Result::whereIn(
                        'course_id',
                        \App\Models\CourseAssignment::where('lecturer_id', $userId)->pluck('course_id')
                    )
                    ->where('status', 'pending_approval')
                    ->count();
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'warning',
                    'icon' => 'fas fa-clipboard-check',
                ];
            },
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the HOD audience.
     *
     * Role: hod. All tiles + tables are scoped to the auth'd HOD's
     * `department_id`. Closures gracefully degrade to zero / empty
     * when the HOD has no department assigned — the controller still
     * surfaces a "not assigned to any department" alert in chrome,
     * which is the right place to communicate that state rather than
     * rendering an empty dashboard.
     *
     * Quick Actions card stays in the view's chrome — its four
     * action buttons (Assign Course / Manage Courses / Review Results
     * / View Timetable) are too narrow to justify a generic
     * action-list widget type.
     */
    private function registerHodWidgets(): void
    {
        $hodRoles = ['hod'];

        WidgetRegistry::register(new WidgetDefinition(
            'hod.total_courses', 'Department Courses', 'stat', $hodRoles,
            function () {
                $departmentId = auth()->user()?->department_id;
                $count = $departmentId
                    ? \App\Models\Course::where('department_id', $departmentId)->count()
                    : 0;
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'info',
                    'icon' => 'fas fa-book',
                    'href' => $departmentId ? route('hod.courses') : null,
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hod.total_lecturers', 'Lecturers', 'stat', $hodRoles,
            function () {
                $departmentId = auth()->user()?->department_id;
                $count = 0;
                if ($departmentId) {
                    $courseIds = \App\Models\Course::where('department_id', $departmentId)->pluck('id');
                    $count = $courseIds->isNotEmpty()
                        ? \App\Models\CourseAssignment::whereIn('course_id', $courseIds)
                            ->whereNotNull('lecturer_id')
                            ->distinct('lecturer_id')
                            ->count('lecturer_id')
                        : 0;
                }
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'success',
                    'icon' => 'fas fa-user-tie',
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hod.total_students', 'Students', 'stat', $hodRoles,
            function () {
                $departmentId = auth()->user()?->department_id;
                $count = $departmentId
                    ? \App\Models\Student::where('department_id', $departmentId)->count()
                    : 0;
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'primary',
                    'icon' => 'fas fa-user-graduate',
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hod.pending_results', 'Pending Results', 'stat', $hodRoles,
            function () {
                $departmentId = auth()->user()?->department_id;
                $count = 0;
                if ($departmentId) {
                    $courseIds = \App\Models\Course::where('department_id', $departmentId)->pluck('id');
                    $count = $courseIds->isNotEmpty()
                        ? \App\Models\Result::whereIn('course_id', $courseIds)
                            ->where('status', 'pending_approval')
                            ->count()
                        : 0;
                }
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'warning',
                    'icon' => 'fas fa-clipboard-check',
                    'href' => $departmentId ? route('hod.results.index') : null,
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hod.recent_assignments', 'Recent Assignments', 'table', $hodRoles,
            function () {
                $departmentId = auth()->user()?->department_id;
                $rows = [];
                if ($departmentId) {
                    $courseIds = \App\Models\Course::where('department_id', $departmentId)->pluck('id');
                    $rows = $courseIds->isNotEmpty()
                        ? \App\Models\CourseAssignment::whereIn('course_id', $courseIds)
                            ->with(['course', 'lecturer', 'session'])
                            ->latest()
                            ->take(5)
                            ->get()
                            ->map(fn($a) => [
                                $a->course->code ?? 'N/A',
                                $a->lecturer->name ?? 'N/A',
                                $a->session->name ?? 'N/A',
                            ])
                            ->all()
                        : [];
                }
                return [
                    'title' => 'Recent Assignments',
                    'icon' => 'fas fa-book-reader',
                    'headers' => ['Course', 'Lecturer', 'Session'],
                    'rows' => $rows,
                    'colspan' => 3,
                    'empty_message' => 'No course assignments yet.',
                ];
            },
            'widgets.table-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hod.pending_results_list', 'Pending Results', 'table', $hodRoles,
            function () {
                $departmentId = auth()->user()?->department_id;
                $rows = [];
                if ($departmentId) {
                    $courseIds = \App\Models\Course::where('department_id', $departmentId)->pluck('id');
                    $rows = $courseIds->isNotEmpty()
                        ? \App\Models\Result::whereIn('course_id', $courseIds)
                            ->where('status', 'pending_approval')
                            ->with(['course', 'studentCourse.student'])
                            ->latest()
                            ->take(5)
                            ->get()
                            ->map(fn($r) => [
                                $r->course->code ?? 'N/A',
                                $r->studentCourse->student->matric_number ?? 'N/A',
                                'Pending',
                            ])
                            ->all()
                        : [];
                }
                return [
                    'title' => 'Pending Results',
                    'icon' => 'fas fa-tasks',
                    'headers' => ['Course', 'Student', 'Status'],
                    'rows' => $rows,
                    'colspan' => 3,
                    'empty_message' => 'No pending results for approval.',
                ];
            },
            'widgets.table-card'
        ));
    }

    /**
     * Register widgets for the dean audience.
     *
     * Role: dean. The dean dashboard has historically been a
     * placeholder ("Dashboard content coming soon") so we don't have
     * a hand-built controller to mirror. These four stat tiles give
     * the dean an institution-level overview (schools, departments,
     * students, programmes). Per-school drill-down tables are out of
     * scope — the existing /dean routes provide that surface; widgets
     * here are summary only.
     */
    private function registerDeanWidgets(): void
    {
        $deanRoles = ['dean'];

        // The Dean role is bound to exactly one school (`users.school_id`).
        // A Dean should never see the institution-wide totals — they
        // oversee *their* school. Each tile is scoped via the closure so
        // a super_admin who happens to be on the dean audience sees their
        // own scope, not the global count. The fallback for users without
        // a `school_id` is 0 so a misconfigured dean never sees the wrong
        // number.
        WidgetRegistry::register(new WidgetDefinition(
            'dean.my_school', 'My School', 'stat', $deanRoles,
            function () {
                $user = auth()->user();
                if (!$user || !$user->school_id) {
                    return ['value' => '—', 'format' => 'text', 'color' => 'secondary',
                            'icon' => 'fas fa-school'];
                }
                $school = \App\Models\School::find($user->school_id);
                return [
                    'value' => $school?->name ?? '—',
                    'format' => 'text',
                    'color' => 'primary',
                    'icon' => 'fas fa-school',
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'dean.total_departments', 'Departments', 'stat', $deanRoles,
            function () {
                $user = auth()->user();
                $count = $user && $user->school_id
                    ? \App\Models\Department::where('school_id', $user->school_id)->count()
                    : 0;
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'info',
                    'icon' => 'fas fa-building-columns',
                    'href' => $user && $user->school_id ? route('dean.departments') : null,
                    'label_extra' => 'in my school',
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'dean.total_programmes', 'Programmes', 'stat', $deanRoles,
            function () {
                $user = auth()->user();
                if (!$user || !$user->school_id) {
                    return ['value' => 0, 'format' => 'number', 'color' => 'warning',
                            'icon' => 'fas fa-graduation-cap'];
                }
                $departmentIds = \App\Models\Department::where('school_id', $user->school_id)->pluck('id');
                $count = \App\Models\Programme::whereIn('department_id', $departmentIds)->count();
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'warning',
                    'icon' => 'fas fa-graduation-cap',
                    'label_extra' => 'across my departments',
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'dean.total_students', 'Students', 'stat', $deanRoles,
            function () {
                $user = auth()->user();
                $count = $user && $user->school_id
                    ? \App\Models\Student::where('school_id', $user->school_id)->count()
                    : 0;
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'success',
                    'icon' => 'fas fa-user-graduate',
                    'href' => $user && $user->school_id ? route('dean.students') : null,
                    'label_extra' => 'in my school',
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'dean.pending_results', 'Pending Results', 'stat', $deanRoles,
            function () {
                $user = auth()->user();
                if (!$user || !$user->school_id) {
                    return ['value' => 0, 'format' => 'number', 'color' => 'warning',
                            'icon' => 'fas fa-hourglass-half'];
                }
                $departmentIds = \App\Models\Department::where('school_id', $user->school_id)->pluck('id');
                $courseIds = \App\Models\Course::whereIn('department_id', $departmentIds)->pluck('id');
                $count = \App\Models\Result::whereIn('course_id', $courseIds)
                    ->where('status', 'approved')
                    ->count();
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => $count > 0 ? 'warning' : 'success',
                    'icon' => 'fas fa-hourglass-half',
                    'href' => route('dean.results'),
                    'label_extra' => 'awaiting my approval',
                ];
            },
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the hospital root dashboard
     * (`/hospital/dashboard`) — the landing page for cmd /
     * hospital_admin / super_admin / admin.
     *
     * Mirrors Hospital\DashboardController::index() exactly for
     * the five stat tiles. The Today's Appointments + Recent
     * Patients tables stay in chrome — they use the controller's
     * eager-loaded $todayAppointments + $recentPatients
     * collections (which include the patient.full_name join the
     * widget closure would otherwise have to recreate).
     */
    private function registerHospitalWidgets(): void
    {
        $hospitalRoles = ['cmd', 'hospital_admin', 'super_admin', 'admin'];

        // --- Stat tiles -------------------------------------------------

        WidgetRegistry::register(new WidgetDefinition(
            'hospital.today_appointments', "Today's Appointments", 'stat', $hospitalRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAppointment::whereDate('appointment_date', today())->count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-calendar-day',
                'href' => route('hospital.appointments.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital.active_patients', 'Active Patients', 'stat', $hospitalRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalPatient::where('is_active', true)->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-users',
                'href' => route('hospital.patients.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital.pending_appointments', 'Pending Appointments', 'stat', $hospitalRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAppointment::where('status', 'scheduled')->count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-clock',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital.today_patients', 'New Patients Today', 'stat', $hospitalRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalPatient::whereDate('created_at', today())->count(),
                'format' => 'number',
                'color' => 'secondary',
                'icon' => 'fas fa-user-plus',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital.total_staff', 'Total Staff', 'stat', $hospitalRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalStaff::count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-user-md',
                'href' => route('hospital.admin.staff'),
            ],
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the doctor audience.
     *
     * Role: doctor (cmd also gets them — cmd drops into the doctor
     * dashboard when they want a clinician's view). All four tiles
     * are scoped to the auth'd user's HospitalStaff row (`doctor_id`
     * on HospitalAppointment). When the user has no HospitalStaff
     * profile (e.g. cmd viewing as admin), tiles degrade to zero.
     *
     * Today's Appointments + Active Consultations tables stay in
     * chrome — they use per-row action buttons (Start / Continue /
     * View) that the plain-text table-card partial can't render.
     */
    private function registerDoctorWidgets(): void
    {
        $doctorRoles = ['doctor', 'cmd'];

        WidgetRegistry::register(new WidgetDefinition(
            'doctor.today_appointments', "Today's Appointments", 'stat', $doctorRoles,
            function () {
                $doctorId = auth()->user()?->hospitalStaff?->id;
                $count = $doctorId
                    ? \App\Models\Hospital\HospitalAppointment::where('staff_id', $doctorId)
                        ->whereDate('appointment_date', today())
                        ->whereIn('status', ['scheduled', 'in_progress'])
                        ->count()
                    : 0;
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'primary',
                    'icon' => 'fas fa-calendar-day',
                    'href' => $doctorId ? route('hospital.appointments.queue') : null,
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'doctor.pending_consultations', 'Pending Consultations', 'stat', $doctorRoles,
            function () {
                $doctorId = auth()->user()?->hospitalStaff?->id;
                $count = $doctorId
                    ? \App\Models\Hospital\HospitalAppointment::where('staff_id', $doctorId)
                        ->where('status', 'in_progress')
                        ->count()
                    : 0;
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'warning',
                    'icon' => 'fas fa-user-clock',
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'doctor.completed_today', 'Completed Today', 'stat', $doctorRoles,
            function () {
                $doctorId = auth()->user()?->hospitalStaff?->id;
                $count = $doctorId
                    ? \App\Models\Hospital\HospitalAppointment::where('staff_id', $doctorId)
                        ->whereDate('appointment_date', today())
                        ->where('status', 'completed')
                        ->count()
                    : 0;
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'success',
                    'icon' => 'fas fa-check-circle',
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'doctor.total_patients', 'Total Patients', 'stat', $doctorRoles,
            function () {
                $doctorId = auth()->user()?->hospitalStaff?->id;
                $count = $doctorId
                    ? \App\Models\Hospital\HospitalAppointment::where('staff_id', $doctorId)
                        ->distinct('patient_id')->count('patient_id')
                    : 0;
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => 'info',
                    'icon' => 'fas fa-users',
                ];
            },
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the nurse audience.
     *
     * Roles: nurse, cmd. Two stat tiles for the nurse landing. The
     * hand-built view references `$stats['admitted_patients']`,
     * `$stats['vitals_recorded_today']`, `$stats['pending_vitals']`
     * — keys the controller doesn't pass. That's a pre-existing
     * data-vs-view mismatch (out of scope); we mirror only the
     * keys the controller actually populates (`today_appointments`,
     * `active_patients`).
     *
     * The "Patients Waiting for Vitals" and "Admitted Patients"
     * tables stay in chrome — they carry per-row View action
     * buttons and conditional rendering based on count.
     */
    private function registerNurseWidgets(): void
    {
        $nurseRoles = ['nurse', 'cmd'];

        WidgetRegistry::register(new WidgetDefinition(
            'nurse.today_appointments', "Today's Appointments", 'stat', $nurseRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAppointment::whereDate('appointment_date', today())
                    ->where('status', 'scheduled')
                    ->count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-calendar-day',
                'href' => route('hospital.appointments.queue'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'nurse.active_patients', 'Active Patients', 'stat', $nurseRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalPatient::where('is_active', true)->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-users',
                'href' => route('hospital.patients.index'),
            ],
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the hospital receptionist audience.
     *
     * Roles: hospital_receptionist, cmd. Four stat tiles covering
     * the queue, today's check-ins, total patients, and new
     * patients today. The Today's Queue table stays in chrome —
     * it has per-row action buttons (Check In / View).
     */
    private function registerReceptionistWidgets(): void
    {
        $receptionistRoles = ['hospital_receptionist', 'cmd'];

        WidgetRegistry::register(new WidgetDefinition(
            'receptionist.queue_count', 'Queue Count', 'stat', $receptionistRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAppointment::whereDate('appointment_date', today())
                    ->whereIn('status', ['scheduled'])
                    ->count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-users',
                'href' => route('hospital.appointments.queue'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'receptionist.checked_in_today', 'Checked In Today', 'stat', $receptionistRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAppointment::whereDate('appointment_date', today())
                    ->where('status', 'completed')
                    ->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-check-circle',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'receptionist.total_patients', 'Total Patients', 'stat', $receptionistRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalPatient::count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-database',
                'href' => route('hospital.patients.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'receptionist.new_patients_today', 'New Patients Today', 'stat', $receptionistRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalPatient::whereDate('created_at', today())->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-user-plus',
            ],
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the pharmacist audience.
     *
     * Roles: pharmacist, store_keeper, cmd. Four stat tiles plus
     * one table for the pending prescriptions list. The Low Stock
     * Alert panel in chrome (which lists drugs with current_stock
     * near zero) stays put — it's a richer layout than the
     * table-card partial supports.
     */
    private function registerPharmacistWidgets(): void
    {
        $pharmacistRoles = ['pharmacist', 'store_keeper', 'cmd'];

        WidgetRegistry::register(new WidgetDefinition(
            'pharmacist.pending_prescriptions', 'Pending Prescriptions', 'stat', $pharmacistRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalPrescription::where('status', 'pending')->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-prescription',
                'href' => route('hospital.pharmacy.prescriptions'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'pharmacist.dispensed_today', 'Dispensed Today', 'stat', $pharmacistRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalPrescription::where('status', 'dispensed')
                    ->whereDate('dispensed_at', today())->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-check-circle',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'pharmacist.low_stock_items', 'Low Stock Items', 'stat', $pharmacistRoles,
            function () {
                // NOTE: hand-built controller used `current_stock <= 10`
                // (a constant threshold) which 500s once any single drug
                // batch goes negative. Use the proper
                // `current_stock <= reorder_level` predicate instead,
                // matching HospitalAdminController::inventory() and
                // dashboard-widget-foundation-slice decisions.
                $count = \App\Models\Hospital\HospitalDrug::whereColumn('current_stock', '<=', 'reorder_level')->count();
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => $count > 0 ? 'danger' : 'success',
                    'icon' => 'fas fa-exclamation-triangle',
                    'href' => route('hospital.pharmacy.low-stock'),
                    'cta' => $count > 0 ? [
                        'label' => 'View Low Stock',
                        'icon' => 'fas fa-list',
                        'href' => route('hospital.pharmacy.low-stock'),
                        'color' => 'danger',
                    ] : null,
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'pharmacist.total_drugs', 'Total Drugs', 'stat', $pharmacistRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalDrug::count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-pills',
                'href' => route('hospital.pharmacy.drugs'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'pharmacist.pending_prescriptions_list', 'Pending Prescriptions', 'table', $pharmacistRoles,
            fn() => [
                'title' => 'Pending Prescriptions',
                'icon' => 'fas fa-prescription',
                'headers' => ['Rx No.', 'Patient', 'Doctor', 'Date', 'Items', 'Status'],
                'rows' => \App\Models\Hospital\HospitalPrescription::with(['patient', 'doctor', 'items'])
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(fn($p) => [
                        $p->prescription_number ?? 'N/A',
                        $p->patient->full_name ?? 'Unknown',
                        'Dr. ' . ($p->doctor->last_name ?? 'TBA'),
                        optional($p->created_at)->format('d M, h:i A') ?? 'N/A',
                        $p->items->count() . ' items',
                        'Pending',
                    ])
                    ->all(),
                'colspan' => 6,
                'empty_message' => 'No pending prescriptions',
            ],
            'widgets.table-card'
        ));
    }

    /**
     * Register widgets for the lab_scientist audience.
     *
     * Roles: lab_scientist, cmd. Four stat tiles. The hand-built
     * view also references `$stats['today_appointments']` — a key
     * the controller doesn't pass (pre-existing mismatch, out of
     * scope); we mirror the four keys it actually populates.
     *
     * The "Pending Lab Requests" table stays in chrome — it has
     * per-row action buttons (Collect Sample / Start Processing /
     * View) that don't fit the plain-text table-card partial.
     */
    private function registerLabWidgets(): void
    {
        $labRoles = ['lab_scientist', 'cmd'];

        WidgetRegistry::register(new WidgetDefinition(
            'lab.pending_requests', 'Pending Requests', 'stat', $labRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalLabRequest::where('status', 'pending')->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-hourglass-half',
                'href' => route('hospital.lab.requests'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'lab.in_progress', 'In Progress', 'stat', $labRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalLabRequest::where('status', 'in_progress')->count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-spinner',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'lab.completed_today', 'Completed Today', 'stat', $labRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalLabRequest::where('status', 'completed')
                    ->whereDate('completed_at', today())->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-check-circle',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'lab.total_tests', 'Total Tests', 'stat', $labRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalLabRequest::count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-flask',
            ],
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the matron audience.
     *
     * Roles: matron, cmd. Four stat tiles covering ward operations.
     * The Ward Occupancy progress bars and Upcoming Roster list
     * stay in chrome — they use progress-bar rendering and custom
     * two-column list markup that doesn't fit generic widgets yet.
     */
    private function registerMatronWidgets(): void
    {
        $matronRoles = ['matron', 'cmd'];

        WidgetRegistry::register(new WidgetDefinition(
            'matron.inpatients', 'Inpatients', 'stat', $matronRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAdmission::where('status', 'admitted')->count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-procedures',
                'href' => route('hospital.matron.rounds'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'matron.today_admissions', "Today's Admissions", 'stat', $matronRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAdmission::whereDate('admission_date', today())->count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-user-plus',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'matron.today_discharges', "Today's Discharges", 'stat', $matronRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAdmission::where('status', 'discharged')
                    ->whereDate('discharge_date', today())->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-sign-out-alt',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'matron.available_beds', 'Available Beds', 'stat', $matronRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalBed::where('status', 'available')->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-bed',
                'href' => route('hospital.wards.index'),
            ],
            'widgets.stat-card'
        ));
    }

    /**
     * Register widgets for the hospital_admin audience.
     *
     * Roles: hospital_admin, cmd. Eight stat tiles covering every
     * module the admin dashboard cares about (appointments,
     * admissions, beds, prescriptions, lab, revenue, low-stock,
     * patients). The hand-built view had a small helper-text
     * "<small class="text-muted">…</small>" under each tile (e.g.
     * "5 pending" under "Today's Appointments"). The generic
     * stat-card partial doesn't support that subtext; we drop the
     * subtext and let the value stand on its own. If a tile
     * genuinely needs a subtext, it's a follow-up partial
     * extension.
     *
     * The revenue tile uses `format: 'currency'` which the
     * stat-card partial renders as "₦1,234,567". The Low Stock
     * tile sets `color` conditionally (danger when count > 0,
     * success otherwise) to mirror the original
     * `text-{{ $count > 0 ? 'danger' : 'success' }}` override.
     *
     * The Revenue sparkline (last 14 days, hand-drawn progress
     * bars) and Recent Admissions cards stay in chrome.
     */
    private function registerHospitalAdminWidgets(): void
    {
        $adminRoles = ['hospital_admin', 'cmd'];

        WidgetRegistry::register(new WidgetDefinition(
            'hospital_admin.today_appointments', "Today's Appointments", 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAppointment::whereDate('appointment_date', today())->count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-calendar-day',
                'href' => route('hospital.appointments.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital_admin.pending_appointments', 'Pending Appointments', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAppointment::where('status', 'scheduled')->count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-clock',
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital_admin.inpatients', 'Inpatients', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalAdmission::where('status', 'admitted')->count(),
                'format' => 'number',
                'color' => 'success',
                'icon' => 'fas fa-procedures',
                'href' => route('hospital.matron.rounds'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital_admin.available_beds', 'Available Beds', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalBed::where('status', 'available')->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-bed',
                'href' => route('hospital.wards.occupancy'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital_admin.revenue_today', 'Revenue Today', 'stat', $adminRoles,
            fn() => [
                'value' => (float) \App\Models\Hospital\HospitalPayment::where('status', \App\Models\Hospital\HospitalPayment::STATUS_COMPLETED)
                    ->whereDate('payment_date', today())->sum('total_amount'),
                'format' => 'currency',
                'color' => 'success',
                'icon' => 'fas fa-coins',
                'href' => route('hospital.admin.revenue'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital_admin.pending_prescriptions', 'Pending Prescriptions', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalPrescription::where('status', 'pending')->count(),
                'format' => 'number',
                'color' => 'warning',
                'icon' => 'fas fa-prescription',
                'href' => route('hospital.pharmacy.prescriptions'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital_admin.pending_lab', 'Pending Lab', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalLabRequest::whereIn('status', ['pending', 'sample_collected'])->count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-flask',
                'href' => route('hospital.lab.requests'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital_admin.low_stock_items', 'Low-Stock Items', 'stat', $adminRoles,
            function () {
                $count = \App\Models\Hospital\HospitalDrug::whereColumn('current_stock', '<=', 'reorder_level')->count();
                return [
                    'value' => $count,
                    'format' => 'number',
                    'color' => $count > 0 ? 'danger' : 'success',
                    'icon' => 'fas fa-exclamation-triangle',
                    'href' => route('hospital.pharmacy.low-stock'),
                ];
            },
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'hospital_admin.total_patients', 'Total Patients', 'stat', $adminRoles,
            fn() => [
                'value' => \App\Models\Hospital\HospitalPatient::count(),
                'format' => 'number',
                'color' => 'primary',
                'icon' => 'fas fa-users',
                'href' => route('hospital.patients.index'),
            ],
            'widgets.stat-card'
        ));
    }

    /**
     * Register every dashboard widget the system ships with for the
     * finance audience (the routes/finance.php middleware: super_admin,
     * admin, finance, finance_officer, accountant, account_officer,
     * auditor, cashier, hospital_accountant, bursary_officer,
     * fees_officer, payment_officer, ict_admin).
     *
     * Overlaps with the bursar role list intentionally — finance and
     * bursar are different routes, so the same user never renders both
     * at once. The 4 stat tiles mirror the 4 large bg-tiles the finance
     * dashboard used to hand-build. Recent-transactions and
     * recent-receipts stay in chrome (they have inline match-based
     * badge colours that don't fit the plain-text table-card partial).
     */
    private function registerFinanceWidgets(): void
    {
        $financeRoles = [
            'finance', 'finance_officer', 'accountant', 'account_officer',
            'cashier', 'hospital_accountant', 'bursary_officer',
            'fees_officer', 'payment_officer', 'auditor',
            'bursar', 'audit_bursar', 'audit',
        ];

        // Mirror of Finance/DashboardController::index() so the resolver
        // returns the same numbers the controller used to compute. All
        // currency tiles use 'format: currency' to render the ₦-prefixed
        // display the shared stat-card partial produces.
        WidgetRegistry::register(new WidgetDefinition(
            'finance.today_income', "Today's Income", 'stat', $financeRoles,
            fn() => [
                'value' => (float) \App\Models\Finance\FinanceReceipt::whereDate('payment_date', today())->sum('amount'),
                'format' => 'currency',
                'color' => 'success',
                'icon' => 'fas fa-cash-register',
                'href' => route('finance.reports.daily'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'finance.monthly_income', 'Monthly Income', 'stat', $financeRoles,
            fn() => [
                'value' => (float) \App\Models\Finance\FinanceReceipt::whereMonth('payment_date', date('m'))
                    ->whereYear('payment_date', date('Y'))->sum('amount'),
                'format' => 'currency',
                'color' => 'primary',
                'icon' => 'fas fa-chart-line',
                'href' => route('finance.reports.monthly'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'finance.outstanding_invoices', 'Outstanding Invoices', 'stat', $financeRoles,
            fn() => [
                'value' => (float) \App\Models\Finance\FinanceInvoice::whereIn('status', ['pending', 'partial'])->sum('balance'),
                'format' => 'currency',
                'color' => 'warning',
                'icon' => 'fas fa-file-invoice-dollar',
                'href' => route('finance.invoices.index'),
            ],
            'widgets.stat-card'
        ));

        WidgetRegistry::register(new WidgetDefinition(
            'finance.active_budgets', 'Active Budgets', 'stat', $financeRoles,
            fn() => [
                'value' => \App\Models\Finance\FinanceBudget::where('status', 'active')->count(),
                'format' => 'number',
                'color' => 'info',
                'icon' => 'fas fa-wallet',
                'href' => route('finance.budgets.index'),
            ],
            'widgets.stat-card'
        ));
    }
}