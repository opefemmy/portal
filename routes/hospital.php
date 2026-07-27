<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hospital\DashboardController;
use App\Http\Controllers\Hospital\ExternalPatientController;
use App\Http\Controllers\Hospital\ExternalVisitController;
use App\Http\Controllers\Hospital\ExternalPortalController;
use App\Http\Controllers\Hospital\PatientController;
use App\Http\Controllers\Hospital\AppointmentController;
use App\Http\Controllers\Hospital\PharmacyController;
use App\Http\Controllers\Hospital\LaboratoryController;
use App\Http\Controllers\Hospital\ConsultationController;

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

    // Internal Hospital Patients (registered patients)
    Route::prefix('patients')->name('patients.')->group(function () {
        Route::get('/', [PatientController::class, 'index'])->name('index');
        Route::get('/create', [PatientController::class, 'create'])->name('create');
        Route::post('/', [PatientController::class, 'store'])->name('store');
        Route::get('/{patient}', [PatientController::class, 'show'])->name('show');
        Route::get('/{patient}/edit', [PatientController::class, 'edit'])->name('edit');
        Route::put('/{patient}', [PatientController::class, 'update'])->name('update');
        Route::get('/search', [PatientController::class, 'search'])->name('search');
        Route::get('/{patient}/timeline', [PatientController::class, 'timeline'])->name('timeline');
    });

    // Hospital Appointments
    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', [AppointmentController::class, 'index'])->name('index');
        Route::get('/create', [AppointmentController::class, 'create'])->name('create');
        Route::post('/', [AppointmentController::class, 'store'])->name('store');
        Route::get('/{appointment}', [AppointmentController::class, 'show'])->name('show');
        Route::put('/{appointment}', [AppointmentController::class, 'update'])->name('update');
        Route::post('/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('check-in');
        Route::post('/{appointment}/start', [AppointmentController::class, 'start'])->name('start');
        Route::get('/queue', [AppointmentController::class, 'queue'])->name('queue');
    });

    // Pharmacy Routes
    Route::prefix('pharmacy')->name('pharmacy.')->group(function () {
        Route::get('/drugs', [PharmacyController::class, 'index'])->name('drugs');
        Route::get('/drugs/create', [PharmacyController::class, 'createDrug'])->name('drugs.create');
        Route::post('/drugs', [PharmacyController::class, 'storeDrug'])->name('drugs.store');
        Route::get('/drugs/{drug}/edit', [PharmacyController::class, 'editDrug'])->name('drugs.edit');
        Route::put('/drugs/{drug}', [PharmacyController::class, 'updateDrug'])->name('drugs.update');
        Route::delete('/drugs/{drug}', [PharmacyController::class, 'destroyDrug'])->name('drugs.destroy');
        Route::get('/prescriptions', [PharmacyController::class, 'prescriptions'])->name('prescriptions');
        Route::get('/prescriptions/{prescription}', [PharmacyController::class, 'showPrescription'])->name('prescriptions.show');
        Route::post('/prescriptions/{prescription}/dispense', [PharmacyController::class, 'dispensePrescription'])->name('prescriptions.dispense');
        Route::get('/categories', [PharmacyController::class, 'categories'])->name('categories');
        Route::get('/low-stock', [PharmacyController::class, 'lowStock'])->name('low-stock');
        Route::get('/expiring', [PharmacyController::class, 'expiring'])->name('expiring');
        Route::get('/suppliers', [PharmacyController::class, 'suppliers'])->name('suppliers');
        Route::post('/suppliers', [PharmacyController::class, 'storeSupplier'])->name('suppliers.store');
    });

    // Laboratory Routes
    Route::prefix('lab')->name('lab.')->group(function () {
        Route::get('/', [LaboratoryController::class, 'index'])->name('index');
        Route::get('/requests', [LaboratoryController::class, 'requests'])->name('requests');
        Route::get('/requests/{request}', [LaboratoryController::class, 'showRequest'])->name('show');
        Route::post('/requests/{request}/collect', [LaboratoryController::class, 'collectSample'])->name('collect');
        Route::post('/requests/{request}/process', [LaboratoryController::class, 'processResult'])->name('process');
        Route::post('/requests/{request}/complete', [LaboratoryController::class, 'complete'])->name('complete');
    });

    // Consultations
    Route::prefix('consultations')->name('consultations.')->group(function () {
        Route::get('/', [ConsultationController::class, 'index'])->name('index');
        Route::get('/create', [ConsultationController::class, 'create'])->name('create');
        Route::post('/', [ConsultationController::class, 'store'])->name('store');
    });
});
