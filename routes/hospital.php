<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hospital\DashboardController;
use App\Http\Controllers\Hospital\PatientController;
use App\Http\Controllers\Hospital\AppointmentController;
use App\Http\Controllers\Hospital\ConsultationController;
use App\Http\Controllers\Hospital\PharmacyController;
use App\Http\Controllers\Hospital\LaboratoryController;

// Hospital Dashboard Routes (All hospital staff)
Route::prefix('hospital')->name('hospital.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Doctor Dashboard
    Route::get('/doctor/dashboard', [DashboardController::class, 'doctorDashboard'])
        ->middleware('role:doctor,cmd')
        ->name('doctor.dashboard');

    // Nurse Dashboard
    Route::get('/nurse/dashboard', [DashboardController::class, 'nurseDashboard'])
        ->middleware('role:nurse')
        ->name('nurse.dashboard');

    // Receptionist Dashboard
    Route::get('/reception/dashboard', [DashboardController::class, 'receptionistDashboard'])
        ->middleware('role:hospital_receptionist')
        ->name('reception.dashboard');

    // Pharmacy Dashboard
    Route::get('/pharmacy/dashboard', [DashboardController::class, 'pharmacyDashboard'])
        ->middleware('role:pharmacist')
        ->name('pharmacy.dashboard');

    // Lab Dashboard
    Route::get('/lab/dashboard', [DashboardController::class, 'labDashboard'])
        ->middleware('role:lab_scientist')
        ->name('lab.dashboard');
});

// Patient Management Routes
Route::prefix('hospital/patients')->name('hospital.patients.')->group(function () {
    Route::get('/', [PatientController::class, 'index'])->name('index');
    Route::get('/create', [PatientController::class, 'create'])->name('create');
    Route::post('/', [PatientController::class, 'store'])->name('store');
    Route::get('/{patient}', [PatientController::class, 'show'])->name('show');
    Route::get('/{patient}/edit', [PatientController::class, 'edit'])->name('edit');
    Route::put('/{patient}', [PatientController::class, 'update'])->name('update');
    Route::get('/search', [PatientController::class, 'search'])->name('search');
    Route::get('/{patient}/timeline', [ConsultationController::class, 'timeline'])->name('timeline');
    Route::get('/{patient}/lab-results', [LaboratoryController::class, 'myResults'])->name('lab-results');
});

// Appointment Routes
Route::prefix('hospital/appointments')->name('hospital.appointments.')->group(function () {
    Route::get('/', [AppointmentController::class, 'index'])->name('index');
    Route::get('/queue', [AppointmentController::class, 'queue'])->name('queue');
    Route::get('/create', [AppointmentController::class, 'create'])->name('create');
    Route::post('/', [AppointmentController::class, 'store'])->name('store');
    Route::get('/{appointment}', [AppointmentController::class, 'show'])->name('show');
    Route::post('/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('check-in');
    Route::post('/{appointment}/start', [AppointmentController::class, 'startConsultation'])->name('start');
    Route::post('/{appointment}/complete', [AppointmentController::class, 'complete'])->name('complete');
    Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('cancel');
    Route::get('/available-slots', [AppointmentController::class, 'availableSlots'])->name('slots');
});

// Consultation Routes
Route::prefix('hospital/consultations')->name('hospital.consultations.')->group(function () {
    Route::get('/create', [ConsultationController::class, 'create'])->name('create');
    Route::post('/', [ConsultationController::class, 'store'])->name('store');
    Route::get('/{consultation}', [ConsultationController::class, 'show'])->name('show');
    Route::post('/vitals', [ConsultationController::class, 'recordVitals'])->name('vitals');
});

// Pharmacy Routes
Route::prefix('hospital/pharmacy')->name('hospital.pharmacy.')->group(function () {
    Route::get('/drugs', [PharmacyController::class, 'drugs'])->name('drugs');
    Route::get('/drugs/create', [PharmacyController::class, 'createDrug'])->name('drugs.create');
    Route::post('/drugs', [PharmacyController::class, 'storeDrug'])->name('drugs.store');
    Route::get('/prescriptions', [PharmacyController::class, 'prescriptions'])->name('prescriptions');
    Route::get('/prescriptions/{prescription}', [PharmacyController::class, 'showPrescription'])->name('prescriptions.show');
    Route::post('/prescriptions/{prescription}/dispense', [PharmacyController::class, 'dispense'])->name('dispense');
    Route::get('/low-stock', [PharmacyController::class, 'lowStock'])->name('low-stock');
    Route::get('/expiring', [PharmacyController::class, 'expiring'])->name('expiring');
    Route::get('/categories', [PharmacyController::class, 'categories'])->name('categories');
    Route::post('/categories', [PharmacyController::class, 'storeCategory'])->name('categories.store');
    Route::get('/suppliers', [PharmacyController::class, 'suppliers'])->name('suppliers');
    Route::post('/suppliers', [PharmacyController::class, 'storeSupplier'])->name('suppliers.store');
});

// Laboratory Routes
Route::prefix('hospital/lab')->name('hospital.lab.')->group(function () {
    Route::get('/', [LaboratoryController::class, 'index'])->name('index');
    Route::get('/{labRequest}', [LaboratoryController::class, 'show'])->name('show');
    Route::post('/{labRequest}/collect', [LaboratoryController::class, 'collectSample'])->name('collect');
    Route::post('/{labRequest}/process', [LaboratoryController::class, 'startProcessing'])->name('process');
    Route::post('/{labRequest}/results', [LaboratoryController::class, 'recordResults'])->name('results');
    Route::post('/{labRequest}/cancel', [LaboratoryController::class, 'cancel'])->name('cancel');
});