<?php if(auth()->guard()->check()): ?>
<?php
$user = auth()->user();
$role = $user->role->slug ?? '';
?>

<?php if(in_array($role, ['super_admin', 'admin'])): ?>
<li class="nav-item">
    <a href="<?php echo e(route('admin.dashboard')); ?>" class="nav-link <?php echo e(request()->is('admin/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.analytics')); ?>" class="nav-link <?php echo e(request()->is('admin/analytics*') ? 'active' : ''); ?>">
        <i class="fas fa-chart-line"></i> Analytics
    </a>
</li>
<?php if($role === 'super_admin'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('admin.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('admin/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<?php endif; ?>
<li class="nav-item">
    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#maintenanceMenu">
        <i class="fas fa-tools"></i> System Maintenance <i class="fas fa-chevron-down float-end"></i>
    </a>
    <div class="collapse" id="maintenanceMenu">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.dashboard')); ?>" class="nav-link">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.health')); ?>" class="nav-link">
                    <i class="fas fa-heartbeat me-2"></i>Health Check
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.updates')); ?>" class="nav-link">
                    <i class="fas fa-sync me-2"></i>Update Manager
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.migrations')); ?>" class="nav-link">
                    <i class="fas fa-database me-2"></i>Migration Manager
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.database')); ?>" class="nav-link">
                    <i class="fas fa-server me-2"></i>Database Repair
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.modules')); ?>" class="nav-link">
                    <i class="fas fa-cubes me-2"></i>Module Scanner
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.permissions')); ?>" class="nav-link">
                    <i class="fas fa-shield-alt me-2"></i>Permissions
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.storage')); ?>" class="nav-link">
                    <i class="fas fa-folder me-2"></i>Storage Scanner
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.cache')); ?>" class="nav-link">
                    <i class="fas fa-broom me-2"></i>Cache Manager
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.backups')); ?>" class="nav-link">
                    <i class="fas fa-database me-2"></i>Backup Manager
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.logs')); ?>" class="nav-link">
                    <i class="fas fa-file-alt me-2"></i>Log Viewer
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.versions')); ?>" class="nav-link">
                    <i class="fas fa-tags me-2"></i>Version Manager
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.maintenance.report')); ?>" class="nav-link">
                    <i class="fas fa-chart-line me-2"></i>System Report
                </a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.users.index')); ?>" class="nav-link <?php echo e(request()->is('admin/users*') && !request()->is('admin/users/unlock*') ? 'active' : ''); ?>">
        <i class="fas fa-users"></i> Users
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.users.unlock')); ?>" class="nav-link <?php echo e(request()->is('admin/users/unlock*') ? 'active' : ''); ?>">
        <i class="fas fa-unlock-alt"></i> Unlock Users
    </a>
</li>
<li class="nav-item">
    <a href="#" class="nav-link <?php echo e(request()->is('admin/staff*') ? 'active' : ''); ?>" data-bs-toggle="collapse" data-bs-target="#staffMenu">
        <i class="fas fa-user-tie"></i> Staff <i class="fas fa-chevron-down float-end"></i>
    </a>
    <div class="collapse" id="staffMenu">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index')); ?>" class="nav-link <?php echo e(request()->is('admin/staff') && !request('role_slug') ? 'active' : ''); ?>">
                    <i class="fas fa-users me-2"></i>All Staff
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'admin'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'admin' ? 'active' : ''); ?>">
                    <i class="fas fa-user-shield me-2"></i>Administrators
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'lecturer'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'lecturer' ? 'active' : ''); ?>">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Lecturers
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'hod'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'hod' ? 'active' : ''); ?>">
                    <i class="fas fa-user-tie me-2"></i>HODs
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'dean'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'dean' ? 'active' : ''); ?>">
                    <i class="fas fa-user-graduate me-2"></i>Deans
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'registrar'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'registrar' ? 'active' : ''); ?>">
                    <i class="fas fa-file-signature me-2"></i>Registrars
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'bursar'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'bursar' ? 'active' : ''); ?>">
                    <i class="fas fa-money-bill-wave me-2"></i>Bursars
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'bursary_officer'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'bursary_officer' ? 'active' : ''); ?>">
                    <i class="fas fa-receipt me-2"></i>Bursary Officers
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'fees_officer'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'fees_officer' ? 'active' : ''); ?>">
                    <i class="fas fa-tags me-2"></i>Fees Officers
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'payment_officer'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'payment_officer' ? 'active' : ''); ?>">
                    <i class="fas fa-credit-card me-2"></i>Payment Officers
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'cashier'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'cashier' ? 'active' : ''); ?>">
                    <i class="fas fa-cash-register me-2"></i>Cashiers
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'accountant'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'accountant' ? 'active' : ''); ?>">
                    <i class="fas fa-calculator me-2"></i>Accountants
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'auditor'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'auditor' ? 'active' : ''); ?>">
                    <i class="fas fa-search-dollar me-2"></i>Auditors
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'librarian'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'librarian' ? 'active' : ''); ?>">
                    <i class="fas fa-book-reader me-2"></i>Librarians
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'library_officer'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'library_officer' ? 'active' : ''); ?>">
                    <i class="fas fa-book-open me-2"></i>Library Officers
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'library_assistant'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'library_assistant' ? 'active' : ''); ?>">
                    <i class="fas fa-bookmark me-2"></i>Library Assistants
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'ict_admin'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'ict_admin' ? 'active' : ''); ?>">
                    <i class="fas fa-laptop-code me-2"></i>ICT Admin
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'cmd'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'cmd' ? 'active' : ''); ?>">
                    <i class="fas fa-user-md me-2"></i>Chief Medical Director
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'doctor'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'doctor' ? 'active' : ''); ?>">
                    <i class="fas fa-stethoscope me-2"></i>Doctors
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'nurse'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'nurse' ? 'active' : ''); ?>">
                    <i class="fas fa-user-nurse me-2"></i>Nurses
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'pharmacist'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'pharmacist' ? 'active' : ''); ?>">
                    <i class="fas fa-pills me-2"></i>Pharmacists
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'lab_scientist'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'lab_scientist' ? 'active' : ''); ?>">
                    <i class="fas fa-flask me-2"></i>Lab Scientists
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.index', ['role_slug' => 'admission_officer'])); ?>" class="nav-link <?php echo e(request('role_slug') == 'admission_officer' ? 'active' : ''); ?>">
                    <i class="fas fa-user-check me-2"></i>Admission Officers
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.staff.create')); ?>" class="nav-link <?php echo e(request()->is('admin/staff/create*') ? 'active' : ''); ?>">
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
                <a href="<?php echo e(route('admin.schools.index')); ?>" class="nav-link <?php echo e(request()->is('admin/schools*') ? 'active' : ''); ?>">
                    <i class="fas fa-building me-2"></i>Schools
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.departments.index')); ?>" class="nav-link <?php echo e(request()->is('admin/departments*') ? 'active' : ''); ?>">
                    <i class="fas fa-building-columns me-2"></i>Departments
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.programmes.index')); ?>" class="nav-link <?php echo e(request()->is('admin/programmes*') ? 'active' : ''); ?>">
                    <i class="fas fa-graduation-cap me-2"></i>Programmes
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.sessions.index')); ?>" class="nav-link <?php echo e(request()->is('admin/sessions*') ? 'active' : ''); ?>">
                    <i class="fas fa-calendar me-2"></i>Sessions
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(url('/admin/admission-centres')); ?>" class="nav-link <?php echo e(request()->is('admin/admission-centres*') ? 'active' : ''); ?>">
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
                <a href="<?php echo e(route('admin.students.import')); ?>" class="nav-link <?php echo e(request()->is('admin/students/import*') ? 'active' : ''); ?>">
                    <i class="fas fa-upload me-2"></i>Upload Student Data
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.students.index')); ?>" class="nav-link <?php echo e(request()->is('admin/students') && !request()->is('admin/students/import*') ? 'active' : ''); ?>">
                    <i class="fas fa-list me-2"></i>View All Students
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.students.create')); ?>" class="nav-link <?php echo e(request()->is('admin/students/create*') ? 'active' : ''); ?>">
                    <i class="fas fa-plus me-2"></i>Add New Student
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.students.import')); ?>" class="nav-link <?php echo e(request()->is('admin/students/import*') ? 'active' : ''); ?>">
                    <i class="fas fa-upload me-2"></i>Import Students
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.students.measurements.export')); ?>" class="nav-link <?php echo e(request()->is('admin/students/measurements*') ? 'active' : ''); ?>">
                    <i class="fas fa-tshirt me-2"></i>Uniform Measurements
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.complaints.index')); ?>" class="nav-link <?php echo e(request()->is('admin/complaints*') ? 'active' : ''); ?>">
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
                <a href="<?php echo e(route('registrar.applications.index')); ?>" class="nav-link">
                    <i class="fas fa-file-contract me-2"></i>Manage Applications
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('registrar.applicants')); ?>" class="nav-link">
                    <i class="fas fa-users me-2"></i>All Applicants
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('registrar.applications.statistics')); ?>" class="nav-link">
                    <i class="fas fa-chart-bar me-2"></i>Application Statistics
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.reports.applications')); ?>" class="nav-link">
                    <i class="fas fa-file-alt me-2"></i>Application Report
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('registrar.admission')); ?>" class="nav-link">
                    <i class="fas fa-user-plus me-2"></i>Admission List
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('registrar.admission.byDepartment')); ?>" class="nav-link">
                    <i class="fas fa-building-columns me-2"></i>Admission by Department
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('registrar.admission.uploadByDepartment')); ?>" class="nav-link">
                    <i class="fas fa-file-upload me-2"></i>Upload Admission List
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('registrar.applications.admitted')); ?>" class="nav-link">
                    <i class="fas fa-user-graduate me-2"></i>Admitted Students
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('registrar.admission.generateLetters')); ?>" class="nav-link">
                    <i class="fas fa-envelope me-2"></i>Generate Letters
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('registrar.admission.uploadTemplate')); ?>" class="nav-link">
                    <i class="fas fa-file-signature me-2"></i>Upload Letter Template
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('registrar.admission.settings')); ?>" class="nav-link">
                    <i class="fas fa-cogs me-2"></i>Admission Settings
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('registrar.admission.track')); ?>" class="nav-link">
                    <i class="fas fa-search me-2"></i>Track Admission
                </a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.sessions.index')); ?>" class="nav-link <?php echo e(request()->is('admin/sessions*') ? 'active' : ''); ?>">
        <i class="fas fa-calendar"></i> Sessions
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.courses.index')); ?>" class="nav-link <?php echo e(request()->is('admin/courses*') && !request()->is('admin/course-assignments*') ? 'active' : ''); ?>">
        <i class="fas fa-book"></i> Courses
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.course-assignments.index')); ?>" class="nav-link <?php echo e(request()->is('admin/course-assignments*') ? 'active' : ''); ?>">
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
                <a href="<?php echo e(route('admin.fees.index')); ?>" class="nav-link <?php echo e(request()->is('admin/fees*') ? 'active' : ''); ?>">
                    <i class="fas fa-money-bill me-2"></i>Fees Configuration
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.fees.index')); ?>" class="nav-link <?php echo e(request()->is('admin/fees*') ? 'active' : ''); ?>">
                    <i class="fas fa-money-bill me-2"></i>Fees
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.payment-types.index')); ?>" class="nav-link <?php echo e(request()->is('admin/payment-types*') ? 'active' : ''); ?>">
                    <i class="fas fa-tags me-2"></i>Payment Types
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(url('/bursar/payments')); ?>" class="nav-link">
                    <i class="fas fa-receipt me-2"></i>View Payments
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(url('/bursar/payments/upload')); ?>" class="nav-link">
                    <i class="fas fa-file-upload me-2"></i>Upload External Payments
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.hospital-services.index')); ?>" class="nav-link <?php echo e(request()->is('admin/hospital-services*') ? 'active' : ''); ?>">
                    <i class="fas fa-hospital me-2"></i>Hospital Services
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(url('/bursar/payments/sync')); ?>" class="nav-link">
                    <i class="fas fa-sync-alt me-2"></i>Payment Synchronization
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('bursar.regimes.index')); ?>" class="nav-link">
                    <i class="fas fa-calculator me-2"></i>Regime Payments
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('bursar.reports')); ?>" class="nav-link">
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
                <a href="<?php echo e(route('admin.results.index')); ?>" class="nav-link <?php echo e(request()->is('admin/results*') ? 'active' : ''); ?>">
                    <i class="fas fa-list me-2"></i>All Results
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.results.upload')); ?>" class="nav-link">
                    <i class="fas fa-upload me-2"></i>Upload Results
                </a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.grades.index')); ?>" class="nav-link <?php echo e(request()->is('admin/grades*') ? 'active' : ''); ?>">
        <i class="fas fa-star"></i> Grades
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.settings.index')); ?>" class="nav-link <?php echo e(request()->is('admin/settings*') ? 'active' : ''); ?>">
        <i class="fas fa-cogs"></i> Settings
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.reports')); ?>" class="nav-link <?php echo e(request()->is('admin/reports*') ? 'active' : ''); ?>">
        <i class="fas fa-chart-bar"></i> Reports
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.notifications.index')); ?>" class="nav-link <?php echo e(request()->is('admin/notifications*') ? 'active' : ''); ?>">
        <i class="fas fa-bell"></i> Notifications
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.course-registrations.index')); ?>" class="nav-link <?php echo e(request()->is('admin/course-registrations*') ? 'active' : ''); ?>">
        <i class="fas fa-clipboard-list"></i> Course Registrations
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.id-cards.index')); ?>" class="nav-link <?php echo e(request()->is('admin/id-cards*') ? 'active' : ''); ?>">
        <i class="fas fa-id-card"></i> ID Cards
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.timetable.index')); ?>" class="nav-link <?php echo e(request()->is('admin/timetable*') ? 'active' : ''); ?>">
        <i class="fas fa-clipboard-check"></i> Timetable
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.transcripts.index')); ?>" class="nav-link <?php echo e(request()->is('admin/transcripts*') ? 'active' : ''); ?>">
        <i class="fas fa-file-signature"></i> Transcripts
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.previous-results.index')); ?>" class="nav-link <?php echo e(request()->is('admin/previous-results*') ? 'active' : ''); ?>">
        <i class="fas fa-history"></i> Previous Results
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.library.books')); ?>" class="nav-link <?php echo e(request()->is('admin/library*') ? 'active' : ''); ?>">
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
                <a href="<?php echo e(route('admin.hostels.index')); ?>" class="nav-link <?php echo e(request()->is('admin/hostels') && !request()->is('admin/hostels/allocations*') ? 'active' : ''); ?>">
                    <i class="fas fa-building me-2"></i>Manage Hostels
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('admin.hostels.allocations')); ?>" class="nav-link <?php echo e(request()->is('admin/hostels/allocations*') ? 'active' : ''); ?>">
                    <i class="fas fa-users me-2"></i>Allocations
                </a>
            </li>
        </ul>
    </div>
</li>
<?php elseif($role === 'student'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('student.dashboard')); ?>" class="nav-link <?php echo e(request()->is('student/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('student/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.profile')); ?>" class="nav-link <?php echo e(request()->is('student/profile*') ? 'active' : ''); ?>">
        <i class="fas fa-user-cog"></i> My Profile
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.measurements')); ?>" class="nav-link <?php echo e(request()->is('student/measurements*') ? 'active' : ''); ?>">
        <i class="fas fa-tshirt"></i> My Measurements
    </a>
</li>
<li class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fas fa-graduation-cap"></i> Academic <span class="caret"></span>
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo e(route('student.courses')); ?>"><i class="fas fa-book me-2"></i>My Courses</a></li>
        <li><a class="dropdown-item" href="<?php echo e(route('student.results')); ?>"><i class="fas fa-chart-line me-2"></i>Results</a></li>
        <li><a class="dropdown-item" href="<?php echo e(route('student.timetable')); ?>"><i class="fas fa-calendar-alt me-2"></i>Timetable</a></li>
    </ul>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.payments')); ?>" class="nav-link <?php echo e(request()->is('student/payments*') ? 'active' : ''); ?>">
        <i class="fas fa-dollar-sign"></i> Payments
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.exam-clearance')); ?>" class="nav-link <?php echo e(request()->is('student/exam-clearance*') ? 'active' : ''); ?>">
        <i class="fas fa-file-alt"></i> Exam Clearance
    </a>
</li>
<li class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fas fa-bed"></i> Hostel & Accommodation <span class="caret"></span>
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo e(route('student.hostel.my')); ?>"><i class="fas fa-home me-2"></i>My Hostel</a></li>
        <li><a class="dropdown-item" href="<?php echo e(route('student.hostel.apply')); ?>"><i class="fas fa-plus me-2"></i>Apply for Hostel</a></li>
    </ul>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.library')); ?>" class="nav-link <?php echo e(request()->is('student/library*') ? 'active' : ''); ?>">
        <i class="fas fa-book-open"></i> Library
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.complaints')); ?>" class="nav-link <?php echo e(request()->is('student/complaints*') ? 'active' : ''); ?>">
        <i class="fas fa-exclamation-circle"></i> Complaints & Support
    </a>
</li>
<li class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fas fa-hospital"></i> Medical Center <span class="caret"></span>
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo e(route('student.medical.index')); ?>"><i class="fas fa-home me-2"></i>Medical Portal</a></li>
        <li><a class="dropdown-item" href="<?php echo e(route('student.medical.book')); ?>"><i class="fas fa-calendar-plus me-2"></i>Book Appointment</a></li>
        <li><a class="dropdown-item" href="<?php echo e(route('student.medical.appointments')); ?>"><i class="fas fa-calendar me-2"></i>My Appointments</a></li>
        <li><a class="dropdown-item" href="<?php echo e(route('student.medical.history')); ?>"><i class="fas fa-file-medical me-2"></i>Medical History</a></li>
        <li><a class="dropdown-item" href="<?php echo e(route('student.medical.prescriptions')); ?>"><i class="fas fa-prescription me-2"></i>Prescriptions</a></li>
        <li><a class="dropdown-item" href="<?php echo e(route('student.medical.lab-results')); ?>"><i class="fas fa-vial me-2"></i>Lab Results</a></li>
        <li><a class="dropdown-item" href="<?php echo e(route('student.medical.admissions')); ?>"><i class="fas fa-procedures me-2"></i>Admissions</a></li>
    </ul>
</li>
<?php elseif($role === 'lecturer'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('lecturer.dashboard')); ?>" class="nav-link <?php echo e(request()->is('lecturer/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('lecturer.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('lecturer/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('lecturer.courses')); ?>" class="nav-link <?php echo e(request()->is('lecturer/courses*') ? 'active' : ''); ?>">
        <i class="fas fa-book"></i> My Courses
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('lecturer.timetable')); ?>" class="nav-link <?php echo e(request()->is('lecturer/timetable*') ? 'active' : ''); ?>">
        <i class="fas fa-calendar-alt"></i> Timetable
    </a>
</li>
<?php elseif($role === 'hod'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('hod.dashboard')); ?>" class="nav-link <?php echo e(request()->is('hod/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('hod.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('hod/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('hod.courses')); ?>" class="nav-link <?php echo e(request()->is('hod/courses*') ? 'active' : ''); ?>">
        <i class="fas fa-book"></i> Courses
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('hod.timetable')); ?>" class="nav-link <?php echo e(request()->is('hod/timetable*') ? 'active' : ''); ?>">
        <i class="fas fa-calendar-alt"></i> Timetable
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('hod.results.index')); ?>" class="nav-link <?php echo e(request()->is('hod/results*') ? 'active' : ''); ?>">
        <i class="fas fa-check-circle"></i> Results
    </a>
</li>
<?php elseif($role === 'registrar'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.dashboard')); ?>" class="nav-link <?php echo e(request()->is('registrar/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('registrar/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.applications.index')); ?>" class="nav-link <?php echo e(request()->is('registrar/applications*') ? 'active' : ''); ?>">
        <i class="fas fa-file-alt"></i> Applications
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.applications.statistics')); ?>" class="nav-link <?php echo e(request()->is('registrar/applications/statistics*') ? 'active' : ''); ?>">
        <i class="fas fa-chart-bar"></i> Statistics
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.applications.admitted')); ?>" class="nav-link <?php echo e(request()->is('registrar/admitted*') ? 'active' : ''); ?>">
        <i class="fas fa-user-graduate"></i> Admitted Students
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.applicants')); ?>" class="nav-link <?php echo e(request()->is('registrar/applicants*') ? 'active' : ''); ?>">
        <i class="fas fa-user-graduate"></i> Old Applicants
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.students.index')); ?>" class="nav-link <?php echo e(request()->is('admin/students*') ? 'active' : ''); ?>">
        <i class="fas fa-users"></i> Students
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.admission')); ?>" class="nav-link <?php echo e(request()->is('registrar/admission*') ? 'active' : ''); ?>">
        <i class="fas fa-user-plus"></i> Admission List
    </a>
</li>
<?php elseif($role === 'admission_officer'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.dashboard')); ?>" class="nav-link <?php echo e(request()->is('registrar/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('registrar/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.applications.index')); ?>" class="nav-link <?php echo e(request()->is('registrar/applications*') ? 'active' : ''); ?>">
        <i class="fas fa-file-alt"></i> Applications
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.applications.statistics')); ?>" class="nav-link <?php echo e(request()->is('registrar/applications/statistics*') ? 'active' : ''); ?>">
        <i class="fas fa-chart-bar"></i> Statistics
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.applications.admitted')); ?>" class="nav-link <?php echo e(request()->is('registrar/admitted*') ? 'active' : ''); ?>">
        <i class="fas fa-user-graduate"></i> Admitted Students
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.applicants')); ?>" class="nav-link <?php echo e(request()->is('registrar/applicants*') ? 'active' : ''); ?>">
        <i class="fas fa-user-graduate"></i> Old Applicants
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.admission')); ?>" class="nav-link <?php echo e(request()->is('registrar/admission*') ? 'active' : ''); ?>">
        <i class="fas fa-user-plus"></i> Admission List
    </a>
</li>
<?php elseif($role === 'bursar'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('bursar.dashboard')); ?>" class="nav-link <?php echo e(request()->is('bursar/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('bursar.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('bursar/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(url('/bursar/payments')); ?>" class="nav-link <?php echo e(request()->is('bursar/payments*') ? 'active' : ''); ?>">
        <i class="fas fa-dollar-sign"></i> Payments
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(url('/bursar/payments/upload')); ?>" class="nav-link <?php echo e(request()->is('bursar/payments/upload*') ? 'active' : ''); ?>">
        <i class="fas fa-file-upload"></i> Upload External
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(url('/bursar/payments/sync')); ?>" class="nav-link <?php echo e(request()->is('bursar/payments/sync*') ? 'active' : ''); ?>">
        <i class="fas fa-sync-alt"></i> Payment Sync
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(url('/bursar/regimes')); ?>" class="nav-link <?php echo e(request()->is('bursar/regimes*') ? 'active' : ''); ?>">
        <i class="fas fa-calculator"></i> Regime Payments
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(url('/bursar/reports')); ?>" class="nav-link <?php echo e(request()->is('bursar/reports*') ? 'active' : ''); ?>">
        <i class="fas fa-chart-bar"></i> Reports
    </a>
</li>

<?php elseif($role === 'business_committee'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('business-committee.dashboard')); ?>" class="nav-link <?php echo e(request()->is('business-committee/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('business-committee.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('business-committee/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('business-committee.results')); ?>" class="nav-link <?php echo e(request()->is('business-committee/results*') ? 'active' : ''); ?>">
        <i class="fas fa-check-circle"></i> Approve Results
    </a>
</li>

<?php elseif($role === 'academic_board'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('academic-board.dashboard')); ?>" class="nav-link <?php echo e(request()->is('academic-board/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('academic-board.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('academic-board/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('academic-board.results')); ?>" class="nav-link <?php echo e(request()->is('academic-board/results*') ? 'active' : ''); ?>">
        <i class="fas fa-gavel"></i> Final Approval
    </a>
</li>

<?php if(\App\Services\Hospital\HospitalPermissions::isHospitalStaff()): ?>
<?php
    $hospitalMenu = \App\Services\Hospital\HospitalPermissions::menuFor();
    $currentPath = trim(request()->path(), '/');
?>
<?php $__currentLoopData = $hospitalMenu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        [$routeName, $icon, $label] = $item;
        $url = '#';
        try {
            $url = route($routeName);
        } catch (\Throwable $e) {
            // Route not registered for the current app context — render disabled link.
        }
        $itemPath = ltrim(str_replace(url('/'), '', $url), '/');
        $isActive = $url !== '#' && (
            str_starts_with($currentPath, $itemPath)
        );
    ?>
    <li class="nav-item">
        <a href="<?php echo e($url); ?>" class="nav-link <?php echo e($isActive ? 'active' : ''); ?>">
            <i class="<?php echo e($icon); ?>"></i> <?php echo e($label); ?>

        </a>
    </li>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>

<?php elseif(in_array($role, ['accountant', 'cashier', 'cmd', 'super_admin'])): ?>
<li class="nav-item">
    <a href="<?php echo e(route('finance.dashboard')); ?>" class="nav-link <?php echo e(request()->is('finance*') ? 'active' : ''); ?>">
        <i class="fas fa-chart-line"></i> Finance
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('finance.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('finance/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('finance.invoices.index')); ?>" class="nav-link <?php echo e(request()->is('finance/invoices*') ? 'active' : ''); ?>">
        <i class="fas fa-file-invoice"></i> Invoices
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('finance.receipts.index')); ?>" class="nav-link <?php echo e(request()->is('finance/receipts*') ? 'active' : ''); ?>">
        <i class="fas fa-receipt"></i> Receipts
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('finance.transactions.index')); ?>" class="nav-link <?php echo e(request()->is('finance/transactions*') ? 'active' : ''); ?>">
        <i class="fas fa-exchange-alt"></i> Transactions
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('finance.budgets.index')); ?>" class="nav-link <?php echo e(request()->is('finance/budgets*') ? 'active' : ''); ?>">
        <i class="fas fa-budget"></i> Budgets
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('finance.payroll.index')); ?>" class="nav-link <?php echo e(request()->is('finance/payroll*') ? 'active' : ''); ?>">
        <i class="fas fa-money-bill-wave"></i> Payroll
    </a>
</li>

<?php elseif(in_array($role, ['rector', 'super_admin'])): ?>
<li class="nav-item">
    <a href="<?php echo e(route('executive.dashboard')); ?>" class="nav-link <?php echo e(request()->is('executive*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Executive Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('executive.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('executive/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('executive.reports.students')); ?>" class="nav-link <?php echo e(request()->is('executive/reports*') ? 'active' : ''); ?>">
        <i class="fas fa-chart-bar"></i> Reports
    </a>
</li>

<?php elseif($role === 'librarian'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('librarian.dashboard')); ?>" class="nav-link <?php echo e(request()->is('librarian/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('librarian.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('librarian/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('librarian.books')); ?>" class="nav-link <?php echo e(request()->is('librarian/books*') ? 'active' : ''); ?>">
        <i class="fas fa-book"></i> Books
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('librarian.loans')); ?>" class="nav-link <?php echo e(request()->is('librarian/loans*') ? 'active' : ''); ?>">
        <i class="fas fa-exchange-alt"></i> Loans
    </a>
</li>

<?php elseif($role === 'auditor'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('auditor.dashboard')); ?>" class="nav-link <?php echo e(request()->is('auditor/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('auditor.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('auditor/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('auditor.audit-logs')); ?>" class="nav-link <?php echo e(request()->is('auditor/audit-logs*') ? 'active' : ''); ?>">
        <i class="fas fa-history"></i> Audit Logs
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('auditor.deleted')); ?>" class="nav-link <?php echo e(request()->is('auditor/deleted*') ? 'active' : ''); ?>">
        <i class="fas fa-trash-restore"></i> Deleted Records
    </a>
</li>

<?php elseif($role === 'dean'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('dean.dashboard')); ?>" class="nav-link <?php echo e(request()->is('dean/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('dean.results')); ?>" class="nav-link <?php echo e(request()->is('dean/results*') ? 'active' : ''); ?>">
        <i class="fas fa-clipboard-check"></i> Results Approval
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('dean.departments')); ?>" class="nav-link <?php echo e(request()->is('dean/departments*') ? 'active' : ''); ?>">
        <i class="fas fa-building-columns"></i> Departments
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('dean.students')); ?>" class="nav-link <?php echo e(request()->is('dean/students*') ? 'active' : ''); ?>">
        <i class="fas fa-user-graduate"></i> Students
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('dean.dashboard-config.edit', auth()->id())); ?>"
       class="nav-link <?php echo e(request()->is('dean/dashboard-config*') ? 'active' : ''); ?>">
        <i class="fas fa-sliders-h"></i> Customize Dashboard
    </a>
</li>
<?php elseif($role === 'applicant'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('applicant.dashboard')); ?>" class="nav-link <?php echo e(request()->is('applicant/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('applicant.apply')); ?>" class="nav-link <?php echo e(request()->is('applicant/apply*') ? 'active' : ''); ?>">
        <i class="fas fa-edit"></i> Apply
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('applicant.application')); ?>" class="nav-link <?php echo e(request()->is('applicant/application*') ? 'active' : ''); ?>">
        <i class="fas fa-file-alt"></i> My Application
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('applicant.payment')); ?>" class="nav-link <?php echo e(request()->is('applicant/payment*') ? 'active' : ''); ?>">
        <i class="fas fa-credit-card"></i> Make Payment
    </a>
</li>
<?php endif; ?>

<li class="nav-item">
    <a href="<?php echo e(route('notifications.index')); ?>" class="nav-link <?php echo e(request()->is('notifications*') ? 'active' : ''); ?>">
        <i class="fas fa-bell"></i> Notifications
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('profile.show')); ?>" class="nav-link <?php echo e(request()->is('profile*') ? 'active' : ''); ?>">
        <i class="fas fa-user"></i> Profile
    </a>
</li>
<?php endif; ?>