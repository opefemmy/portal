<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ProgrammeController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\FeeController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Admin\CourseAssignmentController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\CourseRegistrationController as AdminCourseRegController;
use App\Http\Controllers\Admin\StudentIdCardController;
use App\Http\Controllers\Admin\ExamTimetableController;
use App\Http\Controllers\Admin\TranscriptController;
use App\Http\Controllers\Admin\LibraryController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\GradingController;
use App\Http\Controllers\Admin\HostelController as AdminHostelController;
use App\Http\Controllers\Student\HostelController as StudentHostelController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\CourseRegistrationController;
use App\Http\Controllers\Student\ResultController;
use App\Http\Controllers\Student\PaymentController;
use App\Http\Controllers\Student\TimetableController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\Student\LibraryController as StudentLibraryController;
use App\Http\Controllers\Student\PasswordChangeController;
use App\Http\Controllers\Student\SecurityController;
use App\Http\Controllers\Lecturer\DashboardController as LecturerDashboardController;
use App\Http\Controllers\Lecturer\ResultController as LecturerResultController;
use App\Http\Controllers\Lecturer\AttendanceController;
use App\Http\Controllers\Applicant\ApplicationController;
use App\Http\Controllers\Bursar\RegimeController;
use App\Http\Controllers\Admin\SystemSettingController;

// Public Routes
Route::get('/', function () {
    return redirect('/login');
})->name('home');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    // Student self-registration is disabled - students must use credentials provided by the school
    // Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    // Route::post('/register', [RegisterController::class, 'register']);

    // Password Reset Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPasswordForm'])->name('password.forgot');
    Route::post('/forgot-password/verify-email', [ForgotPasswordController::class, 'verifyEmail'])->name('password.verify-email');
    Route::get('/forgot-password/secret-question', [ForgotPasswordController::class, 'showSecretQuestionForm'])->name('password.secret-question');
    Route::post('/forgot-password/verify-secret', [ForgotPasswordController::class, 'verifySecretAnswer'])->name('password.verify-secret');
    Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset-form');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.reset');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Public Application Form
Route::get('/apply', [ApplicationController::class, 'showApplicationForm'])->name('public.apply');
Route::post('/apply', [ApplicationController::class, 'submitApplication'])->name('public.apply.submit');
Route::get('/apply/check-status', [ApplicationController::class, 'checkStatus'])->name('public.apply.status');

// API Routes for cascading dropdowns
Route::get('/api/departments/{schoolId}', function ($schoolId) {
    $departments = \App\Models\Department::where('school_id', $schoolId)->get();
    return response()->json($departments);
});

Route::get('/api/programmes/{departmentId}', function ($departmentId) {
    $programmes = \App\Models\Programme::where('department_id', $departmentId)->get();
    return response()->json($programmes);
});

// Applicant Routes
Route::prefix('applicant')->name('applicant.')->group(function () {
    // Get departments by school
    Route::get('/departments/{schoolId}', [ApplicationController::class, 'getDepartments']);

    Route::middleware('guest')->group(function () {
        Route::get('/register', [RegisterController::class, 'showApplicantForm'])->name('register');
        Route::post('/register', [RegisterController::class, 'registerApplicant']);
    });
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [ApplicationController::class, 'dashboard'])->name('dashboard');
        Route::get('/apply', [ApplicationController::class, 'showApplicationForm'])->name('apply');
        Route::post('/apply', [ApplicationController::class, 'submitApplication']);
        Route::post('/apply/fee', [ApplicationController::class, 'initiateApplicationFee'])->name('apply.fee');
        Route::get('/apply/payment/verify', [ApplicationController::class, 'verifyApplicationFee'])->name('apply.payment.verify');
        Route::get('/application', [ApplicationController::class, 'viewApplication'])->name('application');
        Route::get('/application/print', [ApplicationController::class, 'printApplication'])->name('application.print');
    });
});

// Admin Routes - redirect /admin to /admin/dashboard
Route::redirect('/admin', '/admin/dashboard');

// Admin Dashboard (requires auth and admin role)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin,admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::resource('users', UserController::class);
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset_password');
    Route::get('/users/upload', [UserController::class, 'upload'])->name('users.upload');
    Route::post('/users/upload', [UserController::class, 'processUpload'])->name('users.upload.process');
    Route::post('/users/{user}/passport', [UserController::class, 'uploadPassport'])->name('users.passport');

    // Institution Setup
    Route::resource('schools', SchoolController::class);
    Route::resource('departments', DepartmentController::class);
    Route::resource('programmes', ProgrammeController::class);
    Route::resource('sessions', SessionController::class);
    Route::post('/sessions/{session}/set-current', [SessionController::class, 'setCurrent'])->name('sessions.set_current');

    // Course Management
    Route::resource('courses', CourseController::class);

    // Fee Management
    Route::resource('fees', FeeController::class);

    // Grade Configuration
    Route::resource('grades', GradeController::class);

    // Grade Classifications
    Route::put('/grades/classification/{classification}', [GradingController::class, 'updateClassification'])->name('grades.classification.update');
    Route::post('/grades/classification', [GradingController::class, 'storeClassification'])->name('grades.classification.store');
    Route::delete('/grades/classification/{classification}', [GradingController::class, 'destroyClassification'])->name('grades.classification.destroy');

    // Grading Scales
    Route::put('/grades/scale/{scale}', [GradingController::class, 'updateScale'])->name('grades.scale.update');
    Route::post('/grades/scale', [GradingController::class, 'storeScale'])->name('grades.scale.store');
    Route::delete('/grades/scale/{scale}', [GradingController::class, 'destroyScale'])->name('grades.scale.destroy');

    // System Settings
    Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SystemSettingController::class, 'updateSettings'])->name('settings.update');
    Route::post('/settings/gateway', [SystemSettingController::class, 'updateGateways'])->name('settings.gateway');
    Route::post('/settings/toggle', [SystemSettingController::class, 'toggleSetting'])->name('settings.toggle');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/students', [ReportController::class, 'students'])->name('reports.students');
    Route::get('/reports/results', [ReportController::class, 'results'])->name('reports.results');
    Route::get('/reports/payments', [ReportController::class, 'payments'])->name('reports.payments');

    // Staff Management
    Route::resource('staff', StaffController::class);
    Route::post('/staff/{user}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset_password');

    // Student Management
    Route::resource('students', StudentController::class);
    Route::post('/students/{student}/reset-password', [StudentController::class, 'resetPassword'])->name('students.reset_password');
    Route::get('/students/lgas/{stateId}', [StudentController::class, 'getLGAs']);

    // Student Import (NEW)
    Route::get('/students/import', [StudentImportController::class, 'index'])->name('students.import');
    Route::post('/students/import', [StudentImportController::class, 'import'])->name('students.import.store');
    Route::get('/students/import/template', [StudentImportController::class, 'downloadTemplate'])->name('students.import.template');

    // Course Assignments (OnCourses)
    Route::resource('course-assignments', CourseAssignmentController::class);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::put('/notifications', [NotificationController::class, 'update'])->name('notifications.update');

    // Course Registration Reports
    Route::get('/course-registrations', [AdminCourseRegController::class, 'index'])->name('course-registrations.index');
    Route::get('/course-registrations/export', [AdminCourseRegController::class, 'export'])->name('course-registrations.export');
    Route::post('/course-registrations/{registration}/unsubmit', [AdminCourseRegController::class, 'unsubmit'])->name('course-registrations.unsubmit');
    Route::post('/course-registrations/{registration}/resubmit', [AdminCourseRegController::class, 'resubmit'])->name('course-registrations.resubmit');

    // Student ID Cards
    Route::get('/id-cards', [StudentIdCardController::class, 'index'])->name('id-cards.index');
    Route::get('/id-cards/{student}/generate', [StudentIdCardController::class, 'generate'])->name('id-cards.generate');
    Route::get('/id-cards/print', [StudentIdCardController::class, 'print'])->name('id-cards.print');
    Route::post('/id-cards/bulk', [StudentIdCardController::class, 'bulk'])->name('id-cards.bulk');

    // Lecture Timetable
    Route::resource('timetable', \App\Http\Controllers\Admin\TimetableController::class);

    // Transcripts
    Route::get('/transcripts', [TranscriptController::class, 'index'])->name('transcripts.index');
    Route::get('/transcripts/{student}', [TranscriptController::class, 'show'])->name('transcripts.show');
    Route::get('/transcripts/{student}/print', [TranscriptController::class, 'print'])->name('transcripts.print');

    // Hostel Management
    Route::resource('hostels', AdminHostelController::class);
    Route::get('/hostels/{hostel}/rooms/create', [AdminHostelController::class, 'createRoom'])->name('hostels.rooms.create');
    Route::post('/hostels/{hostel}/rooms', [AdminHostelController::class, 'storeRoom'])->name('hostels.rooms.store');
    Route::get('/hostels/allocations', [AdminHostelController::class, 'allocations'])->name('hostels.allocations');
    Route::get('/hostels/allocations/create', [AdminHostelController::class, 'createAllocation'])->name('hostels.allocations.create');
    Route::post('/hostels/allocations', [AdminHostelController::class, 'storeAllocation'])->name('hostels.allocations.store');
    Route::post('/hostels/allocations/{allocation}/checkout', [AdminHostelController::class, 'checkOut'])->name('hostels.allocations.checkout');
    Route::get('/hostels/rooms/{hostel}/rooms', [AdminHostelController::class, 'getRooms']);
    Route::get('/hostels/beds/{room}/beds', [AdminHostelController::class, 'getAvailableBeds']);

// Library
    Route::get('/library/verify', function () {
        return view('admin.library.verify');
    })->name('library.verify');

    Route::post('/library/verify', function (\Illuminate\Http\Request $request) {
        $code = \App\Models\Setting::get('library_access_code');
        if ($code && $request->code !== $code) {
            return back()->with('error', 'Invalid access code');
        }
        session()->put('library_verified', true);
        return redirect()->route('admin.library.books');
    })->name('library.verify.post');

    Route::middleware('library.access')->group(function () {
        Route::get('/library/books', [LibraryController::class, 'books'])->name('library.books');
        Route::get('/library/books/create', [LibraryController::class, 'createBook'])->name('library.books.create');
        Route::post('/library/books', [LibraryController::class, 'storeBook'])->name('library.books.store');
        Route::post('/library/books/upload', [LibraryController::class, 'uploadBooks'])->name('library.books.upload');
        Route::get('/library/loans', [LibraryController::class, 'loans'])->name('library.loans');
        Route::post('/library/loans/issue', [LibraryController::class, 'issueBook'])->name('library.loans.issue');
        Route::post('/library/loans/{loan}/return', [LibraryController::class, 'returnBook'])->name('library.loans.return');
    });

    // Results Management
    Route::get('/results', [\App\Http\Controllers\Admin\ResultController::class, 'index'])->name('results.index');
    Route::get('/results/upload', [\App\Http\Controllers\Admin\ResultController::class, 'upload'])->name('results.upload');
    Route::post('/results/upload', [\App\Http\Controllers\Admin\ResultController::class, 'store'])->name('results.store');
    Route::get('/results/template', [\App\Http\Controllers\Admin\ResultController::class, 'downloadTemplate'])->name('results.template');
    Route::get('/results/{result}', [\App\Http\Controllers\Admin\ResultController::class, 'show'])->name('results.show');
    Route::put('/results/{result}/approve', [\App\Http\Controllers\Admin\ResultController::class, 'approve'])->name('results.approve');
    Route::put('/results/{result}/reject', [\App\Http\Controllers\Admin\ResultController::class, 'reject'])->name('results.reject');
    Route::put('/results/{result}/compute', [\App\Http\Controllers\Admin\ResultController::class, 'compute'])->name('results.compute');
    Route::post('/results/release', [\App\Http\Controllers\Admin\ResultController::class, 'release'])->name('results.release');
    Route::post('/results/hide', [\App\Http\Controllers\Admin\ResultController::class, 'hide'])->name('results.hide');
    Route::post('/results/lock', [\App\Http\Controllers\Admin\ResultController::class, 'lock'])->name('results.lock');
    Route::post('/results/publish', [\App\Http\Controllers\Admin\ResultController::class, 'publish'])->name('results.publish');
    Route::post('/results/withdraw', [\App\Http\Controllers\Admin\ResultController::class, 'withdraw'])->name('results.withdraw');
    Route::post('/results/recompute', [\App\Http\Controllers\Admin\ResultController::class, 'recompute'])->name('results.recompute');
    Route::post('/results/bulk-approve', [\App\Http\Controllers\Admin\ResultController::class, 'bulkApprove'])->name('results.bulkApprove');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // System Maintenance Routes
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\MaintenanceController::class, 'dashboard'])->name('dashboard');
        Route::get('/health', [\App\Http\Controllers\Admin\MaintenanceController::class, 'healthCheck'])->name('health');
        Route::post('/health/run', [\App\Http\Controllers\Admin\MaintenanceController::class, 'runHealthCheck'])->name('health.run');
        Route::post('/health/repair', [\App\Http\Controllers\Admin\MaintenanceController::class, 'runHealthCheck'])->name('health.repair');
        Route::get('/updates', [\App\Http\Controllers\Admin\MaintenanceController::class, 'updateManager'])->name('updates');
        Route::post('/migrations/run', [\App\Http\Controllers\Admin\MaintenanceController::class, 'runMigrations'])->name('migrations.run');
        Route::post('/seeders/run', [\App\Http\Controllers\Admin\MaintenanceController::class, 'runSeeders'])->name('seeders.run');
        Route::post('/repairs/run', [\App\Http\Controllers\Admin\MaintenanceController::class, 'runRepairs'])->name('repairs.run');
        Route::get('/migrations', [\App\Http\Controllers\Admin\MaintenanceController::class, 'migrations'])->name('migrations');
        Route::get('/database', [\App\Http\Controllers\Admin\MaintenanceController::class, 'databaseRepair'])->name('database');
        Route::get('/modules', [\App\Http\Controllers\Admin\MaintenanceController::class, 'moduleScanner'])->name('modules');
        Route::get('/permissions', [\App\Http\Controllers\Admin\MaintenanceController::class, 'permissionScanner'])->name('permissions');
        Route::get('/storage', [\App\Http\Controllers\Admin\MaintenanceController::class, 'storageScanner'])->name('storage');
        Route::get('/cache', [\App\Http\Controllers\Admin\MaintenanceController::class, 'cacheManager'])->name('cache');
        Route::post('/cache/clear', [\App\Http\Controllers\Admin\MaintenanceController::class, 'clearCaches'])->name('cache.clear');
        Route::post('/optimize', [\App\Http\Controllers\Admin\MaintenanceController::class, 'optimizeSystem'])->name('optimize');
        Route::get('/backups', [\App\Http\Controllers\Admin\MaintenanceController::class, 'backupManager'])->name('backups');
        Route::post('/backup/create', [\App\Http\Controllers\Admin\MaintenanceController::class, 'createBackup'])->name('backup.create');
        Route::get('/logs', [\App\Http\Controllers\Admin\MaintenanceController::class, 'logViewer'])->name('logs');
        Route::get('/versions', [\App\Http\Controllers\Admin\MaintenanceController::class, 'versionManager'])->name('versions');
        Route::post('/version/register', [\App\Http\Controllers\Admin\MaintenanceController::class, 'registerVersion'])->name('version.register');
        Route::get('/report', [\App\Http\Controllers\Admin\MaintenanceController::class, 'systemReport'])->name('report');
    });
});

// Student Routes
Route::prefix('student')->name('student.')->middleware(['auth', 'role:student', 'student.onboarding'])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/courses', [CourseRegistrationController::class, 'index'])->name('courses');
    Route::get('/courses/register', [CourseRegistrationController::class, 'register'])->name('courses.register');
    Route::post('/courses/register', [CourseRegistrationController::class, 'storeRegistration']);
    Route::delete('/courses/{studentCourse}/drop', [CourseRegistrationController::class, 'dropCourse'])->name('courses.drop');
    Route::get('/courses/print', [CourseRegistrationController::class, 'printForm'])->name('courses.print');

    Route::get('/results', [ResultController::class, 'index'])->name('results');
    Route::get('/results/{semester}', [ResultController::class, 'show'])->name('results.show');
    Route::get('/results/print', [ResultController::class, 'printResult'])->name('results.print');
    Route::get('/results/transcript', [ResultController::class, 'transcript'])->name('results.transcript');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments');
    Route::get('/payments/{fee}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
    Route::post('/payments/{fee}/initiate', [PaymentController::class, 'initiatePayment']);
    Route::get('/payments/verify', [PaymentController::class, 'verifyPayment'])->name('payments.verify');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'printReceipt'])->name('payments.receipt');

    Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable');

    // Student Attendance
    Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance');
    Route::post('/attendance/mark', [StudentAttendanceController::class, 'markAttendance'])->name('attendance.mark');
    Route::get('/my-attendance', [StudentAttendanceController::class, 'myAttendance'])->name('my-attendance');

    // Complaints
    Route::get('/complaints', [\App\Http\Controllers\Student\ComplaintController::class, 'index'])->name('complaints');
    Route::post('/complaints', [\App\Http\Controllers\Student\ComplaintController::class, 'store'])->name('complaints.store');

    // Profile
    Route::get('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/passport', [\App\Http\Controllers\Student\ProfileController::class, 'uploadPassport'])->name('profile.passport');

    // Hostel (NEW)
    Route::get('/hostel', [StudentHostelController::class, 'myHostel'])->name('hostel.my');
    Route::get('/hostel/apply', [StudentHostelController::class, 'availableHostels'])->name('hostel.apply');
    Route::post('/hostel/apply', [StudentHostelController::class, 'apply']);
    Route::post('/hostel/request-change', [StudentHostelController::class, 'requestChange'])->name('hostel.request-change');

    // Library
    Route::get('/library', [StudentLibraryController::class, 'index'])->name('library');
    Route::get('/library/search', [StudentLibraryController::class, 'search'])->name('library.search');
    Route::post('/library/pay-fee', [StudentLibraryController::class, 'payLibraryFee'])->name('library.pay-fee');
    Route::post('/library/borrow/{book}', [StudentLibraryController::class, 'borrowBook'])->name('library.borrow');

    // Password Change (Required for new students)
    Route::get('/password/change-required', [PasswordChangeController::class, 'showChangeForm'])->name('password.change.required');
    Route::post('/password/change', [PasswordChangeController::class, 'changePassword'])->name('password.change');

    // Security Question Setup
    Route::get('/security/setup', [SecurityController::class, 'showSetupForm'])->name('security.setup');
    Route::post('/security/setup', [SecurityController::class, 'setup'])->name('security.setup.store');

    // Student Medical Portal
    Route::get('/medical', [\App\Http\Controllers\Hospital\PatientPortalController::class, 'index'])->name('medical.index');
    Route::get('/medical/appointments', [\App\Http\Controllers\Hospital\PatientPortalController::class, 'myAppointments'])->name('medical.appointments');
    Route::get('/medical/book', function () {
        $doctors = \App\Models\Hospital\HospitalStaff::where('staff_type', 'doctor')->where('is_active', true)->get();
        return view('student.medical.book-appointment', compact('doctors'));
    })->name('medical.book');
    Route::post('/medical/appointment', [\App\Http\Controllers\Hospital\PatientPortalController::class, 'bookAppointment'])->name('medical.appointment.store');
    Route::get('/medical/history', [\App\Http\Controllers\Hospital\PatientPortalController::class, 'myMedicalHistory'])->name('medical.history');
    Route::get('/medical/prescriptions', [\App\Http\Controllers\Hospital\PatientPortalController::class, 'myPrescriptions'])->name('medical.prescriptions');
    Route::get('/medical/lab-results', [\App\Http\Controllers\Hospital\PatientPortalController::class, 'myLabResults'])->name('medical.lab-results');
    Route::get('/medical/admissions', [\App\Http\Controllers\Hospital\PatientPortalController::class, 'myAdmissions'])->name('medical.admissions');
});

// Lecturer Routes
Route::prefix('lecturer')->name('lecturer.')->middleware(['auth', 'role:lecturer'])->group(function () {
    Route::get('/dashboard', [LecturerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/courses', [LecturerDashboardController::class, 'courses'])->name('courses');
    Route::get('/courses/{course}/students', [LecturerDashboardController::class, 'courseStudents'])->name('courses.students');
    Route::get('/courses/{course}/results', [LecturerResultController::class, 'enter'])->name('courses.results');
    Route::post('/courses/{course}/results', [LecturerResultController::class, 'store'])->name('courses.results.store');
    Route::post('/courses/{course}/results/bulk', [LecturerResultController::class, 'bulkUpload'])->name('courses.bulk');
    Route::get('/courses/{course}/template', [LecturerResultController::class, 'downloadTemplate'])->name('courses.template');

    // Edit result before HOD approval
    Route::get('/result/{result}/edit', [LecturerResultController::class, 'edit'])->name('result.edit');
    Route::put('/result/{result}', [LecturerResultController::class, 'update'])->name('result.update');

    Route::get('/attendance/{course}', [AttendanceController::class, 'index'])->name('attendance');
    Route::post('/attendance/{course}', [AttendanceController::class, 'mark']);
    Route::get('/attendance/{course}/report', [AttendanceController::class, 'report'])->name('attendance.report');

    Route::get('/timetable', [LecturerDashboardController::class, 'timetable'])->name('timetable');
});

// HOD Routes
Route::prefix('hod')->name('hod.')->middleware(['auth', 'role:hod'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\HOD\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/courses', [\App\Http\Controllers\HOD\CourseController::class, 'index'])->name('courses');
    Route::get('/courses/assign', [\App\Http\Controllers\HOD\CourseController::class, 'assign'])->name('courses.assign');
    Route::post('/courses/assign', [\App\Http\Controllers\HOD\CourseController::class, 'storeAssignment']);
    Route::put('/courses/{assignment}/reassign', [\App\Http\Controllers\HOD\CourseController::class, 'reassign'])->name('courses.reassign');
    Route::delete('/courses/{assignment}/remove', [\App\Http\Controllers\HOD\CourseController::class, 'removeAssignment'])->name('courses.remove');

    Route::get('/timetable', [\App\Http\Controllers\HOD\TimetableController::class, 'index'])->name('timetable');
    Route::put('/timetable/{timetable}/approve', [\App\Http\Controllers\HOD\TimetableController::class, 'approve'])->name('timetable.approve');
    Route::put('/timetable/{timetable}/reject', [\App\Http\Controllers\HOD\TimetableController::class, 'reject'])->name('timetable.reject');

    Route::get('/results', [\App\Http\Controllers\HOD\ResultController::class, 'index'])->name('results');
    Route::put('/results/{result}/approve', [\App\Http\Controllers\HOD\ResultController::class, 'approve'])->name('results.approve');
    Route::put('/results/{result}/reject', [\App\Http\Controllers\HOD\ResultController::class, 'reject'])->name('results.reject');
});

// Dean Routes
Route::prefix('dean')->name('dean.')->middleware(['auth', 'role:dean'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Dean\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/departments', [\App\Http\Controllers\Dean\DepartmentController::class, 'index'])->name('departments');
    Route::get('/results', [\App\Http\Controllers\Dean\ResultController::class, 'index'])->name('results');
    Route::put('/results/{result}/approve', [\App\Http\Controllers\Dean\ResultController::class, 'approve'])->name('results.approve');
});

// Registrar Routes
Route::prefix('registrar')->name('registrar.')->middleware(['auth', 'role:registrar'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Registrar\DashboardController::class, 'index'])->name('dashboard');

    // Application Management
    Route::get('/applications', [\App\Http\Controllers\Registrar\ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{applicant}', [\App\Http\Controllers\Registrar\ApplicationController::class, 'show'])->name('applications.show');
    Route::put('/applications/{applicant}/status', [\App\Http\Controllers\Registrar\ApplicationController::class, 'updateStatus'])->name('applications.updateStatus');
    Route::post('/applications/bulk', [\App\Http\Controllers\Registrar\ApplicationController::class, 'bulkAction'])->name('applications.bulk');
    Route::get('/applications/export', [\App\Http\Controllers\Registrar\ApplicationController::class, 'export'])->name('applications.export');
    Route::get('/admitted-students', [\App\Http\Controllers\Registrar\ApplicationController::class, 'admittedStudents'])->name('applications.admitted');
    Route::get('/applications/statistics', [\App\Http\Controllers\Registrar\ApplicationController::class, 'statistics'])->name('applications.statistics');

    Route::get('/applicants', [\App\Http\Controllers\Registrar\ApplicantController::class, 'index'])->name('applicants');
    Route::get('/applicants/{applicant}', [\App\Http\Controllers\Registrar\ApplicantController::class, 'show'])->name('applicants.show');
    Route::put('/applicants/{applicant}/admit', [\App\Http\Controllers\Registrar\ApplicantController::class, 'admit'])->name('applicants.admit');
    Route::put('/applicants/{applicant}/reject', [\App\Http\Controllers\Registrar\ApplicantController::class, 'reject'])->name('applicants.reject');
    Route::get('/admission-list', [\App\Http\Controllers\Registrar\AdmissionController::class, 'index'])->name('admission');
    Route::put('/admission-list/{applicant}/status', [\App\Http\Controllers\Registrar\AdmissionController::class, 'updateStatus'])->name('admission.updateStatus');
    Route::post('/admission-list/upload', [\App\Http\Controllers\Registrar\AdmissionController::class, 'upload'])->name('admission.upload');
    Route::get('/admission-list/settings', [\App\Http\Controllers\Registrar\AdmissionController::class, 'settings'])->name('admission.settings');
    Route::put('/admission-list/settings', [\App\Http\Controllers\Registrar\AdmissionController::class, 'updateSettings'])->name('admission.updateSettings');
    Route::get('/admission-list/print', [\App\Http\Controllers\Registrar\AdmissionController::class, 'print'])->name('admission.print');
    Route::get('/admission-track', [\App\Http\Controllers\Registrar\AdmissionController::class, 'track'])->name('admission.track');

    // Admission Letters
    Route::get('/admission-letter/template', [\App\Http\Controllers\Registrar\AdmissionController::class, 'showLetterTemplate'])->name('admission.uploadTemplate');
    Route::post('/admission-letter/template', [\App\Http\Controllers\Registrar\AdmissionController::class, 'uploadLetterTemplate']);
    Route::get('/admission-letter/generate', [\App\Http\Controllers\Registrar\AdmissionController::class, 'generateLetters'])->name('admission.generateLetters');
    Route::get('/admission-letter/{applicant}', [\App\Http\Controllers\Registrar\AdmissionController::class, 'generateLetter'])->name('admission.generateLetter');

    // Admission List by Department
    Route::get('/admission-list/by-department', [\App\Http\Controllers\Registrar\AdmissionController::class, 'listByDepartment'])->name('admission.byDepartment');
    Route::get('/admission-list/upload', [\App\Http\Controllers\Registrar\AdmissionController::class, 'showUploadByDepartment'])->name('admission.uploadByDepartment');
    Route::post('/admission-list/upload-by-department', [\App\Http\Controllers\Registrar\AdmissionController::class, 'uploadAdmissionList']);
});

// Bursar Routes
Route::prefix('bursar')->name('bursar.')->middleware(['auth', 'role:bursar'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Bursar\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/payments', [\App\Http\Controllers\Bursar\PaymentController::class, 'index'])->name('payments');
    Route::get('/payments/{payment}/verify', [\App\Http\Controllers\Bursar\PaymentController::class, 'verify'])->name('payments.verify');
    Route::get('/reports', [\App\Http\Controllers\Bursar\ReportController::class, 'index'])->name('reports');

    // Regime Payments
    Route::resource('regimes', RegimeController::class);
});

// Business Committee Routes
Route::prefix('business-committee')->name('business-committee.')->middleware(['auth', 'role:business_committee'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\BusinessCommittee\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/results', [\App\Http\Controllers\BusinessCommittee\ResultController::class, 'index'])->name('results');
    Route::put('/results/{result}/approve', [\App\Http\Controllers\BusinessCommittee\ResultController::class, 'approve'])->name('results.approve');
    Route::put('/results/{result}/reject', [\App\Http\Controllers\BusinessCommittee\ResultController::class, 'reject'])->name('results.reject');
});

// Academic Board Routes
Route::prefix('academic-board')->name('academic-board.')->middleware(['auth', 'role:academic_board'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AcademicBoard\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/results', [\App\Http\Controllers\AcademicBoard\ResultController::class, 'index'])->name('results');
    Route::put('/results/{result}/approve', [\App\Http\Controllers\AcademicBoard\ResultController::class, 'approve'])->name('results.approve');
    Route::put('/results/{result}/reject', [\App\Http\Controllers\AcademicBoard\ResultController::class, 'reject'])->name('results.reject');
});

// Librarian Routes
Route::prefix('librarian')->name('librarian.')->middleware(['auth', 'role:librarian'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Librarian\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/books', [\App\Http\Controllers\Librarian\DashboardController::class, 'books'])->name('books');
    Route::get('/books/create', [\App\Http\Controllers\Librarian\DashboardController::class, 'createBook'])->name('books.create');
    Route::post('/books', [\App\Http\Controllers\Librarian\DashboardController::class, 'storeBook'])->name('books.store');
    Route::get('/loans', [\App\Http\Controllers\Librarian\DashboardController::class, 'loans'])->name('loans');
    Route::post('/loans/issue', [\App\Http\Controllers\Librarian\DashboardController::class, 'issueBook'])->name('loans.issue');
    Route::post('/loans/{loan}/return', [\App\Http\Controllers\Librarian\DashboardController::class, 'returnBook'])->name('loans.return');
});

// Profile Routes (Shared)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password.update');
    Route::put('/secret-question', [\App\Http\Controllers\ProfileController::class, 'updateSecretQuestion'])->name('profile.update-secret');
});

// Redirect based on role
Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard')->middleware('auth');

// Setup Route (For Render Deployment - Remove after use)
Route::get('/setup', function () {
    try {
        // Clear config cache
        \Illuminate\Support\Facades\Artisan::call('config:clear');

        // Run migrations (fresh with seed)
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);

        // Run seeder
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

        // Clear cache again
        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        return response()->json([
            'success' => true,
            'message' => 'Setup completed! Database seeded with all tables including hostels.',
            'admin_email' => 'admin@portal.edu',
            'admin_password' => 'password'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
    }
});

// TEST LOGIN Route - Use this to test login before deployment
Route::get('/test-login-creds', function () {
    // First check if DB config is loaded
    $dbDriver = config('database.default');
    $dbHost = config('database.connections.'.$dbDriver.'.host');

    return response()->json([
        'success' => true,
        'message' => 'App is working!',
        'php_version' => PHP_VERSION,
        'db_driver' => $dbDriver,
        'db_host' => $dbHost,
        'app_env' => config('app.env'),
        'app_debug' => config('app.debug'),
        'login_credentials' => [
            'admin' => 'admin@portal.edu / password',
            'student' => 'student@test.com / password123'
        ]
    ]);
});

// Test database connection
Route::get('/test-db', function () {
    try {
        \DB::connection()->getPdo();
        return response()->json([
            'success' => true,
            'message' => 'Database connected!',
            'driver' => \DB::connection()->getDriverName(),
            'database' => \DB::connection()->getDatabaseName()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

// Test route without CSRF - REMOVE AFTER TESTING
Route::post('/login-test', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
        $request->session()->regenerate();
        $user = \Illuminate\Support\Facades\Auth::user();
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user->email,
            'role' => $user->role ? $user->role->slug : 'none'
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Invalid credentials'
    ]);
})->middleware('web');

// ===========================================
// HOSPITAL MODULE ROUTES
// ===========================================
require __DIR__.'/hospital.php';

// ===========================================
// FINANCE MODULE ROUTES
// ===========================================
require __DIR__.'/finance.php';

// ===========================================
// NOTIFICATION ROUTES
// ===========================================
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
});

// ===========================================
// EXECUTIVE DASHBOARD ROUTES
// ===========================================
require __DIR__.'/executive.php';

// Simple test login page - REMOVE AFTER TESTING
Route::get('/test-login', function () {
    return '<html><head><title>Direct Login Test</title></head><body style="font-family:Arial;padding:20px;">
<h2>🔑 Direct Login Test</h2>
<form id="loginForm" style="max-width:300px;">
    <input type="email" name="email" value="admin@portal.edu" required style="width:100%;padding:8px;margin-bottom:10px;"><br>
    <input type="password" name="password" value="password" required style="width:100%;padding:8px;margin-bottom:10px;"><br>
    <button type="submit" style="padding:10px 20px;background:#28a745;color:white;border:none;cursor:pointer;">Login</button>
</form>
<h3>Result:</h3>
<pre id="result" style="background:#f4f4f4;padding:15px;border-radius:5px;"></pre>
<script>
document.getElementById("loginForm").onsubmit = async function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    let response = await fetch("/direct-login", {
        method: "POST",
        body: formData,
        headers: {
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        credentials: "include"
    });
    let result = await response.json();
    document.getElementById("result").innerHTML = JSON.stringify(result, null, 2);
    if(result.success) {
        document.getElementById("result").innerHTML += "\n\n✅ <a href=\"" + result.redirect + "\">Click here to go to dashboard</a>";
    }
};
</script>
</body></html>';
});

// NEW DIRECT LOGIN - bypasses all session issues
Route::post('/direct-login', function (\Illuminate\Http\Request $request) {
    try {
        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found']);
        }

        if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid password']);
        }

        // Generate simple token
        $token = base64_encode($user->id . ':' . time());

        return response()->json([
            'success' => true,
            'message' => 'Login successful!',
            'user' => $user->email,
            'name' => $user->name,
            'role' => $user->role ? $user->role->slug : 'none',
            'token' => $token,
            'redirect' => '/admin/dashboard'
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
});