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
use App\Http\Controllers\Admin\CourseAssignmentController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\CourseRegistrationController as AdminCourseRegController;
use App\Http\Controllers\Admin\StudentIdCardController;
use App\Http\Controllers\Admin\ExamTimetableController;
use App\Http\Controllers\Admin\TranscriptController;
use App\Http\Controllers\Admin\LibraryController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\HostelController as AdminHostelController;
use App\Http\Controllers\Student\HostelController as StudentHostelController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\CourseRegistrationController;
use App\Http\Controllers\Student\ResultController;
use App\Http\Controllers\Student\PaymentController;
use App\Http\Controllers\Student\TimetableController;
use App\Http\Controllers\Lecturer\DashboardController as LecturerDashboardController;
use App\Http\Controllers\Lecturer\ResultController as LecturerResultController;
use App\Http\Controllers\Lecturer\AttendanceController;
use App\Http\Controllers\Applicant\ApplicationController;
use App\Http\Controllers\Bursar\RegimeController;

// Public Routes
Route::get('/', function () {
    return redirect('/login');
})->name('home');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Password Reset Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPasswordForm'])->name('password.forgot');
    Route::post('/forgot-password/verify-email', [ForgotPasswordController::class, 'verifyEmail'])->name('password.verify-email');
    Route::get('/forgot-password/secret-question', [ForgotPasswordController::class, 'showSecretQuestionForm'])->name('password.secret-question');
    Route::post('/forgot-password/verify-secret', [ForgotPasswordController::class, 'verifySecretAnswer'])->name('password.verify-secret');
    Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset-form');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.reset');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Applicant Routes
Route::prefix('applicant')->name('applicant.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/register', [RegisterController::class, 'showApplicantForm'])->name('register');
        Route::post('/register', [RegisterController::class, 'registerApplicant']);
    });
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [ApplicationController::class, 'dashboard'])->name('dashboard');
        Route::get('/apply', [ApplicationController::class, 'showApplicationForm'])->name('apply');
        Route::post('/apply', [ApplicationController::class, 'submitApplication']);
        Route::get('/application', [ApplicationController::class, 'viewApplication'])->name('application');
    });
});

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin,admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::resource('users', UserController::class);
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
    Route::post('/students/upload', [StudentController::class, 'upload'])->name('students.upload');
    Route::get('/students/download-template', [StudentController::class, 'downloadTemplate'])->name('students.downloadTemplate');

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

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
});

// Student Routes
Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/courses', [CourseRegistrationController::class, 'index'])->name('courses');
    Route::get('/courses/register', [CourseRegistrationController::class, 'register'])->name('courses.register');
    Route::post('/courses/register', [CourseRegistrationController::class, 'storeRegistration']);
    Route::delete('/courses/{studentCourse}/drop', [CourseRegistrationController::class, 'dropCourse'])->name('courses.drop');
    Route::get('/courses/print', [CourseRegistrationController::class, 'printForm'])->name('courses.print');

    Route::get('/results', [ResultController::class, 'index'])->name('results');
    Route::get('/results/{semester}', [ResultController::class, 'show'])->name('results.show');
    Route::get('/results/print', [ResultController::class, 'printResult'])->name('results.print');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments');
    Route::get('/payments/{fee}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
    Route::post('/payments/{fee}/initiate', [PaymentController::class, 'initiatePayment']);
    Route::get('/payments/verify', [PaymentController::class, 'verifyPayment'])->name('payments.verify');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'printReceipt'])->name('payments.receipt');

    Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable');

    // Complaints
    Route::get('/complaints', [\App\Http\Controllers\Student\ComplaintController::class, 'index'])->name('complaints');
    Route::post('/complaints', [\App\Http\Controllers\Student\ComplaintController::class, 'store'])->name('complaints.store');

    // Profile
    Route::get('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'update'])->name('profile.update');

    // Hostel (NEW)
    Route::get('/hostel', [StudentHostelController::class, 'myHostel'])->name('hostel.my');
    Route::get('/hostel/apply', [StudentHostelController::class, 'availableHostels'])->name('hostel.apply');
    Route::post('/hostel/apply', [StudentHostelController::class, 'apply']);
    Route::post('/hostel/request-change', [StudentHostelController::class, 'requestChange'])->name('hostel.request-change');
});

// Lecturer Routes
Route::prefix('lecturer')->name('lecturer.')->middleware(['auth', 'role:lecturer'])->group(function () {
    Route::get('/dashboard', [LecturerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/courses', [LecturerDashboardController::class, 'courses'])->name('courses');
    Route::get('/courses/{course}/students', [LecturerDashboardController::class, 'courseStudents'])->name('courses.students');
    Route::get('/courses/{course}/results', [LecturerResultController::class, 'enter'])->name('courses.results');
    Route::post('/courses/{course}/results', [LecturerResultController::class, 'store']);
    Route::post('/courses/{course}/results/bulk', [LecturerResultController::class, 'bulkUpload']);

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

        // Run migrations
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);

        // Create sessions table
        \Illuminate\Support\Facades\Artisan::call('session:table');
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

        // Run seeder
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

        // Clear cache again
        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        return response()->json([
            'success' => true,
            'message' => 'Setup completed! Database seeded.',
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