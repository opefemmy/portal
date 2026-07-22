<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Institution Management Portal')</title>

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

            /* Bootstrap Variable Mapping */
            --primary: #247D57;
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
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
            background-color: #f5f6fa;
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
            background: var(--sidebar-hover);
            color: var(--sidebar-link);
            border-left: 3px solid var(--accent-wine);
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
    @yield('styles')
</head>
<body>
    @auth
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0" id="sidebar">
                <div class="text-center py-4">
                    <h4 class="text-white mb-0">
                        <i class="fas fa-university me-2"></i>IMP
                    </h4>
                    <small class="text-white-50">EKSCOTECH Portal</small>
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
        // Sidebar toggle
        $('#sidebarToggle').on('click', function() {
            $('#sidebar').toggleClass('show');
        });

        // DataTables initialization
        $(document).ready(function() {
            $('.datatable').each(function() {
                var table = $(this);
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

        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Dark Mode Toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');

        // Check saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        }

        themeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');

            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            } else {
                localStorage.setItem('theme', 'light');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        });
    </script>
    @yield('scripts')
</body>
</html>