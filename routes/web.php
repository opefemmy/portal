<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DashboardConfigController as AdminDashboardConfigController;
use App\Http\Controllers\Bursar\DashboardConfigController as BursarDashboardConfigController;
use App\Http\Controllers\Registrar\DashboardConfigController as RegistrarDashboardConfigController;
use App\Http\Controllers\Student\DashboardConfigController as StudentDashboardConfigController;
use App\Http\Controllers\Lecturer\DashboardConfigController as LecturerDashboardConfigController;
use App\Http\Controllers\HOD\DashboardConfigController as HodDashboardConfigController;
use App\Http\Controllers\Dean\DashboardConfigController as DeanDashboardConfigController;
use App\Http\Controllers\Librarian\DashboardConfigController as LibrarianDashboardConfigController;
use App\Http\Controllers\BusinessCommittee\DashboardConfigController as BusinessCommitteeDashboardConfigController;
use App\Http\Controllers\AcademicBoard\DashboardConfigController as AcademicBoardDashboardConfigController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ProgrammeController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\FeeController;
use App\Http\Controllers\Admin\PaymentTypeController;
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
use App\Http\Controllers\Applicant\PaymentGatewayController;
use App\Http\Controllers\Payment\TestPaymentController;
use App\Http\Controllers\Bursar\RegimeController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\UserUnlockController;

// Public Routes
Route::get('/', function () {
    return redirect('/login');
})->name('home');

// Public Payment Validation (Before Login)
Route::get('/validate-payment', [\App\Http\Controllers\Applicant\PaymentValidationController::class, 'showValidatePayment'])->name('public.validate-payment');
Route::post('/validate-payment', [\App\Http\Controllers\Applicant\PaymentValidationController::class, 'validatePayment'])->name('public.payment.validate');

// Online Payment System (Public - No Login Required)
Route::prefix('online-payment')->name('online-payment.')->group(function () {
    Route::post('/lookup', [\App\Http\Controllers\OnlinePaymentController::class, 'lookupStudent'])->name('lookup');
    Route::post('/process', [\App\Http\Controllers\OnlinePaymentController::class, 'processPayment'])->name('process');
    Route::post('/validate', [\App\Http\Controllers\OnlinePaymentController::class, 'validatePayment'])->name('validate');
    Route::get('/receipt/{payment}', [\App\Http\Controllers\OnlinePaymentController::class, 'printReceipt'])->name('receipt');

    // XpressPayments callback routes
    Route::get('/callback', [\App\Http\Controllers\OnlinePaymentController::class, 'xpressCallback'])->name('callback');
    Route::post('/verify', [\App\Http\Controllers\OnlinePaymentController::class, 'verifyXpressPayment'])->name('verify');
});

// General Payment Verification API
Route::prefix('api/payment')->name('api.payment.')->group(function () {
    Route::get('/verify/{reference}', [\App\Http\Controllers\PaymentVerificationController::class, 'verify'])->name('verify');
});

// Hospital Payment System (Public - No Login Required)
Route::prefix('hospital-payment')->name('hospital-payment.')->group(function () {
    Route::get('/services', [\App\Http\Controllers\HospitalPaymentController::class, 'getServiceTypes'])->name('services');
    Route::post('/process', [\App\Http\Controllers\HospitalPaymentController::class, 'processPayment'])->name('process');
    Route::post('/validate', [\App\Http\Controllers\HospitalPaymentController::class, 'validatePayment'])->name('validate');
    Route::get('/check/{reference}', [\App\Http\Controllers\HospitalPaymentController::class, 'checkPayment'])->name('check');
    Route::get('/receipt/{payment}', [\App\Http\Controllers\HospitalPaymentController::class, 'printReceipt'])->name('receipt');

    // Look up recent payments by phone — for patients who lost their
    // receipt URL. Public endpoint, exact-match on patient_phone. Lists
    // the last 10 completed payments and links each to the receipt.
    Route::get('/history', [\App\Http\Controllers\HospitalPaymentController::class, 'historyByPhone'])->name('history');
});

// Hospital Patient Portal (Public - External Patients)
Route::prefix('patient-portal')->name('patient-portal.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'portalIndex'])->name('index');
    Route::get('/register', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'showPortalRegister'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'registerPortal'])->name('register.store');
    Route::get('/login', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'showPortalLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'loginPortal'])->name('login.post');
    Route::post('/logout', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'logoutPortal'])->name('logout');

    // Protected routes (require login)
    Route::middleware('patient-portal')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'dashboardPortal'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'profilePortal'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'updateProfilePortal'])->name('profile.update');
        Route::post('/request-service', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'requestServicePortal'])->name('request-service');
        Route::post('/initiate-payment', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'initiatePaymentPortal'])->name('initiate-payment');
        Route::post('/validate-payment', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'validatePaymentPortal'])->name('validate-payment-portal');
        Route::post('/pay-order-items', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'payOrderItemsPortal'])->name('pay-order-items');
        Route::get('/receipt/{payment}', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'viewReceiptPortal'])->name('receipt');
        // Test-payment simulator: the Pay Now button on the dashboard
        // POSTs here. The action flips status → completed, stamps a
        // payment_date, writes an audit row, and redirects back to the
        // receipt page. See ExternalPatientController::payTestPortal.
        Route::post('/payments/{payment}/pay-test', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'payTestPortal'])->name('payment.pay-test');
        Route::post('/regenerate-code', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'regenerateCodePortal'])->name('regenerate-code');
        Route::get('/prescriptions', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'prescriptionsPortal'])->name('prescriptions');
        Route::get('/prescriptions/{prescription}', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'prescriptionShowPortal'])->name('prescription');
        Route::post('/prescriptions/{prescription}/pay', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'prescriptionPayPortal'])->name('prescription.pay');
        Route::get('/payments', [\App\Http\Controllers\Hospital\ExternalPatientController::class, 'paymentsPortal'])->name('payments');
    });
});

// Auth Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::middleware('guest')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    // Student self-registration is disabled - students must use credentials provided by the school
    // Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    // Route::post('/register', [RegisterController::class, 'register']);

    // Password Reset Routes (Email-based)
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPasswordForm'])->name('password.forgot');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/reset/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');

    // Legacy Secret Question Routes (Deprecated but kept for backward compatibility)
    Route::post('/forgot-password/verify-email', [ForgotPasswordController::class, 'verifyEmail'])->name('password.verify-email');
    Route::get('/forgot-password/secret-question', [ForgotPasswordController::class, 'showSecretQuestionForm'])->name('password.secret-question');
    Route::post('/forgot-password/verify-secret', [ForgotPasswordController::class, 'verifySecretAnswer'])->name('password.verify-secret');
    Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset-form');
});

// Public Unlock Route (accessible when logged out)
Route::get('/unlock/{email}/{code}', [\App\Http\Controllers\Admin\UserUnlockController::class, 'showUnlockCode'])->name('public.unlock');
Route::post('/unlock', [\App\Http\Controllers\Admin\UserUnlockController::class, 'unlockUser'])->name('public.unlock.process');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/logout', function () {
    return redirect('/login');
})->name('logout.get');

// Email Verification Routes
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->name('verification.resend');
    Route::get('/email/check', [EmailVerificationController::class, 'check'])->name('verification.check');
});

// Public Application Form
Route::get('/apply', [ApplicationController::class, 'showApplicationForm'])->name('public.apply');
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

// Applicant Public API Routes (for dropdowns - no auth required)
Route::get('/applicant/departments/{schoolId}', [ApplicationController::class, 'getDepartments']);
Route::get('/applicant/programmes/{departmentId}', [ApplicationController::class, 'getProgrammes']);
Route::get('/applicant/lgas/{stateId}', [ApplicationController::class, 'getLGAs']);

// Applicant Routes
Route::prefix('applicant')->name('applicant.')->group(function () {

    // Payment Validation (External Payment System)
    Route::get('/validate-payment', [\App\Http\Controllers\Applicant\PaymentValidationController::class, 'showValidatePayment'])->name('validate-payment');
    Route::post('/validate-payment', [\App\Http\Controllers\Applicant\PaymentValidationController::class, 'validatePayment'])->name('payment.validate');

    // Public status check (no auth required)
    Route::get('/status', [ApplicationController::class, 'checkStatus'])->name('status');
    Route::post('/status-check', [ApplicationController::class, 'checkStatus'])->name('status.check');

    Route::middleware('guest')->group(function () {
        Route::get('/register', [RegisterController::class, 'showApplicantForm'])->name('register');
        Route::post('/register', [RegisterController::class, 'registerApplicant']);
    });
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [ApplicationController::class, 'dashboard'])->name('dashboard');
        Route::get('/apply', [ApplicationController::class, 'showApplicationForm'])->name('apply');
        Route::post('/apply', [ApplicationController::class, 'submitApplication'])->name('apply.submit');

        // Payment routes - application fee
        Route::post('/apply/fee', [ApplicationController::class, 'initiateApplicationFee'])->name('apply.fee');
        Route::get('/apply/payment/verify', [ApplicationController::class, 'verifyApplicationFee'])->name('apply.payment.verify');
        Route::post('/apply/payment/verify-external', [ApplicationController::class, 'verifyExternalPayment'])->name('payment.verify-external');

        // Apply payment - for application fee payment
        Route::get('/apply/payment', [ApplicationController::class, 'showApplyPayment'])->name('apply.payment');
        Route::post('/apply/payment', [ApplicationController::class, 'processApplyPayment'])->name('apply.payment.process');

        // Separate payment page (for applicants to pay now)
        Route::get('/payment', [PaymentGatewayController::class, 'showPaymentPage'])->name('payment');

        // Payment Gateway - Pay Now with online payment
        Route::get('/payment/gateway', [PaymentGatewayController::class, 'showPaymentPage'])->name('payment.gateway');
        Route::post('/payment/initiate', [PaymentGatewayController::class, 'initiatePayment'])->name('payment.initiate');
        // Retry the most recent pending/failed attempt for a given purpose.
        // Posted from /applicant/payments/history next to the Requery button
        // on rows whose status is pending or failed — reuses the open row
        // (Payment::refreshForRetry) instead of creating a duplicate.
        Route::post('/payment/{purpose}/retry', [PaymentGatewayController::class, 'retryPayment'])
            ->where('purpose', 'application|acceptance|school_fee|compulsory')
            ->name('payment.retry');
        Route::get('/payment/callback', [PaymentGatewayController::class, 'paymentCallback'])->name('payment.callback');
        Route::get('/payment/cancel', [PaymentGatewayController::class, 'cancelPayment'])->name('payment.cancel');

        // Test Payment (for demo)
        Route::get('/payment/test', [PaymentGatewayController::class, 'testPayment'])->name('payment.test');
        Route::post('/payment/test/process', [PaymentGatewayController::class, 'processTestPayment'])->name('payment.test.process');

        // Re-sync the applicant-side side effects of an existing completed
        // payment. Use case: applicant paid before the markCompleted fix
        // (commit 8ad089b1) shipped — the Payment row is status='completed'
        // but `applicants.compulsory_paid_at` was never stamped, so the
        // dashboard still shows "Compulsory Fee: Locked" and the
        // applicant→student migration never ran. POST so it's not CSRF-vulnerable
        // to GET preflight; also reachable from the dashboard button.
        Route::post('/payment/sync', [PaymentGatewayController::class, 'syncPaymentSideEffects'])->name('payment.sync');

        // Explicit "Go to Student Portal" route. After the applicant has
        // paid the compulsory fee and `migrateApplicantToStudent` has
        // produced a Student row, the applicant can claim the portal here
        // even if the auto-redirect from the test handler fired before the
        // migration was visible. We re-run markCompleted → migrateApplicantToStudent
        // defensively before bouncing to the student dashboard.
        Route::post('/payment/transfer', [PaymentGatewayController::class, 'transferToStudentPortal'])->name('payment.transfer');

        // Self-service auto-login to the student portal. Once the applicant
        // has been migrated to a Student row (compulsory fee paid), the
        // dashboard "Go to Student Portal" button hits this endpoint, which
        // mints a signed URL via the existing student-side AutoLoginController
        // and 302s the applicant straight into the change-password form.
        Route::get('/auto-login', [\App\Http\Controllers\Applicant\AutoLoginController::class, 'issue'])
            ->name('auto-login.issue');

        // Shared cross-audience test-payment simulator. Disabled in
        // production by the controller; works for applicant catalogue
        // here. For student + bursar + registrar use the routes at
        // the bottom of this file.
        Route::get('/payment/test/applicant', [TestPaymentController::class, 'show'])->defaults('audience', 'applicant')->name('test.show.applicant');
        Route::post('/payment/test/applicant/process', [TestPaymentController::class, 'process'])->defaults('audience', 'applicant')->name('test.process.applicant');

        Route::get('/application', [ApplicationController::class, 'viewApplication'])->name('application');
        Route::get('/application/edit', [ApplicationController::class, 'editApplication'])->name('application.edit');
        Route::put('/application', [ApplicationController::class, 'updateApplication'])->name('application.update');
        Route::get('/application/print', [ApplicationController::class, 'printApplication'])->name('application.print');

        // Admission letter (post-admit + post-acceptance-fee)
        Route::get('/admission-letter', [ApplicationController::class, 'printAdmissionLetter'])
            ->middleware('applicant.paid:application')
            ->name('admission-letter');

        // Transaction history — any paid applicant can view their history.
        Route::get('/payments/history', [ApplicationController::class, 'transactionHistory'])
            ->middleware('applicant.paid:application')
            ->name('payments.history');

        // Authenticated applicant-side payment receipt. The {payment}
        // segment is polymorphic — the controller resolves it against
        // Payment.id first, then ExternalPayment.id, and aborts 403 if
        // the row doesn't belong to the authenticated applicant. Replaces
        // the public `online-payment.receipt` route for the "from the
        // portal" use case (the public route is still used by the
        // gateway's JSON response).
        Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\Applicant\PaymentReceiptController::class, 'show'])
            ->name('payments.receipt');
    });
});

// TEMP: Print route - Only available in local environment
Route::get('/temp-print', function() {
    // Only allow in local environment
    if (app()->environment('production')) {
        abort(404);
    }

    $user = \App\Models\User::where('email', 'opefemmy9@gmail.com')->first();
    if (!$user) {
        abort(404, 'User not found');
    }
    \Illuminate\Support\Facades\Auth::login($user);
    $applicant = \App\Models\Applicant::where('user_id', $user->id)->first();
    return view('applicant.print-simple', compact('applicant'));
});

// Direct print route - Only available in local environment
Route::get('/print-preview', function() {
    // Only allow in local environment
    if (app()->environment('production')) {
        abort(404);
    }

    $user = \App\Models\User::where('email', 'opefemmy9@gmail.com')->first();
    if (!$user) {
        abort(404, 'User not found');
    }
    \Illuminate\Support\Facades\Auth::login($user);
    $applicant = \App\Models\Applicant::where('user_id', $user->id)->first();
    return view('applicant.print', compact('applicant'));
});

// Admin Routes - redirect /admin to /admin/dashboard
Route::redirect('/admin', '/admin/dashboard');

// Admin Dashboard (requires auth and admin role). ICT admin and staff
// share the same admin dashboard, so include them in the role list.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin,admin,ict_admin,staff'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Per-user dashboard widget configurator. Only super_admin can
    // configure anybody else's dashboard; admin/ict_admin/staff can
    // only configure their own. The role middleware above already
    // admits those roles, but we additionally gate by a sub-middleware
    // on `super_admin` for the cross-user endpoints.
    //
    // Slice 8i-routes: also wire `permission:admin.dashboard.configure`
    // for defence-in-depth — mirrors the trait-side
    // `requirePermission()` in AdminDashboardConfigController (slice
    // 8i-controller).
    Route::get('/dashboard-config/{user}', [AdminDashboardConfigController::class, 'edit'])
        ->middleware(['role:super_admin', 'permission:admin.dashboard.configure'])
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [AdminDashboardConfigController::class, 'update'])
        ->middleware(['role:super_admin', 'permission:admin.dashboard.configure'])
        ->name('dashboard-config.update');

    // User Unlock / Password Reset (MUST come before resource routes)
    Route::get('/users/unlock', [UserUnlockController::class, 'showUnlockForm'])->name('users.unlock');
    Route::post('/users/unlock/generate', [UserUnlockController::class, 'generateUnlockCode'])->name('users.unlock.generate');
    Route::get('/users/unlock/code', [UserUnlockController::class, 'showUnlockCode'])->name('users.unlock.code');
    Route::post('/users/unlock', [UserUnlockController::class, 'unlockUser'])->name('users.unlock.process');
    Route::post('/users/unlock/quick', [UserUnlockController::class, 'quickUnlock'])->name('users.unlock.quick');
    Route::post('/users/unlock/reset', [UserUnlockController::class, 'resetUserPassword'])->name('users.unlock.reset');

    // User Management
    Route::resource('users', UserController::class);
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset_password');
    Route::get('/users/upload', [UserController::class, 'upload'])->name('users.upload');
    // Per-user multi-role assignment (writes to role_user pivot +
    // users.role_id). Used by the modal on admin/users.
    Route::put('/users/{user}/roles', [\App\Http\Controllers\Admin\UserRoleController::class, 'update'])
        ->name('users.roles.update');

    Route::post('/users/upload', [UserController::class, 'processUpload'])->name('users.upload.process');
    Route::post('/users/{user}/passport', [UserController::class, 'uploadPassport'])->name('users.passport');

    // Institution Setup
    Route::resource('schools', SchoolController::class);
    Route::resource('departments', DepartmentController::class);
    Route::resource('programmes', ProgrammeController::class);
    Route::resource('sessions', SessionController::class);
    Route::resource('admission-centres', \App\Http\Controllers\Admin\AdmissionCentreController::class);
    Route::post('/admission-centres/{centre}/toggle', [\App\Http\Controllers\Admin\AdmissionCentreController::class, 'toggleStatus'])->name('admission-centres.toggle');
    Route::post('/sessions/{session}/set-current', [SessionController::class, 'setCurrent'])->name('sessions.set_current');

    // Hospital Services Management
    Route::resource('hospital-services', \App\Http\Controllers\Admin\HospitalServiceController::class);
    Route::post('/hospital-services/{service}/toggle', [\App\Http\Controllers\Admin\HospitalServiceController::class, 'toggleStatus'])->name('hospital-services.toggle');

    // Course Management
    Route::get('/courses/upload', [CourseController::class, 'uploadForm'])->name('courses.upload.form');
    Route::post('/courses/upload', [CourseController::class, 'upload'])->name('courses.upload');
    Route::resource('courses', CourseController::class);

    // Fee Management
    Route::resource('fees', FeeController::class);

    // Payment Types Management
    Route::resource('payment-types', PaymentTypeController::class);
    Route::post('/payment-types/{paymentType}/toggle', [PaymentTypeController::class, 'toggle'])->name('payment-types.toggle');

    // Admission Payment Flow — combined config screen (amounts + live overrides + gates)
    Route::get('/admission/payment-flow', [\App\Http\Controllers\Admin\PaymentFlowController::class, 'edit'])->name('admission.payment-flow');
    Route::put('/admission/payment-flow', [\App\Http\Controllers\Admin\PaymentFlowController::class, 'update'])->name('admission.payment-flow.update');

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
    Route::post('/settings/branding', [SystemSettingController::class, 'updateBranding'])->name('settings.branding');

    // Download branding files
    Route::get('/settings/download/logo', [SystemSettingController::class, 'downloadLogo'])->name('settings.download.logo');
    Route::get('/settings/download/icon', [SystemSettingController::class, 'downloadIcon'])->name('settings.download.icon');
    Route::get('/settings/download/house-icon', [SystemSettingController::class, 'downloadHouseIcon'])->name('settings.download.house-icon');

    // Delete branding files
    Route::delete('/settings/delete/logo', [SystemSettingController::class, 'deleteLogo'])->name('settings.delete.logo');
    Route::delete('/settings/delete/icon', [SystemSettingController::class, 'deleteIcon'])->name('settings.delete.icon');
    Route::delete('/settings/delete/house-icon', [SystemSettingController::class, 'deleteHouseIcon'])->name('settings.delete.house-icon');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/students', [ReportController::class, 'students'])->name('reports.students');
    Route::get('/reports/applications', [ReportController::class, 'applications'])->name('reports.applications');
    Route::get('/reports/results', [ReportController::class, 'results'])->name('reports.results');
    Route::get('/reports/payments', [ReportController::class, 'payments'])->name('reports.payments');

    // Staff Management
    Route::resource('staff', StaffController::class);
    Route::post('/staff/{user}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset_password');

    // Student Management (literal routes first to avoid conflict with wildcard)
    Route::get('/students/import', [StudentImportController::class, 'index'])->name('students.import');
    Route::post('/students/import', [StudentImportController::class, 'import'])->name('students.import.store');
    Route::get('/students/import/template', [StudentImportController::class, 'downloadTemplate'])->name('students.import.template');
    Route::get('/students/measurements/export', [StudentController::class, 'exportMeasurements'])->name('students.measurements.export');
    Route::get('/students/lgas/{stateId}', [StudentController::class, 'getLGAs']);

    Route::resource('students', StudentController::class);
    Route::post('/students/{student}/reset-password', [StudentController::class, 'resetPassword'])->name('students.reset_password');

    // Student Uniform Measurements
    Route::get('/students/{student}/measurements', [StudentController::class, 'showMeasurements'])->name('students.measurements');
    Route::get('/students/{student}/measurements/edit', [StudentController::class, 'editMeasurements'])->name('students.measurements.edit');
    Route::put('/students/{student}/measurements', [StudentController::class, 'updateMeasurements'])->name('students.measurements.update');

    // Complaints Management
    Route::resource('complaints', \App\Http\Controllers\Admin\ComplaintController::class);

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

    // Previous-results upload (historical / pre-portal rows for transcripts).
    Route::get('/previous-results', [\App\Http\Controllers\Admin\PreviousResultController::class, 'index'])->name('previous-results.index');
    Route::get('/previous-results/upload', [\App\Http\Controllers\Admin\PreviousResultController::class, 'create'])->name('previous-results.create');
    Route::post('/previous-results/upload', [\App\Http\Controllers\Admin\PreviousResultController::class, 'upload'])->name('previous-results.upload');
    Route::get('/previous-results/template', [\App\Http\Controllers\Admin\PreviousResultController::class, 'downloadTemplate'])->name('previous-results.template');
    Route::delete('/previous-results/{previousResult}', [\App\Http\Controllers\Admin\PreviousResultController::class, 'destroy'])->name('previous-results.destroy');
    Route::post('/previous-results/purge-student/{student}', [\App\Http\Controllers\Admin\PreviousResultController::class, 'purgeForStudent'])->name('previous-results.purge-student');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // System Maintenance Routes
    //
    // Slice 8i-maintenance-routes: each route carries the same
    // `permission:slug` middleware as the controller's trait gate.
    // This is the second layer of defence — even if a focused
    // operator role slips through the `role:` middleware somehow
    // (e.g. an ad-hoc role grant), the permission:slug middleware
    // 403s them at the route resolution stage, before the
    // controller body runs.
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\MaintenanceController::class, 'dashboard'])
            ->middleware('permission:maintenance.dashboard.view')
            ->name('dashboard');
        Route::get('/health', [\App\Http\Controllers\Admin\MaintenanceController::class, 'healthCheck'])
            ->middleware('permission:maintenance.health.view')
            ->name('health');
        Route::post('/health/run', [\App\Http\Controllers\Admin\MaintenanceController::class, 'runHealthCheck'])
            ->middleware('permission:maintenance.health.repair')
            ->name('health.run');
        Route::post('/health/repair', [\App\Http\Controllers\Admin\MaintenanceController::class, 'runHealthCheck'])
            ->middleware('permission:maintenance.health.repair')
            ->name('health.repair');
        Route::get('/updates', [\App\Http\Controllers\Admin\MaintenanceController::class, 'updateManager'])
            ->middleware('permission:maintenance.updates.view')
            ->name('updates');
        Route::post('/migrations/run', [\App\Http\Controllers\Admin\MaintenanceController::class, 'runMigrations'])
            ->middleware('permission:maintenance.updates.apply')
            ->name('migrations.run');
        Route::post('/seeders/run', [\App\Http\Controllers\Admin\MaintenanceController::class, 'runSeeders'])
            ->middleware('permission:maintenance.updates.apply')
            ->name('seeders.run');
        Route::post('/repairs/run', [\App\Http\Controllers\Admin\MaintenanceController::class, 'runRepairs'])
            ->middleware('permission:maintenance.repairs.run')
            ->name('repairs.run');
        Route::get('/migrations', [\App\Http\Controllers\Admin\MaintenanceController::class, 'migrations'])
            ->middleware('permission:maintenance.updates.view')
            ->name('migrations');
        Route::get('/database', [\App\Http\Controllers\Admin\MaintenanceController::class, 'databaseRepair'])
            ->middleware('permission:maintenance.repairs.view')
            ->name('database');
        Route::get('/modules', [\App\Http\Controllers\Admin\MaintenanceController::class, 'moduleScanner'])
            ->middleware('permission:maintenance.scanners.view')
            ->name('modules');
        Route::get('/permissions', [\App\Http\Controllers\Admin\MaintenanceController::class, 'permissionScanner'])
            ->middleware('permission:maintenance.scanners.view')
            ->name('permissions');
        Route::get('/storage', [\App\Http\Controllers\Admin\MaintenanceController::class, 'storageScanner'])
            ->middleware('permission:maintenance.scanners.view')
            ->name('storage');
        Route::get('/cache', [\App\Http\Controllers\Admin\MaintenanceController::class, 'cacheManager'])
            ->middleware('permission:maintenance.cache.view')
            ->name('cache');
        Route::post('/cache/clear', [\App\Http\Controllers\Admin\MaintenanceController::class, 'clearCaches'])
            ->middleware('permission:maintenance.cache.manage')
            ->name('cache.clear');
        Route::post('/optimize', [\App\Http\Controllers\Admin\MaintenanceController::class, 'optimizeSystem'])
            ->middleware('permission:maintenance.cache.manage')
            ->name('optimize');
        Route::get('/backups', [\App\Http\Controllers\Admin\MaintenanceController::class, 'backupManager'])
            ->middleware('permission:maintenance.backups.view')
            ->name('backups');
        Route::post('/backup/create', [\App\Http\Controllers\Admin\MaintenanceController::class, 'createBackup'])
            ->middleware('permission:maintenance.backups.create')
            ->name('backup.create');
        Route::get('/logs', [\App\Http\Controllers\Admin\MaintenanceController::class, 'logViewer'])
            ->middleware('permission:maintenance.logs.view')
            ->name('logs');
        Route::get('/versions', [\App\Http\Controllers\Admin\MaintenanceController::class, 'versionManager'])
            ->middleware('permission:maintenance.versions.view')
            ->name('versions');
        Route::post('/version/register', [\App\Http\Controllers\Admin\MaintenanceController::class, 'registerVersion'])
            ->middleware('permission:maintenance.versions.manage')
            ->name('version.register');
        Route::get('/report', [\App\Http\Controllers\Admin\MaintenanceController::class, 'systemReport'])
            ->middleware('permission:maintenance.report.view')
            ->name('report');
    });
});

// Student Routes
// Auto-login consume endpoint — sits OUTSIDE the auth-guarded student
// group because the user arrives unauthenticated (via a signed URL
// the registrar generated). The `signed` middleware enforces URL
// integrity and an absolute expiry; once inside the controller we
// sign the user in and bounce them to the password-change form.
Route::get('/student/auto-login/{user}', [\App\Http\Controllers\Student\AutoLoginController::class, 'consume'])
    ->middleware('signed')
    ->name('student.auto-login.consume');

Route::prefix('student')->name('student.')->middleware(['auth', 'role:student', 'student.onboarding'])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

    // Per-user dashboard widget configurator. Slice 8i-controller
    // added the trait gate; slice 8i-routes mirrors onto the route.
    Route::get('/dashboard-config/{user}', [StudentDashboardConfigController::class, 'edit'])
        ->middleware('permission:student.dashboard.configure')
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [StudentDashboardConfigController::class, 'update'])
        ->middleware('permission:student.dashboard.configure')
        ->name('dashboard-config.update');
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
    Route::post('/payments/{fee}/initiate', [PaymentController::class, 'initiatePayment'])->name('payments.initiate');
    // Retry a pending/failed school-fee payment. Reuses the open row
    // (Payment::refreshForRetry) and refreshes the gateway reference
    // instead of creating a duplicate. Ownership is enforced in the
    // controller via Student::where('user_id', auth()->id()).
    Route::post('/payments/{payment}/retry', [PaymentController::class, 'retryPayment'])->name('payments.retry');
    Route::get('/payments/verify', [PaymentController::class, 'verifyPayment'])->name('payments.verify');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'printReceipt'])->name('payments.receipt');

    // Shared cross-audience test-payment simulator (student view).
    Route::get('/payment/test', [TestPaymentController::class, 'show'])->defaults('audience', 'student')->name('payment.test.show.student');
    Route::post('/payment/test/process', [TestPaymentController::class, 'process'])->defaults('audience', 'student')->name('payment.test.process.student');

    // Exam clearance
    Route::get('/exam-clearance', [\App\Http\Controllers\Student\ExamClearanceController::class, 'index'])->name('exam-clearance');
    Route::get('/exam-clearance/print', [\App\Http\Controllers\Student\ExamClearanceController::class, 'print'])->name('exam-clearance.print');

    // Admission letter — for migrated applicants who need to reprint
    // after being signed into the student portal. Reuses the
    // applicant.admission-letter blade (same letter, just surfaced on
    // the side the user now lives on).
    Route::get('/admission-letter', [\App\Http\Controllers\Student\AdmissionLetterController::class, 'show'])->name('admission-letter');

    Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable');

    // Student Attendance
    Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance');
    Route::post('/attendance/mark', [StudentAttendanceController::class, 'markAttendance'])->name('attendance.mark');
    Route::get('/my-attendance', [StudentAttendanceController::class, 'myAttendance'])->name('my-attendance');

    // Complaints
    Route::get('/complaints', [\App\Http\Controllers\Student\ComplaintController::class, 'index'])->name('complaints');
    Route::post('/complaints', [\App\Http\Controllers\Student\ComplaintController::class, 'store'])->name('complaints.store');

    // Profile
    Route::get('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/passport', [\App\Http\Controllers\Student\ProfileController::class, 'uploadPassport'])->name('profile.passport');

    // Measurements (Uniform, Scrubs, Lab Coat)
    Route::get('/measurements', function() {
        return view('student.measurements');
    })->name('measurements');

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
//
// Slice 8f-web: every gated route below carries a `permission:slug`
// middleware. The slug is copied verbatim from the controller method's
// `requirePermission(...)` call. Dashboard-config routes stay ungated —
// LecturerDashboardConfigController doesn't call requirePermission.
Route::prefix('lecturer')->name('lecturer.')->middleware(['auth', 'role:lecturer'])->group(function () {
    Route::get('/dashboard', [LecturerDashboardController::class, 'index'])
        ->middleware('permission:academic.dashboard.view')
        ->name('dashboard');

    // Per-user dashboard widget configurator. Slice 8i-controller
    // added the trait gate; slice 8i-routes mirrors onto the route.
    Route::get('/dashboard-config/{user}', [LecturerDashboardConfigController::class, 'edit'])
        ->middleware('permission:academic.dashboard.configure')
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [LecturerDashboardConfigController::class, 'update'])
        ->middleware('permission:academic.dashboard.configure')
        ->name('dashboard-config.update');
    Route::get('/courses', [LecturerDashboardController::class, 'courses'])
        ->middleware('permission:academic.courses.view')
        ->name('courses');
    Route::get('/courses/{course}/students', [LecturerResultController::class, 'courseStudents'])
        ->middleware('permission:academic.results.view')
        ->name('courses.students');
    Route::get('/courses/{course}/results', [LecturerResultController::class, 'enter'])
        ->middleware('permission:academic.results.enter')
        ->name('courses.results');
    Route::post('/courses/{course}/results', [LecturerResultController::class, 'store'])
        ->middleware('permission:academic.results.enter')
        ->name('courses.results.store');
    Route::post('/courses/{course}/results/bulk', [LecturerResultController::class, 'bulkUpload'])
        ->middleware('permission:academic.results.enter')
        ->name('courses.bulk');
    Route::get('/courses/{course}/template', [LecturerResultController::class, 'downloadTemplate'])
        ->middleware('permission:academic.results.enter')
        ->name('courses.template');

    // Edit result before HOD approval
    Route::get('/result/{result}/edit', [LecturerResultController::class, 'edit'])
        ->middleware('permission:academic.results.edit')
        ->name('result.edit');
    Route::put('/result/{result}', [LecturerResultController::class, 'update'])
        ->middleware('permission:academic.results.edit')
        ->name('result.update');

    Route::get('/attendance/{course}', [AttendanceController::class, 'index'])
        ->middleware('permission:academic.attendance.view')
        ->name('attendance');
    Route::post('/attendance/{course}', [AttendanceController::class, 'mark'])
        ->middleware('permission:academic.attendance.mark');
    Route::get('/attendance/{course}/report', [AttendanceController::class, 'report'])
        ->middleware('permission:academic.attendance.view')
        ->name('attendance.report');

    Route::get('/timetable', [LecturerDashboardController::class, 'timetable'])
        ->middleware('permission:academic.timetables.view')
        ->name('timetable');
});

// HOD Routes
//
// Slice 8f-web: every gated route below carries a `permission:slug`
// middleware. Slug copied verbatim from controller method's
// `requirePermission(...)`. Dashboard-config routes stay ungated.
Route::prefix('hod')->name('hod.')->middleware(['auth', 'role:hod'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\HOD\DashboardController::class, 'index'])
        ->middleware('permission:academic.dashboard.view')
        ->name('dashboard');

    // Per-user dashboard widget configurator. Slice 8i-controller
    // added the trait gate; slice 8i-routes mirrors onto the route.
    Route::get('/dashboard-config/{user}', [HodDashboardConfigController::class, 'edit'])
        ->middleware('permission:academic.dashboard.configure')
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [HodDashboardConfigController::class, 'update'])
        ->middleware('permission:academic.dashboard.configure')
        ->name('dashboard-config.update');
    Route::get('/courses', [\App\Http\Controllers\HOD\CourseController::class, 'index'])
        ->middleware('permission:academic.courses.view')
        ->name('courses');
    Route::get('/courses/assign', [\App\Http\Controllers\HOD\CourseController::class, 'assign'])
        ->middleware('permission:academic.courses.assign')
        ->name('courses.assign');
    Route::post('/courses/assign', [\App\Http\Controllers\HOD\CourseController::class, 'storeAssignment'])
        ->middleware('permission:academic.courses.assign')
        ->name('courses.assign.store');
    Route::put('/courses/{assignment}/reassign', [\App\Http\Controllers\HOD\CourseController::class, 'reassign'])
        ->middleware('permission:academic.courses.assign')
        ->name('courses.reassign');
    Route::delete('/courses/{assignment}/remove', [\App\Http\Controllers\HOD\CourseController::class, 'removeAssignment'])
        ->middleware('permission:academic.courses.assign')
        ->name('courses.remove');

    Route::get('/timetable', [\App\Http\Controllers\HOD\TimetableController::class, 'index'])
        ->middleware('permission:academic.timetables.view')
        ->name('timetable');
    Route::put('/timetable/{timetable}/approve', [\App\Http\Controllers\HOD\TimetableController::class, 'approve'])
        ->middleware('permission:academic.timetables.edit')
        ->name('timetable.approve');
    Route::put('/timetable/{timetable}/reject', [\App\Http\Controllers\HOD\TimetableController::class, 'reject'])
        ->middleware('permission:academic.timetables.edit')
        ->name('timetable.reject');

    Route::get('/results', [\App\Http\Controllers\HOD\ResultController::class, 'index'])
        ->middleware('permission:academic.results.view')
        ->name('results.index');
    Route::put('/results/{result}/approve', [\App\Http\Controllers\HOD\ResultController::class, 'approve'])
        ->middleware('permission:academic.results.approve')
        ->name('results.approve');
    Route::put('/results/{result}/reject', [\App\Http\Controllers\HOD\ResultController::class, 'reject'])
        ->middleware('permission:academic.results.approve')
        ->name('results.reject');
    Route::post('/results/bulk-approve', [\App\Http\Controllers\HOD\ResultController::class, 'bulkApprove'])
        ->middleware('permission:academic.results.approve')
        ->name('results.bulkApprove');
    Route::post('/results/bulk-reject', [\App\Http\Controllers\HOD\ResultController::class, 'bulkReject'])
        ->middleware('permission:academic.results.approve')
        ->name('results.bulkReject');
});

// Dean Routes
//
// Slice 8f-web: every gated route below carries a `permission:slug`
// middleware. Slug copied verbatim from controller method's
// `requirePermission(...)`. Dashboard-config routes stay ungated.
Route::prefix('dean')->name('dean.')->middleware(['auth', 'role:dean'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Dean\DashboardController::class, 'index'])
        ->middleware('permission:academic.dashboard.view')
        ->name('dashboard');

    // Per-user dashboard widget configurator. Slice 8i-controller
    // added the trait gate; slice 8i-routes mirrors onto the route.
    Route::get('/dashboard-config/{user}', [DeanDashboardConfigController::class, 'edit'])
        ->middleware('permission:academic.dashboard.configure')
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [DeanDashboardConfigController::class, 'update'])
        ->middleware('permission:academic.dashboard.configure')
        ->name('dashboard-config.update');
    Route::get('/departments', [\App\Http\Controllers\Dean\DepartmentController::class, 'index'])
        ->middleware('permission:academic.departments.view')
        ->name('departments');
    Route::get('/results', [\App\Http\Controllers\Dean\ResultController::class, 'index'])
        ->middleware('permission:academic.results.view')
        ->name('results');
    Route::put('/results/{result}/approve', [\App\Http\Controllers\Dean\ResultController::class, 'approve'])
        ->middleware('permission:academic.results.approve')
        ->name('results.approve');
    Route::post('/results/bulk-approve', [\App\Http\Controllers\Dean\ResultController::class, 'bulkApprove'])
        ->middleware('permission:academic.results.approve')
        ->name('results.bulkApprove');
    Route::post('/results/bulk-reject', [\App\Http\Controllers\Dean\ResultController::class, 'bulkReject'])
        ->middleware('permission:academic.results.approve')
        ->name('results.bulkReject');
});

// Registrar Routes - accessible by registrar, admin, super_admin, and admission_officer
Route::prefix('registrar')->name('registrar.')->middleware(['auth', 'role:registrar,super_admin,admin,admission_officer'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Registrar\DashboardController::class, 'index'])
        ->middleware('permission:registrar.dashboard.view')
        ->name('dashboard');

    // Per-user dashboard widget configurator. Slice 8i-controller
    // added the trait gate; slice 8i-routes mirrors onto the route.
    Route::get('/dashboard-config/{user}', [RegistrarDashboardConfigController::class, 'edit'])
        ->middleware('permission:registrar.dashboard.configure')
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [RegistrarDashboardConfigController::class, 'update'])
        ->middleware('permission:registrar.dashboard.configure')
        ->name('dashboard-config.update');

    // Application Management
    // Literal sub-paths (statistics, export, bulk) MUST come before /{applicant}
    // wildcard or Laravel will try to match "statistics" as an applicant id.
    Route::get('/applications', [\App\Http\Controllers\Registrar\ApplicationController::class, 'index'])
        ->middleware('permission:registrar.applicants.view')
        ->name('applications.index');
    Route::get('/applications/statistics', [\App\Http\Controllers\Registrar\ApplicationController::class, 'statistics'])
        ->middleware('permission:registrar.reports.view')
        ->name('applications.statistics');
    Route::get('/applications/export', [\App\Http\Controllers\Registrar\ApplicationController::class, 'export'])
        ->middleware('permission:registrar.reports.export')
        ->name('applications.export');
    Route::post('/applications/bulk', [\App\Http\Controllers\Registrar\ApplicationController::class, 'bulkAction'])
        ->middleware('permission:registrar.applicants.status-update')
        ->name('applications.bulk');
    Route::get('/admitted-students', [\App\Http\Controllers\Registrar\ApplicationController::class, 'admittedStudents'])
        ->middleware('permission:registrar.applicants.view')
        ->name('applications.admitted');
    Route::get('/applications/{applicant}', [\App\Http\Controllers\Registrar\ApplicationController::class, 'show'])
        ->middleware('permission:registrar.applicants.view')
        ->name('applications.show');
    Route::put('/applications/{applicant}/status', [\App\Http\Controllers\Registrar\ApplicationController::class, 'updateStatus'])
        ->middleware('permission:registrar.applicants.status-update')
        ->name('applications.updateStatus');

    Route::get('/applicants', [\App\Http\Controllers\Registrar\ApplicantController::class, 'index'])
        ->middleware('permission:registrar.applicants.view')
        ->name('applicants');
    Route::get('/applicants/{applicant}', [\App\Http\Controllers\Registrar\ApplicantController::class, 'show'])
        ->middleware('permission:registrar.applicants.view')
        ->name('applicants.show');
    Route::put('/applicants/{applicant}/admit', [\App\Http\Controllers\Registrar\ApplicantController::class, 'admit'])
        ->middleware('permission:registrar.applicants.status-update')
        ->name('applicants.admit');
    Route::put('/applicants/{applicant}/reject', [\App\Http\Controllers\Registrar\ApplicantController::class, 'reject'])
        ->middleware('permission:registrar.applicants.status-update')
        ->name('applicants.reject');
    Route::get('/admission-list', [\App\Http\Controllers\Registrar\AdmissionController::class, 'index'])
        ->middleware('permission:registrar.admissions.view')
        ->name('admission');

    // Literal sub-paths FIRST so they don't get shadowed by /{applicant} wildcard.
    Route::get('/admission-list/settings', [\App\Http\Controllers\Registrar\AdmissionController::class, 'settings'])
        ->middleware('permission:registrar.settings.view')
        ->name('admission.settings');
    Route::put('/admission-list/settings', [\App\Http\Controllers\Registrar\AdmissionController::class, 'updateSettings'])
        ->middleware('permission:registrar.settings.edit')
        ->name('admission.updateSettings');
    Route::get('/admission-list/print', [\App\Http\Controllers\Registrar\AdmissionController::class, 'print'])
        ->middleware('permission:registrar.admissions.view')
        ->name('admission.print');
    Route::post('/admission-list/upload', [\App\Http\Controllers\Registrar\AdmissionController::class, 'upload'])
        ->middleware('permission:registrar.admissions.bulk-upload')
        ->name('admission.upload');
    Route::get('/admission-list/by-department', [\App\Http\Controllers\Registrar\AdmissionController::class, 'listByDepartment'])
        ->middleware('permission:registrar.admissions.view')
        ->name('admission.byDepartment');
    Route::get('/admission-list/upload', [\App\Http\Controllers\Registrar\AdmissionController::class, 'showUploadByDepartment'])
        ->middleware('permission:registrar.admissions.bulk-upload')
        ->name('admission.uploadByDepartment');
    Route::post('/admission-list/upload-by-department', [\App\Http\Controllers\Registrar\AdmissionController::class, 'uploadAdmissionList'])
        ->middleware('permission:registrar.admissions.bulk-upload');

    // Wildcard /{applicant} routes AFTER the literal ones.
    Route::get('/admission-list/{applicant}', [\App\Http\Controllers\Registrar\AdmissionController::class, 'show'])
        ->middleware('permission:registrar.admissions.view')
        ->name('admission.show');
    Route::get('/admission-list/{applicant}/edit', [\App\Http\Controllers\Registrar\AdmissionController::class, 'edit'])
        ->middleware('permission:registrar.applicants.edit')
        ->name('admission.edit');
    Route::put('/admission-list/{applicant}', [\App\Http\Controllers\Registrar\AdmissionController::class, 'update'])
        ->middleware('permission:registrar.applicants.edit')
        ->name('admission.update');
    Route::delete('/admission-list/{applicant}', [\App\Http\Controllers\Registrar\AdmissionController::class, 'destroy'])
        ->middleware('permission:registrar.applicants.edit')
        ->name('admission.destroy');
    Route::post('/admission-list/{applicant}/reset-password', [\App\Http\Controllers\Registrar\AdmissionController::class, 'resetPassword'])
        ->middleware('permission:registrar.applicants.reset-password')
        ->name('admission.resetPassword');
    Route::put('/admission-list/{applicant}/status', [\App\Http\Controllers\Registrar\AdmissionController::class, 'updateStatus'])
        ->middleware('permission:registrar.applicants.status-update')
        ->name('admission.updateStatus');
    Route::get('/admission-track', [\App\Http\Controllers\Registrar\AdmissionController::class, 'track'])
        ->middleware('permission:registrar.admissions.track')
        ->name('admission.track');

    // Admission Letters (literal /settings, /template, /generate before /{applicant} wildcard)
    Route::get('/admission-letter/settings', [\App\Http\Controllers\Registrar\AdmissionController::class, 'showLetterSettings'])
        ->middleware('permission:registrar.settings.view')
        ->name('admission.letters');
    Route::post('/admission-letter/settings', [\App\Http\Controllers\Registrar\AdmissionController::class, 'saveLetterSettings'])
        ->middleware('permission:registrar.settings.edit')
        ->name('admission.saveLetterSettings');
    Route::delete('/admission-letter/signature', [\App\Http\Controllers\Registrar\AdmissionController::class, 'deleteSignature'])
        ->middleware('permission:registrar.settings.edit')
        ->name('admission.deleteSignature');
    // Partial auto-save for individual fields (registrar_name, fees).
    // Hit via fetch() on blur of the input — page does not reload.
    Route::patch('/admission-letter/settings/field', [\App\Http\Controllers\Registrar\AdmissionController::class, 'saveLetterField'])
        ->middleware('permission:registrar.settings.edit')
        ->name('admission.saveLetterField');
    Route::get('/admission-letter/template', [\App\Http\Controllers\Registrar\AdmissionController::class, 'showLetterTemplate'])
        ->middleware('permission:registrar.settings.view')
        ->name('admission.uploadTemplate');
    Route::post('/admission-letter/template', [\App\Http\Controllers\Registrar\AdmissionController::class, 'uploadLetterTemplate'])
        ->middleware('permission:registrar.settings.edit')
        ->name('admission.uploadTemplate.store');
    Route::get('/admission-letter/generate', [\App\Http\Controllers\Registrar\AdmissionController::class, 'generateLetters'])
        ->middleware('permission:registrar.admissions.generate-letter')
        ->name('admission.generateLetters');
    Route::get('/admission-letter/{applicant}', [\App\Http\Controllers\Registrar\AdmissionController::class, 'generateLetter'])
        ->middleware('permission:registrar.admissions.generate-letter')
        ->name('admission.generateLetter');

    // Shared test-payment simulator (controller does NOT gate — skip per slice 8f-web decision).
    Route::get('/payment/test', [TestPaymentController::class, 'show'])->defaults('audience', 'both')->name('payment.test.show');
    Route::post('/payment/test/process', [TestPaymentController::class, 'process'])->defaults('audience', 'both')->name('payment.test.process');
});

// Bursar Routes. The bursary staff roles seeded by ERPRolesSeeder
// (bursary_officer, fees_officer, payment_officer) and cashier all need
// access to the same dashboard — they all share the bursar reports /
// paid-students / regimes / payments screens. super_admin and admin
// are included so the platform admins can debug any bursar flow.
Route::prefix('bursar')->name('bursar.')->middleware(['auth', 'role:bursar,bursary_officer,fees_officer,payment_officer,cashier,accountant,account_officer,finance_officer,finance,auditor,internal_auditor,external_auditor,ict_admin,hospital_accountant,super_admin,admin'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Bursar\DashboardController::class, 'index'])
        ->middleware('permission:bursar.dashboard.view')
        ->name('dashboard');

    // Per-user dashboard widget configurator. Slice 8i-controller
    // added the trait gate to BursarDashboardConfigController; this
    // is the route-layer mirror (slice 8i-routes) for defence-in-depth.
    Route::get('/dashboard-config/{user}', [BursarDashboardConfigController::class, 'edit'])
        ->middleware('permission:bursar.dashboard.configure')
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [BursarDashboardConfigController::class, 'update'])
        ->middleware('permission:bursar.dashboard.configure')
        ->name('dashboard-config.update');
    Route::get('/debtors', [\App\Http\Controllers\Bursar\DashboardController::class, 'debtors'])
        ->middleware('permission:bursar.debtors.view')
        ->name('debtors');
    Route::get('/paid-students', [\App\Http\Controllers\Bursar\DashboardController::class, 'paidStudents'])
        ->middleware('permission:bursar.payments.view')
        ->name('paid-students');

    // Shared test-payment simulator (controller does NOT gate — skip per slice 8f-web decision).
    Route::get('/payment/test', [TestPaymentController::class, 'show'])->defaults('audience', 'both')->name('payment.test.show');
    Route::post('/payment/test/process', [TestPaymentController::class, 'process'])->defaults('audience', 'both')->name('payment.test.process');
    Route::get('/payments', [\App\Http\Controllers\Bursar\PaymentController::class, 'index'])
        ->middleware('permission:bursar.payments.view')
        ->name('payments');
    Route::get('/payments/{payment}/verify', [\App\Http\Controllers\Bursar\PaymentController::class, 'verify'])
        ->middleware('permission:bursar.payments.verify')
        ->name('payments.verify');
    Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\Bursar\PaymentController::class, 'receipt'])
        ->middleware('permission:bursar.payments.view')
        ->name('payments.receipt');
    Route::get('/reports', [\App\Http\Controllers\Bursar\ReportController::class, 'index'])
        ->middleware('permission:bursar.reports.view')
        ->name('reports');

    // External Payment Upload
    Route::get('/payments/upload', [\App\Http\Controllers\Bursar\PaymentController::class, 'showUploadForm'])
        ->middleware('permission:bursar.payments.create')
        ->name('payments.upload');
    Route::post('/payments/upload', [\App\Http\Controllers\Bursar\PaymentController::class, 'uploadPayments'])
        ->middleware('permission:bursar.payments.create')
        ->name('payments.upload.store');

    // Payment Synchronization (New)
    Route::prefix('payments/sync')->name('payments.sync.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Bursar\PaymentSyncController::class, 'index'])
            ->middleware('permission:bursar.payments.view')
            ->name('index');
        Route::get('/upload', [\App\Http\Controllers\Bursar\PaymentSyncController::class, 'showUploadForm'])
            ->middleware('permission:bursar.payments.create')
            ->name('upload');
        Route::post('/preview', [\App\Http\Controllers\Bursar\PaymentSyncController::class, 'preview'])
            ->middleware('permission:bursar.payments.create')
            ->name('preview');
        Route::get('/preview', [\App\Http\Controllers\Bursar\PaymentSyncController::class, 'previewResults'])
            ->middleware('permission:bursar.payments.create')
            ->name('preview.results');
        Route::post('/import', [\App\Http\Controllers\Bursar\PaymentSyncController::class, 'import'])
            ->middleware('permission:bursar.payments.create')
            ->name('import');
        Route::get('/template', [\App\Http\Controllers\Bursar\PaymentSyncController::class, 'downloadTemplate'])
            ->middleware('permission:bursar.payments.create')
            ->name('template');
        Route::get('/logs', [\App\Http\Controllers\Bursar\PaymentSyncController::class, 'logs'])
            ->middleware('permission:bursar.payments.view')
            ->name('logs');
    });

    // Regime Payments — resource rewritten into explicit verb lines so each
    // can carry its own permission: middleware (no show() method on the
    // controller; that route 404s today — behaviour preserved).
    Route::get('/regimes', [RegimeController::class, 'index'])
        ->middleware('permission:bursar.regimes.view')
        ->name('regimes.index');
    Route::get('/regimes/create', [RegimeController::class, 'create'])
        ->middleware('permission:bursar.regimes.configure')
        ->name('regimes.create');
    Route::post('/regimes', [RegimeController::class, 'store'])
        ->middleware('permission:bursar.regimes.configure')
        ->name('regimes.store');
    Route::get('/regimes/{regime}', [RegimeController::class, 'show'])
        ->middleware('permission:bursar.regimes.view')
        ->name('regimes.show');
    Route::get('/regimes/{regime}/edit', [RegimeController::class, 'edit'])
        ->middleware('permission:bursar.regimes.configure')
        ->name('regimes.edit');
    Route::put('/regimes/{regime}', [RegimeController::class, 'update'])
        ->middleware('permission:bursar.regimes.configure')
        ->name('regimes.update');
    Route::delete('/regimes/{regime}', [RegimeController::class, 'destroy'])
        ->middleware('permission:bursar.regimes.configure')
        ->name('regimes.destroy');
});

// Business Committee Routes
Route::prefix('business-committee')->name('business-committee.')->middleware(['auth', 'role:business_committee'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\BusinessCommittee\DashboardController::class, 'index'])
        ->middleware('permission:business_committee.dashboard.view')
        ->name('dashboard');

    // Per-user dashboard widget configurator. Slice 8i-controller
    // added the trait gate; slice 8i-routes mirrors onto the route.
    Route::get('/dashboard-config/{user}', [BusinessCommitteeDashboardConfigController::class, 'edit'])
        ->middleware('permission:business_committee.dashboard.configure')
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [BusinessCommitteeDashboardConfigController::class, 'update'])
        ->middleware('permission:business_committee.dashboard.configure')
        ->name('dashboard-config.update');
    Route::get('/results', [\App\Http\Controllers\BusinessCommittee\ResultController::class, 'index'])
        ->middleware('permission:business_committee.results.view')
        ->name('results');
    Route::put('/results/{result}/approve', [\App\Http\Controllers\BusinessCommittee\ResultController::class, 'approve'])
        ->middleware('permission:business_committee.results.approve')
        ->name('results.approve');
    Route::put('/results/{result}/reject', [\App\Http\Controllers\BusinessCommittee\ResultController::class, 'reject'])
        ->middleware('permission:business_committee.results.approve')
        ->name('results.reject');
    Route::post('/results/bulk-approve', [\App\Http\Controllers\BusinessCommittee\ResultController::class, 'bulkApprove'])
        ->middleware('permission:business_committee.results.approve')
        ->name('results.bulkApprove');
    Route::post('/results/bulk-reject', [\App\Http\Controllers\BusinessCommittee\ResultController::class, 'bulkReject'])
        ->middleware('permission:business_committee.results.approve')
        ->name('results.bulkReject');
});

// Academic Board Routes
Route::prefix('academic-board')->name('academic-board.')->middleware(['auth', 'role:academic_board'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AcademicBoard\DashboardController::class, 'index'])
        ->middleware('permission:academic.dashboard.view')
        ->name('dashboard');

    // Per-user dashboard widget configurator. Slice 8i-controller
    // added the trait gate; slice 8i-routes mirrors onto the route.
    Route::get('/dashboard-config/{user}', [AcademicBoardDashboardConfigController::class, 'edit'])
        ->middleware('permission:academic.dashboard.configure')
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [AcademicBoardDashboardConfigController::class, 'update'])
        ->middleware('permission:academic.dashboard.configure')
        ->name('dashboard-config.update');
    Route::get('/results', [\App\Http\Controllers\AcademicBoard\ResultController::class, 'index'])
        ->middleware('permission:academic.results.view')
        ->name('results');
    Route::put('/results/{result}/approve', [\App\Http\Controllers\AcademicBoard\ResultController::class, 'approve'])
        ->middleware('permission:academic.results.board-approve')
        ->name('results.approve');
    Route::put('/results/{result}/reject', [\App\Http\Controllers\AcademicBoard\ResultController::class, 'reject'])
        ->middleware('permission:academic.results.board-approve')
        ->name('results.reject');
    Route::post('/results/bulk-approve', [\App\Http\Controllers\AcademicBoard\ResultController::class, 'bulkApprove'])
        ->middleware('permission:academic.results.board-approve')
        ->name('results.bulkApprove');
    Route::post('/results/bulk-reject', [\App\Http\Controllers\AcademicBoard\ResultController::class, 'bulkReject'])
        ->middleware('permission:academic.results.board-approve')
        ->name('results.bulkReject');
});

// Librarian Routes. Library Officer and Library Assistant (seeded by
// ERPRolesSeeder) share the same catalogue/loans screens as the
// Librarian — they just have a narrower permission set, enforced
// in code where it matters (e.g. only Librarian can delete books).
Route::prefix('librarian')->name('librarian.')->middleware(['auth', 'role:librarian,library_officer,library_assistant'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Librarian\DashboardController::class, 'index'])
        ->middleware('permission:librarian.dashboard.view')
        ->name('dashboard');

    // Per-user dashboard widget configurator. Slice 8i-controller
    // added the trait gate; slice 8i-routes mirrors onto the route.
    Route::get('/dashboard-config/{user}', [LibrarianDashboardConfigController::class, 'edit'])
        ->middleware('permission:librarian.dashboard.configure')
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [LibrarianDashboardConfigController::class, 'update'])
        ->middleware('permission:librarian.dashboard.configure')
        ->name('dashboard-config.update');
    Route::get('/books', [\App\Http\Controllers\Librarian\DashboardController::class, 'books'])
        ->middleware('permission:librarian.books.view')
        ->name('books');
    Route::get('/books/create', [\App\Http\Controllers\Librarian\DashboardController::class, 'createBook'])
        ->middleware('permission:librarian.books.create')
        ->name('books.create');
    Route::post('/books', [\App\Http\Controllers\Librarian\DashboardController::class, 'storeBook'])
        ->middleware('permission:librarian.books.create')
        ->name('books.store');
    Route::get('/loans', [\App\Http\Controllers\Librarian\DashboardController::class, 'loans'])
        ->middleware('permission:librarian.borrowing.view')
        ->name('loans');
    Route::post('/loans/issue', [\App\Http\Controllers\Librarian\DashboardController::class, 'issueBook'])
        ->middleware('permission:librarian.borrowing.issue')
        ->name('loans.issue');
    Route::post('/loans/{loan}/return', [\App\Http\Controllers\Librarian\DashboardController::class, 'returnBook'])
        ->middleware('permission:librarian.borrowing.return')
        ->name('loans.return');
});

// Profile Routes (Shared)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::put('/secret-question', [\App\Http\Controllers\ProfileController::class, 'updateSecretQuestion'])->name('profile.update-secret');
});

// Redirect based on role
Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard')->middleware('auth');

// Setup Route - Only available in local environment
Route::get('/setup', function () {
    // Only allow in local environment
    if (app()->environment('production')) {
        abort(404);
    }

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

// TEST LOGIN Route - Only available in local environment
Route::get('/test-login-creds', function () {
    // Only allow in local environment
    if (app()->environment('production')) {
        abort(404);
    }

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

// Test database connection - Only available in local environment
Route::get('/test-db', function () {
    // Only allow in local environment
    if (app()->environment('production')) {
        abort(404);
    }

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

// Test route without CSRF - REMOVE AFTER TESTING - Only available in local environment
Route::post('/login-test', function (\Illuminate\Http\Request $request) {
    // Only allow in local environment
    if (app()->environment('production')) {
        abort(404);
    }

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

    // Shared PDF-receipt download endpoint. Used by the student,
    // bursar, and applicant receipt views. Ownership is enforced
    // inside PaymentReceiptPdfController — see its userCanViewReceipt().
    Route::get('/payments/{payment}/receipt.pdf', [\App\Http\Controllers\Payment\PaymentReceiptPdfController::class, 'download'])
        ->name('payments.receipt.pdf');

    // Shared "Requery this payment" endpoint. Used by the student and
    // applicant history tables when a row is still `pending` or
    // `failed` (typically because the gateway callback never landed).
    // Ownership is enforced inside PaymentRequeryController — see its
    // userCanRequery().
    Route::post('/payments/{payment}/requery', [\App\Http\Controllers\Payment\PaymentRequeryController::class, 'requery'])
        ->name('payments.requery');
});

// ===========================================
// EXECUTIVE DASHBOARD ROUTES
// ===========================================
require __DIR__.'/executive.php';

// Simple test login page - Only available in local environment
Route::get('/test-login', function () {
    // Only allow in local environment
    if (app()->environment('production')) {
        abort(404);
    }

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

// DIRECT LOGIN - Only available in local environment
Route::post('/direct-login', function (\Illuminate\Http\Request $request) {
    // Only allow in local environment
    if (app()->environment('production')) {
        abort(404);
    }

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