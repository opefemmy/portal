@auth
@php
$user = auth()->user();
$role = $user->role->slug ?? '';
@endphp

@if(in_array($role, ['super_admin', 'admin']))
<li class="nav-item">
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->is('admin/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.analytics') }}" class="nav-link {{ request()->is('admin/analytics*') ? 'active' : '' }}">
        <i class="fas fa-chart-line"></i> Analytics
    </a>
</li>
<li class="nav-item">
    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#maintenanceMenu">
        <i class="fas fa-tools"></i> System Maintenance <i class="fas fa-chevron-down float-end"></i>
    </a>
    <div class="collapse" id="maintenanceMenu">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.dashboard') }}" class="nav-link">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.health') }}" class="nav-link">
                    <i class="fas fa-heartbeat me-2"></i>Health Check
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.updates') }}" class="nav-link">
                    <i class="fas fa-sync me-2"></i>Update Manager
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.migrations') }}" class="nav-link">
                    <i class="fas fa-database me-2"></i>Migration Manager
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.database') }}" class="nav-link">
                    <i class="fas fa-server me-2"></i>Database Repair
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.modules') }}" class="nav-link">
                    <i class="fas fa-cubes me-2"></i>Module Scanner
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.permissions') }}" class="nav-link">
                    <i class="fas fa-shield-alt me-2"></i>Permissions
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.storage') }}" class="nav-link">
                    <i class="fas fa-folder me-2"></i>Storage Scanner
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.cache') }}" class="nav-link">
                    <i class="fas fa-broom me-2"></i>Cache Manager
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.backups') }}" class="nav-link">
                    <i class="fas fa-database me-2"></i>Backup Manager
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.logs') }}" class="nav-link">
                    <i class="fas fa-file-alt me-2"></i>Log Viewer
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.versions') }}" class="nav-link">
                    <i class="fas fa-tags me-2"></i>Version Manager
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.maintenance.report') }}" class="nav-link">
                    <i class="fas fa-chart-line me-2"></i>System Report
                </a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}">
        <i class="fas fa-users"></i> Users
    </a>
</li>
<li class="nav-item">
    <a href="#" class="nav-link {{ request()->is('admin/staff*') ? 'active' : '' }}" data-bs-toggle="collapse" data-bs-target="#staffMenu">
        <i class="fas fa-user-tie"></i> Staff <i class="fas fa-chevron-down float-end"></i>
    </a>
    <div class="collapse" id="staffMenu">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a href="{{ route('admin.staff.index') }}" class="nav-link {{ request()->is('admin/staff') && !request('role_slug') ? 'active' : '' }}">
                    <i class="fas fa-users me-2"></i>All Staff
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.staff.index', ['role_slug' => 'admin']) }}" class="nav-link {{ request('role_slug') == 'admin' ? 'active' : '' }}">
                    <i class="fas fa-user-shield me-2"></i>Administrators
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.staff.index', ['role_slug' => 'lecturer']) }}" class="nav-link {{ request('role_slug') == 'lecturer' ? 'active' : '' }}">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Lecturers
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.staff.index', ['role_slug' => 'hod']) }}" class="nav-link {{ request('role_slug') == 'hod' ? 'active' : '' }}">
                    <i class="fas fa-user-tie me-2"></i>HODs
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.staff.index', ['role_slug' => 'dean']) }}" class="nav-link {{ request('role_slug') == 'dean' ? 'active' : '' }}">
                    <i class="fas fa-user-graduate me-2"></i>Deans
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.staff.index', ['role_slug' => 'registrar']) }}" class="nav-link {{ request('role_slug') == 'registrar' ? 'active' : '' }}">
                    <i class="fas fa-file-signature me-2"></i>Registrars
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.staff.index', ['role_slug' => 'bursar']) }}" class="nav-link {{ request('role_slug') == 'bursar' ? 'active' : '' }}">
                    <i class="fas fa-money-bill-wave me-2"></i>Bursars
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.staff.index', ['role_slug' => 'librarian']) }}" class="nav-link {{ request('role_slug') == 'librarian' ? 'active' : '' }}">
                    <i class="fas fa-book-reader me-2"></i>Librarians
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.staff.index', ['role_slug' => 'ict_admin']) }}" class="nav-link {{ request('role_slug') == 'ict_admin' ? 'active' : '' }}">
                    <i class="fas fa-laptop-code me-2"></i>ICT Admin
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.staff.create') }}" class="nav-link {{ request()->is('admin/staff/create*') ? 'active' : '' }}">
                    <i class="fas fa-user-plus me-2"></i>Add New Staff
                </a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#institutionMenu">
        <i class="fas fa-school"></i> Institution Setup <i class="fas fa-chevron-down float-end"></i>
    </a>
    <div class="collapse" id="institutionMenu">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a href="{{ route('admin.schools.index') }}" class="nav-link {{ request()->is('admin/schools*') ? 'active' : '' }}">
                    <i class="fas fa-building me-2"></i>Schools
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.departments.index') }}" class="nav-link {{ request()->is('admin/departments*') ? 'active' : '' }}">
                    <i class="fas fa-building-columns me-2"></i>Departments
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.programmes.index') }}" class="nav-link {{ request()->is('admin/programmes*') ? 'active' : '' }}">
                    <i class="fas fa-graduation-cap me-2"></i>Programmes
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.sessions.index') }}" class="nav-link {{ request()->is('admin/sessions*') ? 'active' : '' }}">
                    <i class="fas fa-calendar me-2"></i>Sessions
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ url('/admin/admission-centres') }}" class="nav-link {{ request()->is('admin/admission-centres*') ? 'active' : '' }}">
                    <i class="fas fa-building me-2"></i>Admission Centres
                </a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#studentMenu">
        <i class="fas fa-user-graduate"></i> Manage Students <i class="fas fa-chevron-down float-end"></i>
    </a>
    <div class="collapse" id="studentMenu">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a href="{{ route('admin.students.import') }}" class="nav-link {{ request()->is('admin/students/import*') ? 'active' : '' }}">
                    <i class="fas fa-upload me-2"></i>Upload Student Data
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->is('admin/students') && !request()->is('admin/students/import*') ? 'active' : '' }}">
                    <i class="fas fa-list me-2"></i>View All Students
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.students.create') }}" class="nav-link {{ request()->is('admin/students/create*') ? 'active' : '' }}">
                    <i class="fas fa-plus me-2"></i>Add New Student
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.students.import') }}" class="nav-link {{ request()->is('admin/students/import*') ? 'active' : '' }}">
                    <i class="fas fa-key me-2"></i>Reset Student Password
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.complaints.index') }}" class="nav-link {{ request()->is('admin/complaints*') ? 'active' : '' }}">
                    <i class="fas fa-exclamation-circle me-2"></i>Student Complaints
                </a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#admissionMenu">
        <i class="fas fa-user-plus"></i> Manage Admission <i class="fas fa-chevron-down float-end"></i>
    </a>
    <div class="collapse" id="admissionMenu">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a href="{{ route('registrar.applications.index') }}" class="nav-link">
                    <i class="fas fa-file-contract me-2"></i>Manage Applications
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('registrar.applicants') }}" class="nav-link">
                    <i class="fas fa-users me-2"></i>All Applicants
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('registrar.applications.statistics') }}" class="nav-link">
                    <i class="fas fa-chart-bar me-2"></i>Application Statistics
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.reports.students') }}" class="nav-link">
                    <i class="fas fa-file-alt me-2"></i>Application Report
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('registrar.admission') }}" class="nav-link">
                    <i class="fas fa-user-plus me-2"></i>Admission List
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('registrar.admission.byDepartment') }}" class="nav-link">
                    <i class="fas fa-building-columns me-2"></i>Admission by Department
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('registrar.admission.uploadByDepartment') }}" class="nav-link">
                    <i class="fas fa-file-upload me-2"></i>Upload Admission List
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('registrar.applications.admitted') }}" class="nav-link">
                    <i class="fas fa-user-graduate me-2"></i>Admitted Students
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('registrar.admission.generateLetters') }}" class="nav-link">
                    <i class="fas fa-envelope me-2"></i>Generate Letters
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('registrar.admission.uploadTemplate') }}" class="nav-link">
                    <i class="fas fa-file-signature me-2"></i>Upload Letter Template
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('registrar.admission.settings') }}" class="nav-link">
                    <i class="fas fa-cogs me-2"></i>Admission Settings
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('registrar.admission.track') }}" class="nav-link">
                    <i class="fas fa-search me-2"></i>Track Admission
                </a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a href="{{ route('admin.sessions.index') }}" class="nav-link {{ request()->is('admin/sessions*') ? 'active' : '' }}">
        <i class="fas fa-calendar"></i> Sessions
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.courses.index') }}" class="nav-link {{ request()->is('admin/courses*') && !request()->is('admin/course-assignments*') ? 'active' : '' }}">
        <i class="fas fa-book"></i> Courses
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.course-assignments.index') }}" class="nav-link {{ request()->is('admin/course-assignments*') ? 'active' : '' }}">
        <i class="fas fa-chalkboard-teacher"></i> OnCourses
    </a>
</li>
<li class="nav-item">
    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#bursarMenu">
        <i class="fas fa-dollar-sign"></i> Bursar <i class="fas fa-chevron-down float-end"></i>
    </a>
    <div class="collapse" id="bursarMenu">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a href="{{ route('admin.fees.index') }}" class="nav-link {{ request()->is('admin/fees*') ? 'active' : '' }}">
                    <i class="fas fa-money-bill me-2"></i>Fees Configuration
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bursar.payments') }}" class="nav-link">
                    <i class="fas fa-receipt me-2"></i>View Payments
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bursar.payments.upload') }}" class="nav-link">
                    <i class="fas fa-file-upload me-2"></i>Upload External Payments
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bursar.payments.sync.index') }}" class="nav-link">
                    <i class="fas fa-sync-alt me-2"></i>Payment Synchronization
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bursar.regimes.index') }}" class="nav-link">
                    <i class="fas fa-calculator me-2"></i>Regime Payments
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bursar.reports') }}" class="nav-link">
                    <i class="fas fa-chart-bar me-2"></i>Financial Reports
                </a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#resultsMenu">
        <i class="fas fa-clipboard-check"></i> Results <i class="fas fa-chevron-down float-end"></i>
    </a>
    <div class="collapse" id="resultsMenu">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a href="{{ route('admin.results.index') }}" class="nav-link {{ request()->is('admin/results*') ? 'active' : '' }}">
                    <i class="fas fa-list me-2"></i>All Results
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.results.upload') }}" class="nav-link">
                    <i class="fas fa-upload me-2"></i>Upload Results
                </a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a href="{{ route('admin.grades.index') }}" class="nav-link {{ request()->is('admin/grades*') ? 'active' : '' }}">
        <i class="fas fa-star"></i> Grades
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}">
        <i class="fas fa-cogs"></i> Settings
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.reports') }}" class="nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}">
        <i class="fas fa-chart-bar"></i> Reports
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.notifications.index') }}" class="nav-link {{ request()->is('admin/notifications*') ? 'active' : '' }}">
        <i class="fas fa-bell"></i> Notifications
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.course-registrations.index') }}" class="nav-link {{ request()->is('admin/course-registrations*') ? 'active' : '' }}">
        <i class="fas fa-clipboard-list"></i> Course Registrations
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.id-cards.index') }}" class="nav-link {{ request()->is('admin/id-cards*') ? 'active' : '' }}">
        <i class="fas fa-id-card"></i> ID Cards
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.timetable.index') }}" class="nav-link {{ request()->is('admin/timetable*') ? 'active' : '' }}">
        <i class="fas fa-clipboard-check"></i> Timetable
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.transcripts.index') }}" class="nav-link {{ request()->is('admin/transcripts*') ? 'active' : '' }}">
        <i class="fas fa-file-signature"></i> Transcripts
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.library.books') }}" class="nav-link {{ request()->is('admin/library*') ? 'active' : '' }}">
        <i class="fas fa-book"></i> Library
    </a>
</li>
<li class="nav-item">
    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#hostelMenu">
        <i class="fas fa-bed"></i> Hostel <i class="fas fa-chevron-down float-end"></i>
    </a>
    <div class="collapse" id="hostelMenu">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a href="{{ route('admin.hostels.index') }}" class="nav-link {{ request()->is('admin/hostels') && !request()->is('admin/hostels/allocations*') ? 'active' : '' }}">
                    <i class="fas fa-building me-2"></i>Manage Hostels
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.hostels.allocations') }}" class="nav-link {{ request()->is('admin/hostels/allocations*') ? 'active' : '' }}">
                    <i class="fas fa-users me-2"></i>Allocations
                </a>
            </li>
        </ul>
    </div>
</li>
@elseif($role === 'student')
<li class="nav-item">
    <a href="{{ route('student.dashboard') }}" class="nav-link {{ request()->is('student/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('student.profile') }}" class="nav-link {{ request()->is('student/profile*') ? 'active' : '' }}">
        <i class="fas fa-user-cog"></i> My Profile
    </a>
</li>
<li class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fas fa-graduation-cap"></i> Academic <span class="caret"></span>
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="{{ route('student.courses') }}"><i class="fas fa-book me-2"></i>My Courses</a></li>
        <li><a class="dropdown-item" href="{{ route('student.results') }}"><i class="fas fa-chart-line me-2"></i>Results</a></li>
        <li><a class="dropdown-item" href="{{ route('student.timetable') }}"><i class="fas fa-calendar-alt me-2"></i>Timetable</a></li>
    </ul>
</li>
<li class="nav-item">
    <a href="{{ route('student.payments') }}" class="nav-link {{ request()->is('student/payments*') ? 'active' : '' }}">
        <i class="fas fa-dollar-sign"></i> Payments
    </a>
</li>
<li class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fas fa-bed"></i> Hostel & Accommodation <span class="caret"></span>
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="{{ route('student.hostel.my') }}"><i class="fas fa-home me-2"></i>My Hostel</a></li>
        <li><a class="dropdown-item" href="{{ route('hostel.apply') }}"><i class="fas fa-plus me-2"></i>Apply for Hostel</a></li>
    </ul>
</li>
<li class="nav-item">
    <a href="{{ route('student.library') }}" class="nav-link {{ request()->is('student/library*') ? 'active' : '' }}">
        <i class="fas fa-book-open"></i> Library
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('student.complaints') }}" class="nav-link {{ request()->is('student/complaints*') ? 'active' : '' }}">
        <i class="fas fa-exclamation-circle"></i> Complaints & Support
    </a>
</li>
<li class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fas fa-hospital"></i> Medical Center <span class="caret"></span>
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="{{ route('student.medical.index') }}"><i class="fas fa-home me-2"></i>Medical Portal</a></li>
        <li><a class="dropdown-item" href="{{ route('student.medical.book') }}"><i class="fas fa-calendar-plus me-2"></i>Book Appointment</a></li>
        <li><a class="dropdown-item" href="{{ route('student.medical.appointments') }}"><i class="fas fa-calendar me-2"></i>My Appointments</a></li>
        <li><a class="dropdown-item" href="{{ route('student.medical.history') }}"><i class="fas fa-file-medical me-2"></i>Medical History</a></li>
        <li><a class="dropdown-item" href="{{ route('student.medical.prescriptions') }}"><i class="fas fa-prescription me-2"></i>Prescriptions</a></li>
        <li><a class="dropdown-item" href="{{ route('student.medical.lab-results') }}"><i class="fas fa-vial me-2"></i>Lab Results</a></li>
        <li><a class="dropdown-item" href="{{ route('student.medical.admissions') }}"><i class="fas fa-procedures me-2"></i>Admissions</a></li>
    </ul>
</li>
@elseif($role === 'lecturer')
<li class="nav-item">
    <a href="{{ route('lecturer.dashboard') }}" class="nav-link {{ request()->is('lecturer/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('lecturer.courses') }}" class="nav-link {{ request()->is('lecturer/courses*') ? 'active' : '' }}">
        <i class="fas fa-book"></i> My Courses
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('lecturer.timetable') }}" class="nav-link {{ request()->is('lecturer/timetable*') ? 'active' : '' }}">
        <i class="fas fa-calendar-alt"></i> Timetable
    </a>
</li>
@elseif($role === 'hod')
<li class="nav-item">
    <a href="{{ route('hod.dashboard') }}" class="nav-link {{ request()->is('hod/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('hod.courses') }}" class="nav-link {{ request()->is('hod/courses*') ? 'active' : '' }}">
        <i class="fas fa-book"></i> Courses
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('hod.timetable') }}" class="nav-link {{ request()->is('hod/timetable*') ? 'active' : '' }}">
        <i class="fas fa-calendar-alt"></i> Timetable
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('hod.results') }}" class="nav-link {{ request()->is('hod/results*') ? 'active' : '' }}">
        <i class="fas fa-check-circle"></i> Results
    </a>
</li>
@elseif($role === 'registrar')
<li class="nav-item">
    <a href="{{ route('registrar.dashboard') }}" class="nav-link {{ request()->is('registrar/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('registrar.applications.index') }}" class="nav-link {{ request()->is('registrar/applications*') ? 'active' : '' }}">
        <i class="fas fa-file-alt"></i> Applications
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('registrar.applications.statistics') }}" class="nav-link {{ request()->is('registrar/applications/statistics*') ? 'active' : '' }}">
        <i class="fas fa-chart-bar"></i> Statistics
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('registrar.applications.admitted') }}" class="nav-link {{ request()->is('registrar/admitted*') ? 'active' : '' }}">
        <i class="fas fa-user-graduate"></i> Admitted Students
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('registrar.applicants') }}" class="nav-link {{ request()->is('registrar/applicants*') ? 'active' : '' }}">
        <i class="fas fa-user-graduate"></i> Old Applicants
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->is('admin/students*') ? 'active' : '' }}">
        <i class="fas fa-users"></i> Students
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('registrar.admission') }}" class="nav-link {{ request()->is('registrar/admission*') ? 'active' : '' }}">
        <i class="fas fa-user-plus"></i> Admission List
    </a>
</li>
@elseif($role === 'bursar')
<li class="nav-item">
    <a href="{{ route('bursar.dashboard') }}" class="nav-link {{ request()->is('bursar/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('bursar.payments') }}" class="nav-link {{ request()->is('bursar/payments*') ? 'active' : '' }}">
        <i class="fas fa-dollar-sign"></i> Payments
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('bursar.payments.upload') }}" class="nav-link {{ request()->is('bursar/payments/upload*') ? 'active' : '' }}">
        <i class="fas fa-file-upload"></i> Upload External
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('bursar.payments.sync.index') }}" class="nav-link {{ request()->is('bursar/payments/sync*') ? 'active' : '' }}">
        <i class="fas fa-sync-alt"></i> Payment Sync
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('bursar.regimes.index') }}" class="nav-link {{ request()->is('bursar/regimes*') ? 'active' : '' }}">
        <i class="fas fa-calculator"></i> Regime Payments
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('bursar.reports') }}" class="nav-link {{ request()->is('bursar/reports*') ? 'active' : '' }}">
        <i class="fas fa-chart-bar"></i> Reports
    </a>
</li>
{{-- BUSINESS COMMITTEE DASHBOARD --}}
@elseif($role === 'business_committee')
<li class="nav-item">
    <a href="{{ route('business-committee.dashboard') }}" class="nav-link {{ request()->is('business-committee/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('business-committee.results') }}" class="nav-link {{ request()->is('business-committee/results*') ? 'active' : '' }}">
        <i class="fas fa-check-circle"></i> Approve Results
    </a>
</li>
{{-- ACADEMIC BOARD DASHBOARD --}}
@elseif($role === 'academic_board')
<li class="nav-item">
    <a href="{{ route('academic-board.dashboard') }}" class="nav-link {{ request()->is('academic-board/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('academic-board.results') }}" class="nav-link {{ request()->is('academic-board/results*') ? 'active' : '' }}">
        <i class="fas fa-gavel"></i> Final Approval
    </a>
</li>
{{-- HOSPITAL MODULE --}}
@elseif(in_array($role, ['cmd', 'doctor', 'nurse', 'hospital_receptionist', 'pharmacist', 'lab_scientist', 'store_keeper', 'super_admin']))
<li class="nav-item">
    <a href="{{ route('hospital.dashboard') }}" class="nav-link {{ request()->is('hospital*') ? 'active' : '' }}">
        <i class="fas fa-hospital"></i> Hospital
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('hospital.patients.index') }}" class="nav-link {{ request()->is('hospital/patients*') ? 'active' : '' }}">
        <i class="fas fa-users"></i> Patients
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('hospital.appointments.index') }}" class="nav-link {{ request()->is('hospital/appointments*') ? 'active' : '' }}">
        <i class="fas fa-calendar-check"></i> Appointments
    </a>
</li>
@if(in_array($role, ['pharmacist', 'cmd', 'super_admin']))
<li class="nav-item">
    <a href="{{ route('hospital.pharmacy.drugs') }}" class="nav-link {{ request()->is('hospital/pharmacy*') ? 'active' : '' }}">
        <i class="fas fa-pills"></i> Pharmacy
    </a>
</li>
@endif
@if(in_array($role, ['lab_scientist', 'cmd', 'super_admin']))
<li class="nav-item">
    <a href="{{ route('hospital.lab.index') }}" class="nav-link {{ request()->is('hospital/lab*') ? 'active' : '' }}">
        <i class="fas fa-flask"></i> Laboratory
    </a>
</li>
@endif
{{-- FINANCE MODULE --}}
@elseif(in_array($role, ['accountant', 'cashier', 'cmd', 'super_admin']))
<li class="nav-item">
    <a href="{{ route('finance.dashboard') }}" class="nav-link {{ request()->is('finance*') ? 'active' : '' }}">
        <i class="fas fa-chart-line"></i> Finance
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('finance.invoices.index') }}" class="nav-link {{ request()->is('finance/invoices*') ? 'active' : '' }}">
        <i class="fas fa-file-invoice"></i> Invoices
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('finance.receipts.index') }}" class="nav-link {{ request()->is('finance/receipts*') ? 'active' : '' }}">
        <i class="fas fa-receipt"></i> Receipts
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('finance.transactions.index') }}" class="nav-link {{ request()->is('finance/transactions*') ? 'active' : '' }}">
        <i class="fas fa-exchange-alt"></i> Transactions
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('finance.budgets.index') }}" class="nav-link {{ request()->is('finance/budgets*') ? 'active' : '' }}">
        <i class="fas fa-budget"></i> Budgets
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('finance.payroll.index') }}" class="nav-link {{ request()->is('finance/payroll*') ? 'active' : '' }}">
        <i class="fas fa-money-bill-wave"></i> Payroll
    </a>
</li>
{{-- RECTOR / EXECUTIVE DASHBOARD --}}
@elseif(in_array($role, ['rector', 'super_admin']))
<li class="nav-item">
    <a href="{{ route('executive.dashboard') }}" class="nav-link {{ request()->is('executive*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Executive Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('executive.reports.students') }}" class="nav-link {{ request()->is('executive/reports*') ? 'active' : '' }}">
        <i class="fas fa-chart-bar"></i> Reports
    </a>
</li>
{{-- LIBRARIAN DASHBOARD --}}
@elseif($role === 'librarian')
<li class="nav-item">
    <a href="{{ route('librarian.dashboard') }}" class="nav-link {{ request()->is('librarian/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('librarian.books') }}" class="nav-link {{ request()->is('librarian/books*') ? 'active' : '' }}">
        <i class="fas fa-book"></i> Books
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('librarian.loans') }}" class="nav-link {{ request()->is('librarian/loans*') ? 'active' : '' }}">
        <i class="fas fa-exchange-alt"></i> Loans
    </a>
</li>
{{-- AUDITOR DASHBOARD --}}
@elseif($role === 'auditor')
<li class="nav-item">
    <a href="{{ route('auditor.dashboard') }}" class="nav-link {{ request()->is('auditor/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('auditor.audit-logs') }}" class="nav-link {{ request()->is('auditor/audit-logs*') ? 'active' : '' }}">
        <i class="fas fa-history"></i> Audit Logs
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('auditor.deleted') }}" class="nav-link {{ request()->is('auditor/deleted*') ? 'active' : '' }}">
        <i class="fas fa-trash-restore"></i> Deleted Records
    </a>
</li>
@elseif($role === 'applicant')
<li class="nav-item">
    <a href="{{ route('applicant.dashboard') }}" class="nav-link {{ request()->is('applicant/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('applicant.apply') }}" class="nav-link {{ request()->is('applicant/apply*') ? 'active' : '' }}">
        <i class="fas fa-edit"></i> Apply
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('applicant.application') }}" class="nav-link {{ request()->is('applicant/application*') ? 'active' : '' }}">
        <i class="fas fa-file-alt"></i> My Application
    </a>
</li>
@endif

<li class="nav-item">
    <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->is('notifications*') ? 'active' : '' }}">
        <i class="fas fa-bell"></i> Notifications
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('profile.show') }}" class="nav-link {{ request()->is('profile*') ? 'active' : '' }}">
        <i class="fas fa-user"></i> Profile
    </a>
</li>
@endauth