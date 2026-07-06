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
    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}">
        <i class="fas fa-users"></i> Users
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.staff.index') }}" class="nav-link {{ request()->is('admin/staff*') ? 'active' : '' }}">
        <i class="fas fa-user-tie"></i> Staff
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('admin.schools.index') }}" class="nav-link {{ request()->is('admin/schools*') ? 'active' : '' }}">
        <i class="fas fa-school"></i> Institution Setup
    </a>
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
    <a href="{{ route('admin.fees.index') }}" class="nav-link {{ request()->is('admin/fees*') ? 'active' : '' }}">
        <i class="fas fa-dollar-sign"></i> Fees
    </a>
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
    <a href="{{ route('admin.hostels.index') }}" class="nav-link {{ request()->is('admin/hostels*') ? 'active' : '' }}">
        <i class="fas fa-bed"></i> Hostel
    </a>
</li>
@elseif($role === 'student')
<li class="nav-item">
    <a href="{{ route('student.dashboard') }}" class="nav-link {{ request()->is('student/dashboard*') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('student.profile.edit') }}" class="nav-link {{ request()->is('student/profile*') ? 'active' : '' }}">
        <i class="fas fa-user-cog"></i> My Profile
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('student.courses') }}" class="nav-link {{ request()->is('student/courses*') ? 'active' : '' }}">
        <i class="fas fa-book"></i> My Courses
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('student.results') }}" class="nav-link {{ request()->is('student/results*') ? 'active' : '' }}">
        <i class="fas fa-chart-line"></i> Results
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('student.payments') }}" class="nav-link {{ request()->is('student/payments*') ? 'active' : '' }}">
        <i class="fas fa-dollar-sign"></i> Payments
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('student.timetable') }}" class="nav-link {{ request()->is('student/timetable*') ? 'active' : '' }}">
        <i class="fas fa-calendar-alt"></i> Timetable
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('student.hostel.my') }}" class="nav-link {{ request()->is('student/hostel*') ? 'active' : '' }}">
        <i class="fas fa-bed"></i> Hostel
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('student.complaints') }}" class="nav-link {{ request()->is('student/complaints*') ? 'active' : '' }}">
        <i class="fas fa-exclamation-circle"></i> Complaints
    </a>
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
    <a href="{{ route('bursar.regimes.index') }}" class="nav-link {{ request()->is('bursar/regimes*') ? 'active' : '' }}">
        <i class="fas fa-calculator"></i> Regime Payments
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('bursar.reports') }}" class="nav-link {{ request()->is('bursar/reports*') ? 'active' : '' }}">
        <i class="fas fa-chart-bar"></i> Reports
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
    <a href="{{ route('profile.show') }}" class="nav-link {{ request()->is('profile*') ? 'active' : '' }}">
        <i class="fas fa-user"></i> Profile
    </a>
</li>
@endauth