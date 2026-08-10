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
use App\Http\Controllers\Hospital\InventoryController;
use App\Http\Controllers\Hospital\DutyRosterController;

// Public Patient Portal Routes (for outsiders)
Route::prefix('patient')->name('patient.')->group(function () {
    Route::get('/login', [ExternalPortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [ExternalPortalController::class, 'login']);
    Route::post('/logout', [ExternalPortalController::class, 'logout'])->name('logout');
    Route::get('/logout', [ExternalPortalController::class, 'logout'])->name('logout.get');

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

    // Role-specific dashboards
    Route::get('/doctor/dashboard', [DashboardController::class, 'doctorDashboard'])
        ->name('doctor.dashboard');

    Route::get('/nurse/dashboard', [DashboardController::class, 'nurseDashboard'])
        ->name('nurse.dashboard');

    Route::get('/reception/dashboard', [DashboardController::class, 'receptionistDashboard'])
        ->name('reception.dashboard');

    Route::get('/pharmacy/dashboard', [DashboardController::class, 'pharmacyDashboard'])
        ->name('pharmacy.dashboard');

    Route::get('/lab/dashboard', [DashboardController::class, 'labDashboard'])
        ->name('lab.dashboard');

    // External Patients Management (for outsiders)
    // Receptionists manage; doctors/nurses/cmd view. hospital_admin
    // and medical_records_officer need read-only access for records
    // management and audit.
    Route::prefix('external-patients')->name('external-patients.')->middleware('role:cmd,doctor,nurse,hospital_receptionist,hospital_admin,medical_records_officer,super_admin,admin')->group(function () {
        Route::get('/', [ExternalPatientController::class, 'index'])->name('index');
        Route::post('/', [ExternalPatientController::class, 'store'])->name('store');
        Route::get('/{patient}', [ExternalPatientController::class, 'show'])->name('show');
        Route::put('/{patient}', [ExternalPatientController::class, 'update'])->name('update');
        Route::post('/{patient}/visit', [ExternalPatientController::class, 'createVisit'])->name('visit');
        Route::post('/{patient}/appointment', [ExternalPatientController::class, 'scheduleAppointment'])->name('appointment');
        Route::post('/{patient}/communication', [ExternalPatientController::class, 'sendCommunication'])->name('communication');
    });

    // Visit Management (external patients) — doctors and nurses
    Route::prefix('visits')->name('visits.')->middleware('role:cmd,doctor,nurse,super_admin,admin')->group(function () {
        Route::get('/{visit}/edit', [ExternalVisitController::class, 'edit'])->name('edit');
        Route::put('/{visit}', [ExternalVisitController::class, 'update'])->name('update');
        Route::post('/{visit}/vitals', [ExternalVisitController::class, 'addVitals'])->name('vitals');
        Route::post('/{visit}/prescription', [ExternalVisitController::class, 'addPrescription'])->name('prescription');
        Route::post('/{visit}/lab', [ExternalVisitController::class, 'addLabOrder'])->name('lab');
        Route::post('/{visit}/complete', [ExternalVisitController::class, 'complete'])->name('complete');
    });

    // Quick patient lookup
    Route::post('/patient/lookup', [ExternalPatientController::class, 'lookup'])
        ->middleware('role:cmd,doctor,nurse,hospital_receptionist,pharmacist,lab_scientist,super_admin,admin')
        ->name('patient.lookup');

    // Internal Hospital Patients (registered patients)
    Route::prefix('patients')->name('patients.')->middleware('role:cmd,doctor,nurse,hospital_receptionist,pharmacist,lab_scientist,super_admin,admin')->group(function () {
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
    Route::prefix('appointments')->name('appointments.')->middleware('role:cmd,doctor,nurse,hospital_receptionist,super_admin,admin')->group(function () {
        Route::get('/', [AppointmentController::class, 'index'])->name('index');
        Route::get('/create', [AppointmentController::class, 'create'])->name('create');
        Route::post('/', [AppointmentController::class, 'store'])->name('store');
        Route::get('/{appointment}/edit', [AppointmentController::class, 'edit'])->name('edit');
        Route::put('/{appointment}', [AppointmentController::class, 'update'])->name('update');
        Route::get('/{appointment}', [AppointmentController::class, 'show'])->name('show');
        Route::post('/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('check-in');
        Route::post('/{appointment}/start', [AppointmentController::class, 'start'])->name('start');
        Route::get('/queue', [AppointmentController::class, 'queue'])->name('queue');
    });

    // Pharmacy Routes (pharmacist / store_keeper / cmd)
    Route::prefix('pharmacy')->name('pharmacy.')->middleware('role:cmd,pharmacist,store_keeper,super_admin,admin')->group(function () {
        Route::get('/drugs', [PharmacyController::class, 'drugs'])->name('drugs');
        Route::get('/drugs/create', [PharmacyController::class, 'createDrug'])->name('drugs.create');
        Route::post('/drugs', [PharmacyController::class, 'storeDrug'])->name('drugs.store');
        Route::get('/drugs/{drug}/edit', [PharmacyController::class, 'editDrug'])->name('drugs.edit');
        Route::put('/drugs/{drug}', [PharmacyController::class, 'updateDrug'])->name('drugs.update');
        Route::delete('/drugs/{drug}', [PharmacyController::class, 'destroyDrug'])->name('drugs.destroy');
        Route::get('/prescriptions', [PharmacyController::class, 'prescriptions'])->name('prescriptions');
        Route::get('/prescriptions/{prescription}', [PharmacyController::class, 'showPrescription'])->name('prescriptions.show');
        Route::post('/prescriptions/{prescription}/dispense', [PharmacyController::class, 'dispense'])->name('prescriptions.dispense');
        Route::get('/categories', [PharmacyController::class, 'categories'])->name('categories');
        Route::get('/low-stock', [PharmacyController::class, 'lowStock'])->name('low-stock');
        Route::get('/expiring', [PharmacyController::class, 'expiring'])->name('expiring');
        Route::get('/suppliers', [PharmacyController::class, 'suppliers'])->name('suppliers');
        Route::post('/suppliers', [PharmacyController::class, 'storeSupplier'])->name('suppliers.store');

        // Inventory operations (receive / adjust / expire)
        Route::get('/receive', [InventoryController::class, 'showReceive'])->name('receive');
        Route::post('/receive', [InventoryController::class, 'receive'])->name('receive.store');
        Route::get('/adjust', [InventoryController::class, 'showAdjust'])->name('adjust');
        Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust.store');
        Route::get('/expire', [InventoryController::class, 'showExpire'])->name('expire');
        Route::post('/expire', [InventoryController::class, 'expire'])->name('expire.store');
    });

    // Laboratory Routes (lab_scientist / cmd)
    Route::prefix('lab')->name('lab.')->middleware('role:cmd,lab_scientist,super_admin,admin')->group(function () {
        Route::get('/', [LaboratoryController::class, 'index'])->name('index');
        Route::get('/requests', [LaboratoryController::class, 'index'])->name('requests');
        Route::get('/requests/{request}', [LaboratoryController::class, 'show'])->name('show');
        Route::post('/requests/{request}/collect', [LaboratoryController::class, 'collectSample'])->name('collect');
        Route::post('/requests/{request}/process', [LaboratoryController::class, 'recordResults'])->name('process');
        Route::post('/requests/{request}/complete', [LaboratoryController::class, 'startProcessing'])->name('complete');
    });

    // Consultations (doctor / cmd)
    Route::prefix('consultations')->name('consultations.')->middleware('role:cmd,doctor,super_admin,admin')->group(function () {
        Route::get('/', [ConsultationController::class, 'index'])->name('index');
        Route::get('/create', [ConsultationController::class, 'create'])->name('create');
        Route::post('/', [ConsultationController::class, 'store'])->name('store');
        Route::get('/{consultation}', [ConsultationController::class, 'show'])->name('show');
        // Doctor prescribing & lab suggestions
        Route::post('/{consultation}/prescriptions', [ConsultationController::class, 'addPrescription'])
            ->name('prescriptions.store');
        Route::post('/{consultation}/lab-requests', [ConsultationController::class, 'addLabRequest'])
            ->name('lab-requests.store');
    });

    // Clinical SOAP / progress notes (doctor / cmd)
    Route::prefix('patients')->name('patients.')->group(function () {
        Route::post('/{patient}/soap-notes', [ConsultationController::class, 'storeSoapNote'])
            ->middleware('role:cmd,doctor,super_admin,admin')
            ->name('soap.store');
        Route::post('/clinical-notes/{note}/sign', [ConsultationController::class, 'signClinicalNote'])
            ->middleware('role:cmd,doctor,super_admin,admin')
            ->name('soap.sign');
        Route::get('/{patient}/clinical-notes', [ConsultationController::class, 'clinicalNotes'])
            ->middleware('role:cmd,doctor,nurse,super_admin,admin')
            ->name('soap.index');
    });

    // Duty roster (cmd / nurse / doctor)
    Route::prefix('roster')->name('roster.')->middleware('role:cmd,doctor,nurse,hospital_receptionist,super_admin,admin,matron,ward_manager')->group(function () {
        Route::get('/', [DutyRosterController::class, 'index'])->name('index');
        Route::post('/', [DutyRosterController::class, 'store'])->name('store');
        Route::delete('/{entry}', [DutyRosterController::class, 'destroy'])->name('destroy');
    });
});