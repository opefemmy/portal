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
<li class="nav-item">
    <a href="<?php echo e(route('admin.users.index')); ?>" class="nav-link <?php echo e(request()->is('admin/users*') ? 'active' : ''); ?>">
        <i class="fas fa-users"></i> Users
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.staff.index')); ?>" class="nav-link <?php echo e(request()->is('admin/staff*') ? 'active' : ''); ?>">
        <i class="fas fa-user-tie"></i> Staff
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.schools.index')); ?>" class="nav-link <?php echo e(request()->is('admin/schools*') ? 'active' : ''); ?>">
        <i class="fas fa-school"></i> Institution Setup
    </a>
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
    <a href="<?php echo e(route('admin.fees.index')); ?>" class="nav-link <?php echo e(request()->is('admin/fees*') ? 'active' : ''); ?>">
        <i class="fas fa-dollar-sign"></i> Fees
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.grades.index')); ?>" class="nav-link <?php echo e(request()->is('admin/grades*') ? 'active' : ''); ?>">
        <i class="fas fa-star"></i> Grades
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
    <a href="<?php echo e(route('admin.library.books')); ?>" class="nav-link <?php echo e(request()->is('admin/library*') ? 'active' : ''); ?>">
        <i class="fas fa-book"></i> Library
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('admin.hostels.index')); ?>" class="nav-link <?php echo e(request()->is('admin/hostels*') ? 'active' : ''); ?>">
        <i class="fas fa-bed"></i> Hostel
    </a>
</li>
<?php elseif($role === 'student'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('student.dashboard')); ?>" class="nav-link <?php echo e(request()->is('student/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.profile.edit')); ?>" class="nav-link <?php echo e(request()->is('student/profile*') ? 'active' : ''); ?>">
        <i class="fas fa-user-cog"></i> My Profile
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.courses')); ?>" class="nav-link <?php echo e(request()->is('student/courses*') ? 'active' : ''); ?>">
        <i class="fas fa-book"></i> My Courses
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.results')); ?>" class="nav-link <?php echo e(request()->is('student/results*') ? 'active' : ''); ?>">
        <i class="fas fa-chart-line"></i> Results
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.payments')); ?>" class="nav-link <?php echo e(request()->is('student/payments*') ? 'active' : ''); ?>">
        <i class="fas fa-dollar-sign"></i> Payments
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.timetable')); ?>" class="nav-link <?php echo e(request()->is('student/timetable*') ? 'active' : ''); ?>">
        <i class="fas fa-calendar-alt"></i> Timetable
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.hostel.my')); ?>" class="nav-link <?php echo e(request()->is('student/hostel*') ? 'active' : ''); ?>">
        <i class="fas fa-bed"></i> Hostel
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('student.complaints')); ?>" class="nav-link <?php echo e(request()->is('student/complaints*') ? 'active' : ''); ?>">
        <i class="fas fa-exclamation-circle"></i> Complaints
    </a>
</li>
<?php elseif($role === 'lecturer'): ?>
<li class="nav-item">
    <a href="<?php echo e(route('lecturer.dashboard')); ?>" class="nav-link <?php echo e(request()->is('lecturer/dashboard*') ? 'active' : ''); ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
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
    <a href="<?php echo e(route('hod.results')); ?>" class="nav-link <?php echo e(request()->is('hod/results*') ? 'active' : ''); ?>">
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
    <a href="<?php echo e(route('admin.students.index')); ?>" class="nav-link <?php echo e(request()->is('admin/students*') ? 'active' : ''); ?>">
        <i class="fas fa-users"></i> Students
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('registrar.applicants')); ?>" class="nav-link <?php echo e(request()->is('registrar/applicants*') ? 'active' : ''); ?>">
        <i class="fas fa-user-graduate"></i> Applicants
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
    <a href="<?php echo e(route('bursar.payments')); ?>" class="nav-link <?php echo e(request()->is('bursar/payments*') ? 'active' : ''); ?>">
        <i class="fas fa-dollar-sign"></i> Payments
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('bursar.regimes.index')); ?>" class="nav-link <?php echo e(request()->is('bursar/regimes*') ? 'active' : ''); ?>">
        <i class="fas fa-calculator"></i> Regime Payments
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo e(route('bursar.reports')); ?>" class="nav-link <?php echo e(request()->is('bursar/reports*') ? 'active' : ''); ?>">
        <i class="fas fa-chart-bar"></i> Reports
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
<?php endif; ?>

<li class="nav-item">
    <a href="<?php echo e(route('profile.show')); ?>" class="nav-link <?php echo e(request()->is('profile*') ? 'active' : ''); ?>">
        <i class="fas fa-user"></i> Profile
    </a>
</li>
<?php endif; ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/layouts/sidebar.blade.php ENDPATH**/ ?>