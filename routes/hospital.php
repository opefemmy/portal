<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hospital\DashboardController;
use App\Http\Controllers\Hospital\ExternalPatientController;
use App\Http\Controllers\Hospital\ExternalVisitController;
use App\Http\Controllers\Hospital\ExternalPortalController;

// Public Patient Portal Routes (for outsiders)
Route::prefix('patient')->name('patient.')->group(function () {
    Route::get('/login', [ExternalPortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [ExternalPortalController::class, 'login']);
    Route::post('/logout', [ExternalPortalController::class, 'logout'])->name('logout');

    // Protected routes
    Route::middleware('patient.portal')->group(function () {
        Route::get('/dashboard', [ExternalPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/medical-history', [ExternalPortalController::class, 'medicalHistory'])->name('history');
        Route::get('/appointments', [ExternalPortalController::class, 'appointments']);
        Route::get('/payments', [ExternalPortalController::class, 'payments']);
        Route::get('/communications', [ExternalPortalController::class, 'communications']);
        Route::get('/profile', [ExternalPortalController::class, 'profile']);
        Route::post('/regenerate-code', [ExternalPortalController::class, 'regenerateCode'])->name('regenerate-code');
    });
});

// Hospital Dashboard Routes (All hospital staff)
Route::prefix('hospital')->name('hospital.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Doctor Dashboard
    Route::get('/doctor/dashboard', [DashboardController::class, 'doctorDashboard'])
        ->name('doctor.dashboard');

    // Nurse Dashboard
    Route::get('/nurse/dashboard', [DashboardController::class, 'nurseDashboard'])
        ->name('nurse.dashboard');

    // Receptionist Dashboard
    Route::get('/reception/dashboard', [DashboardController::class, 'receptionistDashboard'])
        ->name('reception.dashboard');

    // Pharmacy Dashboard
    Route::get('/pharmacy/dashboard', [DashboardController::class, 'pharmacyDashboard'])
        ->name('pharmacy.dashboard');

    // Lab Dashboard
    Route::get('/lab/dashboard', [DashboardController::class, 'labDashboard'])
        ->name('lab.dashboard');

    // External Patients Management (for outsiders)
    Route::prefix('external-patients')->name('external-patients.')->group(function () {
        Route::get('/', [ExternalPatientController::class, 'index'])->name('index');
        Route::post('/', [ExternalPatientController::class, 'store'])->name('store');
        Route::get('/{patient}', [ExternalPatientController::class, 'show'])->name('show');
        Route::put('/{patient}', [ExternalPatientController::class, 'update'])->name('update');
        Route::post('/{patient}/visit', [ExternalPatientController::class, 'createVisit'])->name('visit');
        Route::post('/{patient}/appointment', [ExternalPatientController::class, 'scheduleAppointment'])->name('appointment');
        Route::post('/{patient}/communication', [ExternalPatientController::class, 'sendCommunication'])->name('communication');
    });

    // Visit Management (external patients)
    Route::prefix('visits')->name('visits.')->group(function () {
        Route::get('/{visit}/edit', [ExternalVisitController::class, 'edit'])->name('edit');
        Route::put('/{visit}', [ExternalVisitController::class, 'update'])->name('update');
        Route::post('/{visit}/vitals', [ExternalVisitController::class, 'addVitals'])->name('vitals');
        Route::post('/{visit}/prescription', [ExternalVisitController::class, 'addPrescription'])->name('prescription');
        Route::post('/{visit}/lab', [ExternalVisitController::class, 'addLabOrder'])->name('lab');
        Route::post('/{visit}/complete', [ExternalVisitController::class, 'complete'])->name('complete');
    });

    // Quick patient lookup
    Route::post('/patient/lookup', [ExternalPatientController::class, 'lookup'])->name('patient.lookup');
});
