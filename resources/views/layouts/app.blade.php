@php
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Cache;
    use App\Models\SystemSetting;
    $institutionShortName = 'EKSCOTECH';
    if (Schema::hasTable('system_settings')) {
        try {
            // Cache for 60 minutes to improve performance
            $institutionShortName = Cache::remember('institution_short_name', 60, fn() => SystemSetting::get('institution_short_name', 'EKSCOTECH'));
        } catch (\Exception $e) {
            $institutionShortName = 'EKSCOTECH';
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $institutionShortName . ' Portal')</title>

    <!-- Favicon / Institution Icon -->
    @php
    $institutionIcon = null;
    $iconExists = false;
    $publicIconExists = file_exists(public_path('images/icon.png'));
    try {
        $institutionIcon = Cache::remember('institution_icon', 60, fn() => \App\Models\SystemSetting::get('institution_icon'));
        $iconPath = $institutionIcon ? storage_path('app/public/' . $institutionIcon) : null;
        $iconExists = $institutionIcon && file_exists($iconPath);
    } catch (\Exception $e) {
        $iconExists = false;
    }
    @endphp
    @if($publicIconExists)
        <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}?v={{ time() }}">
        <link rel="apple-touch-icon" href="{{ asset('images/icon.png') }}?v={{ time() }}">
    @elseif($institutionIcon && $iconExists)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $institutionIcon) }}">
        <link rel="apple-touch-icon" href="{{ asset('storage/' . $institutionIcon) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">
    @endif

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS - Using Bootstrap 5 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.min.css">

    <style>
        :root {
            /* Custom Colors from Institution */
            --primary: #247D57;
            --primary-dark: #1E6A4A;
            --primary-light: #2E9A6B;
            --secondary: #6a1b9a;
            --accent: #247D57;
            --accent-wine: #82103C;
            --accent-wine-2: #9A1648;
            --hover-color: #EC4899;

            /* Global Hover Color */
            a:hover, .btn:hover, .nav-link:hover, .dropdown-item:hover {
                color: var(--hover-color) !important;
            }

            /* Bootstrap Colors */
            --blue: #007bff;
            --indigo: #6610f2;
            --purple: #6f42c1;
            --pink: #e83e8c;
            --red: #dc3545;
            --orange: #fd7e14;
            --yellow: #ffc107;
            --green: #28a745;
            --teal: #20c997;
            --cyan: #17a2b8;
            --white: #fff;
            --gray: #6c757d;
            --gray-dark: #343a40;

            /* Bootstrap Variable Mapping - Use Institution Colors */
            --primary: #247D57;
            --secondary: #6c757d;
            --success: #247D57;
            --info: #17a2b8;
            --warning: #f59e0b;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;

            /* Sidebar Colors */
            --sidebar-bg: #247D57;
            --sidebar-bg-dark: #1E6A4A;
            --sidebar-tree: #1F5F45;
            --sidebar-link: rgba(255, 255, 255, .9);
            --sidebar-link-muted: rgba(255, 255, 255, .75);
            --sidebar-hover: rgba(255, 255, 255, .08);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 1rem;
            font-weight: 500;
            background-color: #f5f6fa;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 700 !important;
        }

        .display-1, .display-2, .display-3, .display-4 {
            font-weight: 800 !important;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, var(--sidebar-bg-dark) 100%);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: var(--sidebar-link);
            padding: 12px 20px;
            border-radius: 5px;
            margin: 2px 8px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
            border-left: 4px solid #ffffff;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .sidebar .nav-link.active i {
            color: #ffffff !important;
        }

        .sidebar .nav-link.active .badge {
            background: #ffffff !important;
            color: var(--primary) !important;
        }

        .sidebar .nav-link i {
            width: 25px;
            color: var(--sidebar-link-muted);
        }

        .sidebar .nav-link:hover i,
        .sidebar .nav-link.active i {
            color: var(--sidebar-link);
        }

        /* Sidebar tree/collapse */
        .sidebar .collapse {
            background: var(--sidebar-tree);
            border-radius: 5px;
            margin: 5px 10px;
        }

        .sidebar .collapse .nav-link {
            padding: 10px 15px;
            font-size: 0.9rem;
        }

        /* Topbar */
        .topbar {
            background: var(--primary);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary-dark);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-success {
            background-color: var(--green);
            border-color: #218838;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-header.bg-primary {
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
        }

        .stat-card {
            border-left: 4px solid var(--primary);
        }

        .stat-card.success { border-left-color: var(--success); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.danger { border-left-color: var(--danger); }
        .stat-card.info { border-left-color: var(--info); }

        /* Links */
        a {
            color: var(--primary);
        }

        a:hover {
            color: var(--primary-dark);
        }

        /* Badges */
        .badge.bg-primary {
            background-color: var(--primary) !important;
        }

        /* Tables */
        .table thead th {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary-dark);
        }

        /* Form controls focus */
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(36, 125, 87, 0.25);
        }

        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }

        body.dark-mode .card {
            background-color: #2d2d2d;
            border-color: #404040;
            color: #e0e0e0;
        }

        body.dark-mode .card-header {
            background-color: #363636;
            border-color: #404040;
        }

        body.dark-mode .table {
            color: #e0e0e0;
        }

        body.dark-mode .table thead th {
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 5px;
            margin: 2px 8px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: var(--accent);
        }

        .sidebar .nav-link i {
            width: 25px;
        }

        .topbar {
            background: var(--accent);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stat-card {
            border-left: 4px solid var(--primary);
        }

        .stat-card.success { border-left-color: var(--success); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.danger { border-left-color: var(--danger); }
        .stat-card.info { border-left-color: var(--info); }

        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }

        body.dark-mode .card {
            background-color: #2d2d2d;
            border-color: #404040;
            color: #e0e0e0;
        }

        body.dark-mode .card-header {
            background-color: #363636;
            border-color: #404040;
        }

        body.dark-mode .table {
            color: #e0e0e0;
        }

        body.dark-mode .table thead th {
            background-color: #363636;
            border-color: #404040;
        }

        body.dark-mode .table td {
            border-color: #404040;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #363636;
            border-color: #404040;
            color: #e0e0e0;
        }

        body.dark-mode .modal-content {
            background-color: #2d2d2d;
            border-color: #404040;
        }

        body.dark-mode .dropdown-menu {
            background-color: #2d2d2d;
            border-color: #404040;
        }

        body.dark-mode .dropdown-item {
            color: #e0e0e0;
        }

        body.dark-mode .page-header {
            color: #e0e0e0;
        }

        body.dark-mode .text-muted {
            color: #a0a0a0 !important;
        }

        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
        }

        .alert {
            border-radius: 10px;
        }

        .page-header {
            background: var(--accent);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                z-index: 1000;
                width: 250px;
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }
    </style>
    {{-- Global print stylesheet. Hides the entire portal chrome
         (sidebar, topbar, navbar, breadcrumbs, action buttons) so any
         page that triggers window.print() — receipts, admission
         letters, exam clearance, course forms, results, ID cards —
         comes out as a clean printable sheet rather than the full
         dashboard. Views opt into the printable surface by wrapping
         their content in `.printable-container`. Per-view `@media
         print` rules (e.g. for watermark opacity bumps) still win
         because they come later in the cascade. --}}
    <style>
        @media print {
            /* Portal chrome — off. */
            .sidebar, .topbar, .navbar,
            .main-header, .main-footer,
            .breadcrumb, .page-actions,
            .alert.alert-info.no-print,
            .no-print { display: none !important; }

            /* Page title row + its button cluster (the "back" / "edit"
               buttons most page headers sit beside their title). */
            .page-header .btn,
            .page-header .actions,
            .d-flex.justify-content-between.flex-wrap.flex-md-nowrap { display: none !important; }

            /* Reset the layout for print. The dashboard layout uses
               Bootstrap container-fluid + px-4 padding that look fine
               on screen but waste paper. */
            body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
            .container-fluid, .container, .px-4, .py-4,
            .row, .col-md-12, main { padding: 0 !important; margin: 0 !important; }
            .wrapper { margin: 0 !important; padding: 0 !important; }

            /* Opt-in printable container — full width, no shadow, no
               border-radius, no border. Views that want a print-clean
               card wrap their content in this class. */
            .printable-container,
            .card.print-shadow-none {
                max-width: 100% !important;
                width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    @php
        $institutionName = 'Ekiti State College of Technology';
        $institutionShortName = 'EKSCOTECH';
        $institutionLogo = null;
        $logoExists = false;
        $publicLogoExists = file_exists(public_path('images/logo.png'));

        if (Schema::hasTable('system_settings')) {
            try {
                // Cache for 60 minutes for better performance
                $institutionName = Cache::remember('institution_name', 60, fn() => SystemSetting::get('institution_name', 'Ekiti State College of Technology'));
                $institutionShortName = Cache::remember('institution_short_name', 60, fn() => SystemSetting::get('institution_short_name', 'EKSCOTECH'));
                $institutionLogo = Cache::remember('institution_logo', 60, fn() => SystemSetting::get('institution_logo'));
                $logoPath = $institutionLogo ? storage_path('app/public/' . $institutionLogo) : null;
                $logoExists = $institutionLogo && file_exists($logoPath);
            } catch (\Exception $e) {
                // Use defaults
            }
        }
    @endphp
    @auth
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0" id="sidebar">
                <div class="text-center py-4">
                    @if($publicLogoExists)
                        <img src="{{ asset('images/logo.png') }}?v={{ time() }}" alt="Logo" class="mb-2" style="max-height: 50px;">
                    @elseif($institutionLogo && $logoExists)
                        <img src="{{ asset('storage/' . $institutionLogo) }}" alt="Logo" class="mb-2" style="max-height: 50px;">
                    @else
                        <h4 class="text-white mb-0">
                            <i class="fas fa-university me-2"></i>{{ $institutionShortName }}
                        </h4>
                    @endif
                    <small class="text-white-50">{{ $institutionName }}</small>
                </div>

                <ul class="nav flex-column py-2">
                    @include('layouts.sidebar')
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-auto">
                <!-- Topbar -->
                <div class="topbar py-3 px-4 mb-4 d-flex justify-content-between align-items-center">
                    <button class="btn btn-link text-dark d-md-none" id="sidebarToggle">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>

                    <div class="d-flex align-items-center gap-3">
                        <!-- Dark Mode Toggle -->
                        <button class="btn btn-outline-dark btn-sm" id="themeToggle" title="Toggle Dark/Light Mode">
                            <i class="fas fa-moon" id="themeIcon"></i>
                        </button>

                        <!-- Direct Logout Button -->
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </button>
                        </form>
                        <div class="dropdown">
                            <button class="btn btn-link text-dark dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>
                                {{ auth()->user()->name }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="{{ route('password.update') }}"><i class="fas fa-key me-2"></i>Change Password</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Page Content -->
                <main class="px-4">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>
    @else
    @yield('content')
    @endauth

    <!-- Scripts - Using jQuery 3.x and DataTables 1.13.x -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.min.js"></script>

    <script>
        // Sidebar toggle - check if element exists first
        if ($('#sidebarToggle').length) {
            $('#sidebarToggle').on('click', function() {
                $('#sidebar').toggleClass('show');
            });
        }

        // DataTables initialization
        $(document).ready(function() {
            $('.datatable').each(function() {
                var table = $(this);
                var thCount = table.find('thead th').length;
                var firstRowTdCount = table.find('tbody tr:first > td').length;
                // Skip when the body row is the empty-state row (one <td>
                // with a colspan matching the header). DataTables counts
                // <td> elements literally and would warn "Incorrect column
                // count" (https://datatables.net/tn/18) for that mismatch.
                if (thCount === 0 || firstRowTdCount < thCount) {
                    return;
                }
                if (table.find('thead').length > 0 && table.find('tbody tr').length > 0) {
                    table.DataTable({
                        processing: false,
                        responsive: true,
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                        destroy: true
                    });
                }
            });
        });

        // SweetAlert helpers
        function confirmDelete(formId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }

        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        }

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message
            });
        }

        // Flash messages
        @if(session('success'))
            showSuccess('{{ session("success") }}');
        @endif

        @if(session('error'))
            showError('{{ session("error") }}');
        @endif

        // CSRF token for AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Promote title attributes on buttons/links to Bootstrap tooltips
        // and initialize all tooltip triggers (data-bs-toggle or auto-promoted).
        // For icon-only buttons (those that contain only an <i> icon and no
        // visible text), we infer a sensible tooltip from the FontAwesome
        // class so every action button hints what it does.
        var iconTitles = {
            'fa-edit':          'Edit',
            'fa-pen':           'Edit',
            'fa-pencil-alt':    'Edit',
            'fa-trash':         'Delete',
            'fa-trash-alt':     'Delete',
            'fa-eye':           'View',
            'fa-eye-slash':     'Hide',
            'fa-download':      'Download',
            'fa-file-download': 'Download',
            'fa-print':         'Print',
            'fa-plus':          'Add new',
            'fa-plus-circle':   'Add new',
            'fa-check':         'Approve',
            'fa-check-circle':  'Approve',
            'fa-times':         'Cancel',
            'fa-times-circle':  'Cancel',
            'fa-ban':           'Disable',
            'fa-undo':          'Restore',
            'fa-redo':          'Re-apply',
            'fa-sync':          'Refresh',
            'fa-sync-alt':      'Refresh',
            'fa-upload':        'Upload',
            'fa-file-upload':   'Upload',
            'fa-file-import':   'Import',
            'fa-file-export':   'Export',
            'fa-paper-plane':   'Submit',
            'fa-save':          'Save',
            'fa-search':        'Search',
            'fa-filter':        'Filter',
            'fa-cog':           'Settings',
            'fa-cogs':          'Settings',
            'fa-wrench':        'Configure',
            'fa-key':           'Reset password',
            'fa-lock':          'Lock',
            'fa-unlock':        'Unlock',
            'fa-sign-in-alt':   'Sign in',
            'fa-sign-out-alt':  'Sign out',
            'fa-user-plus':     'Add user',
            'fa-graduation-cap':'View transcript',
            'fa-id-card':       'ID card',
            'fa-credit-card':   'Pay',
            'fa-dollar-sign':   'Pay fees',
            'fa-book':          'Courses',
            'fa-chart-line':    'Results',
            'fa-chart-bar':     'Statistics',
            'fa-arrow-left':    'Back',
            'fa-arrow-right':   'Next',
            'fa-broom':         'Reset',
        };

        function inferTitle(el) {
            // 1. If the element already has a title, use it.
            if (el.hasAttribute('title') && el.getAttribute('title').trim() !== '') {
                return el.getAttribute('title');
            }
            // 2. If the element has visible text content, use it.
            var text = (el.textContent || '').trim();
            if (text.length > 0 && text.length < 60) {
                return text;
            }
            // 3. Icon-only? Map from FontAwesome class to a default tooltip.
            var icon = el.querySelector('i[class*="fa-"]');
            if (icon) {
                var classes = (icon.className || '').split(/\s+/);
                for (var i = 0; i < classes.length; i++) {
                    if (iconTitles[classes[i]]) {
                        return iconTitles[classes[i]];
                    }
                }
            }
            return null;
        }

        document.querySelectorAll('button, a.btn, a.nav-link').forEach(function (el) {
            var title = inferTitle(el);
            if (!title) return;
            el.setAttribute('title', title);
            if (!el.hasAttribute('data-bs-toggle') || el.getAttribute('data-bs-toggle') === 'tooltip') {
                el.setAttribute('data-bs-toggle', 'tooltip');
                el.setAttribute('data-bs-placement', 'top');
            }
        });

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Dark Mode Toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');

        // Check saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark' && themeIcon) {
            document.body.classList.add('dark-mode');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        }

        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');

                if (document.body.classList.contains('dark-mode')) {
                    localStorage.setItem('theme', 'dark');
                    if (themeIcon) {
                        themeIcon.classList.remove('fa-moon');
                        themeIcon.classList.add('fa-sun');
                    }
                } else {
                    localStorage.setItem('theme', 'light');
                    if (themeIcon) {
                        themeIcon.classList.remove('fa-sun');
                        themeIcon.classList.add('fa-moon');
                    }
                }
            });
        }
    </script>
    @stack('scripts')
</body>
</html>