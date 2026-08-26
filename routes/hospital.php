<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hospital\DashboardController;
use App\Http\Controllers\Hospital\DashboardConfigController as HospitalDashboardConfigController;
use App\Http\Controllers\Hospital\Doctor\DashboardConfigController as HospitalDoctorDashboardConfigController;
use App\Http\Controllers\Hospital\Nurse\DashboardConfigController as HospitalNurseDashboardConfigController;
use App\Http\Controllers\Hospital\Reception\DashboardConfigController as HospitalReceptionDashboardConfigController;
use App\Http\Controllers\Hospital\Pharmacy\DashboardConfigController as HospitalPharmacyDashboardConfigController;
use App\Http\Controllers\Hospital\Lab\DashboardConfigController as HospitalLabDashboardConfigController;
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
use App\Http\Controllers\Hospital\MatronDashboardController;
use App\Http\Controllers\Hospital\Matron\DashboardConfigController as HospitalMatronDashboardConfigController;
use App\Http\Controllers\Hospital\WardController;
use App\Http\Controllers\Hospital\HospitalAdminController;
use App\Http\Controllers\Hospital\Admin\DashboardConfigController as HospitalAdminDashboardConfigController;
use App\Http\Controllers\Hospital\RecordsController;
use App\Http\Controllers\Hospital\StaffNoteController;
use App\Http\Controllers\Hospital\ReferralController;
use App\Http\Controllers\Hospital\SignOutController;

// Public Patient Portal Routes (for outsiders)
//
// These 11 routes are NOT in scope for slice 8f. They are gated by
// `patient.portal` (external-patient auth), not `auth`+`role`, so a
// Laravel `permission:` slug does not apply. Left untouched.
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
//
// Slice 8f changes:
//   1. The outer `prefix('hospital')->name('hospital.')` group gains
//      `auth` middleware. This closes the 18-route leak where any
//      authenticated user could reach the per-audience dashboards.
//   2. Every route below carries a `permission:slug` chain. Slugs
//      are copied verbatim from the controller methods (slice 8e),
//      or for the 18 dashboards (which had no controller gate) from
//      the audience-specific catalogue slug.
//
// Skipped: the 11 patient-portal routes above. Not Laravel-auth.
Route::prefix('hospital')->name('hospital.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:patients.view')
        ->name('dashboard');

    // Per-user dashboard widget configurator (root cmd/hospital_admin)
    Route::get('/dashboard-config/{user}', [HospitalDashboardConfigController::class, 'edit'])
        ->middleware('permission:patients.view')
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [HospitalDashboardConfigController::class, 'update'])
        ->middleware('permission:patients.view')
        ->name('dashboard-config.update');

    // Role-specific dashboards
    Route::get('/doctor/dashboard', [DashboardController::class, 'doctorDashboard'])
        ->middleware('permission:patients.view')
        ->name('doctor.dashboard');

    // Per-user dashboard widget configurator (doctor)
    Route::get('/doctor/dashboard-config/{user}', [HospitalDoctorDashboardConfigController::class, 'edit'])
        ->middleware('permission:patients.view')
        ->name('doctor.dashboard-config.edit');
    Route::put('/doctor/dashboard-config/{user}', [HospitalDoctorDashboardConfigController::class, 'update'])
        ->middleware('permission:patients.view')
        ->name('doctor.dashboard-config.update');

    Route::get('/nurse/dashboard', [DashboardController::class, 'nurseDashboard'])
        ->middleware('permission:wards.view')
        ->name('nurse.dashboard');

    // Per-user dashboard widget configurator (nurse)
    Route::get('/nurse/dashboard-config/{user}', [HospitalNurseDashboardConfigController::class, 'edit'])
        ->middleware('permission:wards.view')
        ->name('nurse.dashboard-config.edit');
    Route::put('/nurse/dashboard-config/{user}', [HospitalNurseDashboardConfigController::class, 'update'])
        ->middleware('permission:wards.view')
        ->name('nurse.dashboard-config.update');

    Route::get('/reception/dashboard', [DashboardController::class, 'receptionistDashboard'])
        ->middleware('permission:patients.view')
        ->name('reception.dashboard');

    // Per-user dashboard widget configurator (reception)
    Route::get('/reception/dashboard-config/{user}', [HospitalReceptionDashboardConfigController::class, 'edit'])
        ->middleware('permission:patients.view')
        ->name('reception.dashboard-config.edit');
    Route::put('/reception/dashboard-config/{user}', [HospitalReceptionDashboardConfigController::class, 'update'])
        ->middleware('permission:patients.view')
        ->name('reception.dashboard-config.update');

    Route::get('/pharmacy/dashboard', [DashboardController::class, 'pharmacyDashboard'])
        ->middleware('permission:pharmacy.view')
        ->name('pharmacy.dashboard');

    // Per-user dashboard widget configurator (pharmacy)
    Route::get('/pharmacy/dashboard-config/{user}', [HospitalPharmacyDashboardConfigController::class, 'edit'])
        ->middleware('permission:pharmacy.view')
        ->name('pharmacy.dashboard-config.edit');
    Route::put('/pharmacy/dashboard-config/{user}', [HospitalPharmacyDashboardConfigController::class, 'update'])
        ->middleware('permission:pharmacy.view')
        ->name('pharmacy.dashboard-config.update');

    Route::get('/lab/dashboard', [DashboardController::class, 'labDashboard'])
        ->middleware('permission:lab.view')
        ->name('lab.dashboard');

    // Per-user dashboard widget configurator (lab)
    Route::get('/lab/dashboard-config/{user}', [HospitalLabDashboardConfigController::class, 'edit'])
        ->middleware('permission:lab.view')
        ->name('lab.dashboard-config.edit');
    Route::put('/lab/dashboard-config/{user}', [HospitalLabDashboardConfigController::class, 'update'])
        ->middleware('permission:lab.view')
        ->name('lab.dashboard-config.update');

    // External Patients Management (for outsiders)
    // Receptionists manage; doctors/nurses/cmd view. hospital_admin
    // and medical_records_officer need read-only access for records
    // management and audit.
    Route::prefix('external-patients')->name('external-patients.')->middleware(['role:cmd,doctor,nurse,hospital_receptionist,hospital_admin,medical_records_officer,super_admin,admin'])->group(function () {
        Route::get('/', [ExternalPatientController::class, 'index'])
            ->middleware('permission:external-patients.view')
            ->name('index');
        Route::post('/', [ExternalPatientController::class, 'store'])
            ->middleware('permission:external-patients.create')
            ->name('store');
        Route::get('/{patient}', [ExternalPatientController::class, 'show'])
            ->middleware('permission:external-patients.view')
            ->name('show');
        Route::put('/{patient}', [ExternalPatientController::class, 'update'])
            ->middleware('permission:external-patients.edit')
            ->name('update');
        Route::post('/{patient}/visit', [ExternalPatientController::class, 'createVisit'])
            ->middleware('permission:external-patients.visit')
            ->name('visit');
        Route::post('/{patient}/appointment', [ExternalPatientController::class, 'scheduleAppointment'])
            ->middleware('permission:external-patients.appointment')
            ->name('appointment');
        Route::post('/{patient}/communication', [ExternalPatientController::class, 'sendCommunication'])
            ->middleware('permission:external-patients.view')
            ->name('communication');
    });

    // Visit Management (external patients) — doctors and nurses
    Route::prefix('visits')->name('visits.')->middleware(['role:cmd,doctor,nurse,super_admin,admin'])->group(function () {
        Route::get('/{visit}/edit', [ExternalVisitController::class, 'edit'])
            ->middleware('permission:patients.view')
            ->name('edit');
        Route::put('/{visit}', [ExternalVisitController::class, 'update'])
            ->middleware('permission:patients.edit')
            ->name('update');
        Route::post('/{visit}/vitals', [ExternalVisitController::class, 'addVitals'])
            ->middleware('permission:visits.vitals')
            ->name('vitals');
        Route::post('/{visit}/prescription', [ExternalVisitController::class, 'addPrescription'])
            ->middleware('permission:prescriptions.create')
            ->name('prescription');
        Route::post('/{visit}/lab', [ExternalVisitController::class, 'addLabOrder'])
            ->middleware('permission:lab.create')
            ->name('lab');
        Route::post('/{visit}/complete', [ExternalVisitController::class, 'complete'])
            ->middleware('permission:patients.edit')
            ->name('complete');
    });

    // Quick patient lookup
    Route::post('/patient/lookup', [ExternalPatientController::class, 'lookup'])
        ->middleware(['role:cmd,doctor,nurse,hospital_receptionist,pharmacist,lab_scientist,super_admin,admin', 'permission:external-patients.view'])
        ->name('patient.lookup');

    // Internal Hospital Patients (registered patients)
    // medical_records_officer is included with read-only access — they
    // navigate patients from /hospital/records/{patient} but are still
    // blocked from clinical writes by EnforcesPermission.
    Route::prefix('patients')->name('patients.')->middleware(['role:cmd,doctor,nurse,hospital_receptionist,pharmacist,lab_scientist,medical_records_officer,super_admin,admin'])->group(function () {
        Route::get('/', [PatientController::class, 'index'])
            ->middleware('permission:patients.view')
            ->name('index');
        Route::get('/create', [PatientController::class, 'create'])
            ->middleware('permission:patients.create')
            ->name('create');
        Route::post('/', [PatientController::class, 'store'])
            ->middleware('permission:patients.create')
            ->name('store');
        Route::get('/{patient}', [PatientController::class, 'show'])
            ->middleware('permission:patients.view')
            ->name('show');
        Route::get('/{patient}/edit', [PatientController::class, 'edit'])
            ->middleware('permission:patients.edit')
            ->name('edit');
        Route::put('/{patient}', [PatientController::class, 'update'])
            ->middleware('permission:patients.edit')
            ->name('update');
        Route::get('/search', [PatientController::class, 'search'])
            ->middleware('permission:patients.search')
            ->name('search');
        Route::get('/{patient}/timeline', [PatientController::class, 'timeline'])
            ->middleware('permission:patients.view')
            ->name('timeline');
    });

    // Hospital Appointments
    //
    // Patient-flow chain-of-custody: book → records-officer certifies
    // (appointments.certify) → records-officer assigns a doctor on
    // duty (appointments.assign-doctor) → nurse takes vitals
    // (appointments.vitals) → doctor starts the consultation
    // (appointments.start).
    //
    // medical_records_officer is added to the role middleware so the
    // records-officer desk can see + act on the queue; nurse is
    // already present (slice 8f).
    Route::prefix('appointments')->name('appointments.')->middleware(['role:cmd,doctor,nurse,hospital_receptionist,medical_records_officer,super_admin,admin'])->group(function () {
        Route::get('/', [AppointmentController::class, 'index'])
            ->middleware('permission:appointments.view')
            ->name('index');
        Route::get('/create', [AppointmentController::class, 'create'])
            ->middleware('permission:appointments.create')
            ->name('create');
        Route::post('/', [AppointmentController::class, 'store'])
            ->middleware('permission:appointments.create')
            ->name('store');
        Route::get('/{appointment}/edit', [AppointmentController::class, 'edit'])
            ->middleware('permission:appointments.update')
            ->name('edit');
        Route::put('/{appointment}', [AppointmentController::class, 'update'])
            ->middleware('permission:appointments.update')
            ->name('update');
        Route::get('/{appointment}', [AppointmentController::class, 'show'])
            ->middleware('permission:appointments.view')
            ->name('show');
        Route::post('/{appointment}/check-in', [AppointmentController::class, 'checkIn'])
            ->middleware('permission:appointments.check-in')
            ->name('check-in');
        Route::post('/{appointment}/start', [AppointmentController::class, 'start'])
            ->middleware('permission:appointments.start')
            ->name('start');
        // Records-officer desk endpoints.
        Route::post('/{appointment}/certify', [AppointmentController::class, 'certify'])
            ->middleware('permission:appointments.certify')
            ->name('certify');
        Route::post('/{appointment}/assign-doctor', [AppointmentController::class, 'assignDoctor'])
            ->middleware('permission:appointments.assign-doctor')
            ->name('assign-doctor');
        // Nurse vitals stamping.
        Route::post('/{appointment}/vitals', [AppointmentController::class, 'recordVitals'])
            ->middleware('permission:appointments.vitals')
            ->name('vitals');
        Route::get('/queue', [AppointmentController::class, 'queue'])
            ->middleware('permission:appointments.view')
            ->name('queue');
    });

    // Pharmacy Routes (pharmacist / store_keeper / cmd)
    Route::prefix('pharmacy')->name('pharmacy.')->middleware(['role:cmd,pharmacist,store_keeper,super_admin,admin'])->group(function () {
        Route::get('/drugs', [PharmacyController::class, 'drugs'])
            ->middleware('permission:pharmacy.drugs')
            ->name('drugs');
        Route::get('/drugs/create', [PharmacyController::class, 'createDrug'])
            ->middleware('permission:pharmacy.drugs')
            ->name('drugs.create');
        Route::post('/drugs', [PharmacyController::class, 'storeDrug'])
            ->middleware('permission:pharmacy.drugs')
            ->name('drugs.store');
        Route::get('/drugs/{drug}/edit', [PharmacyController::class, 'editDrug'])
            ->middleware('permission:pharmacy.drugs')
            ->name('drugs.edit');
        Route::put('/drugs/{drug}', [PharmacyController::class, 'updateDrug'])
            ->middleware('permission:pharmacy.drugs')
            ->name('drugs.update');
        Route::delete('/drugs/{drug}', [PharmacyController::class, 'destroyDrug'])
            ->middleware('permission:pharmacy.drugs')
            ->name('drugs.destroy');
        Route::get('/prescriptions', [PharmacyController::class, 'prescriptions'])
            ->middleware('permission:prescriptions.view')
            ->name('prescriptions');
        Route::get('/prescriptions/{prescription}', [PharmacyController::class, 'showPrescription'])
            ->middleware('permission:prescriptions.view')
            ->name('prescriptions.show');
        Route::post('/prescriptions/{prescription}/dispense', [PharmacyController::class, 'dispense'])
            ->middleware('permission:pharmacy.dispense')
            ->name('prescriptions.dispense');
        Route::get('/categories', [PharmacyController::class, 'categories'])
            ->middleware('permission:pharmacy.drugs')
            ->name('categories');
        Route::get('/low-stock', [PharmacyController::class, 'lowStock'])
            ->middleware('permission:pharmacy.drugs')
            ->name('low-stock');
        Route::get('/expiring', [PharmacyController::class, 'expiring'])
            ->middleware('permission:pharmacy.drugs')
            ->name('expiring');
        Route::get('/suppliers', [PharmacyController::class, 'suppliers'])
            ->middleware('permission:pharmacy.receive')
            ->name('suppliers');
        Route::post('/suppliers', [PharmacyController::class, 'storeSupplier'])
            ->middleware('permission:pharmacy.receive')
            ->name('suppliers.store');

        // Inventory operations (receive / adjust / expire)
        Route::get('/receive', [InventoryController::class, 'showReceive'])
            ->middleware('permission:pharmacy.receive')
            ->name('receive');
        Route::post('/receive', [InventoryController::class, 'receive'])
            ->middleware('permission:pharmacy.receive')
            ->name('receive.store');
        Route::get('/adjust', [InventoryController::class, 'showAdjust'])
            ->middleware('permission:pharmacy.adjust')
            ->name('adjust');
        Route::post('/adjust', [InventoryController::class, 'adjust'])
            ->middleware('permission:pharmacy.adjust')
            ->name('adjust.store');
        Route::get('/expire', [InventoryController::class, 'showExpire'])
            ->middleware('permission:pharmacy.expire')
            ->name('expire');
        Route::post('/expire', [InventoryController::class, 'expire'])
            ->middleware('permission:pharmacy.expire')
            ->name('expire.store');
    });

    // Laboratory Routes (lab_scientist / cmd)
    Route::prefix('lab')->name('lab.')->middleware(['role:cmd,lab_scientist,super_admin,admin'])->group(function () {
        Route::get('/', [LaboratoryController::class, 'index'])
            ->middleware('permission:lab.view')
            ->name('index');
        Route::get('/requests', [LaboratoryController::class, 'index'])
            ->middleware('permission:lab.view')
            ->name('requests');
        Route::get('/requests/{request}', [LaboratoryController::class, 'show'])
            ->middleware('permission:lab.view')
            ->name('show');
        Route::post('/requests/{request}/collect', [LaboratoryController::class, 'collectSample'])
            ->middleware('permission:lab.collect')
            ->name('collect');
        Route::post('/requests/{request}/process', [LaboratoryController::class, 'recordResults'])
            ->middleware('permission:lab.process')
            ->name('process');
        Route::post('/requests/{request}/complete', [LaboratoryController::class, 'startProcessing'])
            ->middleware('permission:lab.process')
            ->name('complete');
    });

    // Consultations (doctor / cmd)
    Route::prefix('consultations')->name('consultations.')->middleware(['role:cmd,doctor,super_admin,admin'])->group(function () {
        Route::get('/', [ConsultationController::class, 'index'])
            ->middleware('permission:consultations.view')
            ->name('index');
        Route::get('/create', [ConsultationController::class, 'create'])
            ->middleware('permission:consultations.create')
            ->name('create');
        Route::post('/', [ConsultationController::class, 'store'])
            ->middleware('permission:consultations.create')
            ->name('store');
        Route::get('/{consultation}', [ConsultationController::class, 'show'])
            ->middleware('permission:consultations.view')
            ->name('show');
        // Doctor prescribing & lab suggestions
        Route::post('/{consultation}/prescriptions', [ConsultationController::class, 'addPrescription'])
            ->middleware('permission:prescriptions.create')
            ->name('prescriptions.store');
        Route::post('/{consultation}/lab-requests', [ConsultationController::class, 'addLabRequest'])
            ->middleware('permission:lab.create')
            ->name('lab-requests.store');
    });

    // Clinical SOAP / progress notes (doctor / cmd)
    Route::prefix('patients')->name('patients.')->group(function () {
        Route::post('/{patient}/soap-notes', [ConsultationController::class, 'storeSoapNote'])
            ->middleware(['role:cmd,doctor,super_admin,admin', 'permission:consultations.soap'])
            ->name('soap.store');
        Route::post('/clinical-notes/{note}/sign', [ConsultationController::class, 'signClinicalNote'])
            ->middleware(['role:cmd,doctor,super_admin,admin', 'permission:consultations.sign'])
            ->name('soap.sign');
        Route::get('/{patient}/clinical-notes', [ConsultationController::class, 'clinicalNotes'])
            ->middleware(['role:cmd,doctor,nurse,super_admin,admin', 'permission:consultations.view'])
            ->name('soap.index');
    });

    // Duty roster (cmd / nurse / doctor)
    Route::prefix('roster')->name('roster.')->middleware(['role:cmd,doctor,nurse,hospital_receptionist,super_admin,admin,matron,ward_manager'])->group(function () {
        Route::get('/', [DutyRosterController::class, 'index'])
            ->middleware('permission:patients.view')
            ->name('index');
        Route::post('/', [DutyRosterController::class, 'store'])
            ->middleware('permission:patients.view')
            ->name('store');
        Route::delete('/{entry}', [DutyRosterController::class, 'destroy'])
            ->middleware('permission:patients.view')
            ->name('destroy');
    });

    // === Matron (senior nurse, ward operations oversight) ===
    Route::prefix('matron')->name('matron.')->middleware(['role:matron,cmd,super_admin,admin'])->group(function () {
        Route::get('/dashboard', [MatronDashboardController::class, 'index'])
            ->middleware('permission:wards.view')
            ->name('dashboard');

        // Per-user dashboard widget configurator
        Route::get('/dashboard-config/{user}', [HospitalMatronDashboardConfigController::class, 'edit'])
            ->middleware('permission:wards.view')
            ->name('dashboard-config.edit');
        Route::put('/dashboard-config/{user}', [HospitalMatronDashboardConfigController::class, 'update'])
            ->middleware('permission:wards.view')
            ->name('dashboard-config.update');
        Route::get('/rounds',    [MatronDashboardController::class, 'rounds'])
            ->middleware('permission:patients.view')
            ->name('rounds');
        Route::get('/staff',     [MatronDashboardController::class, 'staffLoad'])
            ->middleware('permission:monitoring.notes')
            ->name('staff');
    });

    // === Ward Manager (beds, occupancy, ward CRUD) ===
    Route::prefix('wards')->name('wards.')->middleware(['role:matron,ward_manager,cmd,super_admin,admin'])->group(function () {
        Route::get('/',                 [WardController::class, 'index'])
            ->middleware('permission:wards.view')
            ->name('index');
        Route::get('/create',           [WardController::class, 'create'])
            ->middleware('permission:wards.manage')
            ->name('create');
        Route::post('/',                [WardController::class, 'store'])
            ->middleware('permission:wards.manage')
            ->name('store');
        Route::get('/{ward}/edit',      [WardController::class, 'edit'])
            ->middleware('permission:wards.manage')
            ->name('edit');
        Route::put('/{ward}',           [WardController::class, 'update'])
            ->middleware('permission:wards.manage')
            ->name('update');
        Route::get('/occupancy',        [WardController::class, 'occupancyReport'])
            ->middleware('permission:wards.view')
            ->name('occupancy');
        Route::get('/{ward}/beds',      [WardController::class, 'beds'])
            ->middleware('permission:wards.view')
            ->name('beds');
        Route::post('/{ward}/beds',     [WardController::class, 'assignBed'])
            ->middleware('permission:beds.assign')
            ->name('beds.assign');
        Route::post('/beds/{bed}/discharge', [WardController::class, 'dischargeBed'])
            ->middleware('permission:beds.manage')
            ->name('beds.discharge');
    });

    // === Hospital Admin (cross-cutting dashboard + staff + revenue + inventory + attendance) ===
    Route::prefix('admin')->name('admin.')->middleware(['role:hospital_admin,cmd,super_admin,admin'])->group(function () {
        Route::get('/dashboard', [HospitalAdminController::class, 'index'])
            ->middleware('permission:reports.daily-revenue')
            ->name('dashboard');

        // Per-user dashboard widget configurator
        Route::get('/dashboard-config/{user}', [HospitalAdminDashboardConfigController::class, 'edit'])
            ->middleware('permission:reports.daily-revenue')
            ->name('dashboard-config.edit');
        Route::put('/dashboard-config/{user}', [HospitalAdminDashboardConfigController::class, 'update'])
            ->middleware('permission:reports.daily-revenue')
            ->name('dashboard-config.update');
        Route::get('/staff',     [HospitalAdminController::class, 'staff'])
            ->middleware('permission:staff.view')
            ->name('staff');
        Route::post('/staff/{staff}/toggle', [HospitalAdminController::class, 'toggleAvailability'])
            ->middleware('permission:staff.edit')
            ->name('staff.toggle');
        Route::get('/revenue',   [HospitalAdminController::class, 'revenue'])
            ->middleware('permission:reports.daily-revenue')
            ->name('revenue');
        Route::get('/inventory', [HospitalAdminController::class, 'inventory'])
            ->middleware('permission:inventory.view')
            ->name('inventory');
        Route::get('/attendance',[HospitalAdminController::class, 'attendance'])
            ->middleware('permission:attendance.view')
            ->name('attendance');
    });

    // === Medical Records (archive, transfer, request queue, audit) ===
    Route::prefix('records')->name('records.')->middleware(['role:medical_records_officer,cmd,super_admin,admin,hospital_admin'])->group(function () {
        Route::get('/',                  [RecordsController::class, 'index'])
            ->middleware('permission:records.view')
            ->name('index');
        Route::get('/search',            [RecordsController::class, 'search'])
            ->middleware('permission:records.view')
            ->name('search');
        Route::get('/{patient}',         [RecordsController::class, 'show'])
            ->middleware('permission:records.view')
            ->name('show');
        Route::post('/{patient}/archive',[RecordsController::class, 'archive'])
            ->middleware('permission:records.archive')
            ->name('archive');
        Route::post('/{patient}/unarchive',[RecordsController::class, 'unarchive'])
            ->middleware('permission:records.archive')
            ->name('unarchive');
        Route::post('/{patient}/transfer',[RecordsController::class, 'transfer'])
            ->middleware('permission:records.transfer')
            ->name('transfer');
        Route::get('/audit',             [RecordsController::class, 'auditLog'])
            ->middleware('permission:audit.view')
            ->name('audit');
        Route::get('/requests',          [RecordsController::class, 'requests'])
            ->middleware('permission:records.request')
            ->name('requests');
        Route::post('/requests/{recordRequest}/fulfill', [RecordsController::class, 'fulfillRequest'])
            ->middleware('permission:records.request')
            ->name('requests.fulfill');
        Route::post('/requests/{recordRequest}/reject',  [RecordsController::class, 'rejectRequest'])
            ->middleware('permission:records.request')
            ->name('requests.reject');
    });

    // === Staff notes (handover / instruction / commentary / alert) ===
    // Any clinical or administrative staff can post a note on a
    // patient file. The note is timestamped, attributed, and shows up
    // on the patient timeline for downstream staff to read.
    Route::prefix('patients/{patient}/notes')->name('patients.notes.')->group(function () {
        Route::post('/', [StaffNoteController::class, 'store'])
            ->middleware('permission:notes.create')
            ->name('store');
        Route::post('/{note}/pin', [StaffNoteController::class, 'togglePin'])
            ->middleware('permission:notes.pin')
            ->name('pin');
        Route::delete('/{note}', [StaffNoteController::class, 'destroy'])
            ->middleware('permission:notes.delete')
            ->name('destroy');
    });

    // === Doctor referrals (send to lab / pharmacy / x-ray / nurse) ===
    // A doctor, after seeing a patient, can refer them onward: order
    // a lab test, prescribe a drug, send them to x-ray, send them
    // back to a nurse for a procedure, or set a follow-up date.
    // Each referral drops a staff note on the chart so the receiving
    // team sees it without leaving their normal queue.
    Route::prefix('patients/{patient}/referrals')->name('patients.referrals.')->group(function () {
        Route::post('/lab',       [ReferralController::class, 'toLab'])
            ->middleware('permission:referrals.send.lab')
            ->name('lab');
        Route::post('/pharmacy',  [ReferralController::class, 'toPharmacy'])
            ->middleware('permission:referrals.send.pharmacy')
            ->name('pharmacy');
        Route::post('/radiology', [ReferralController::class, 'toRadiology'])
            ->middleware('permission:referrals.send.radiology')
            ->name('radiology');
        Route::post('/nurse',     [ReferralController::class, 'toNurse'])
            ->middleware('permission:referrals.send.nurse')
            ->name('nurse');
        Route::post('/follow-up', [ReferralController::class, 'followUp'])
            ->middleware('permission:appointments.create')
            ->name('follow-up');
    });

    // === End-of-day sign-out (records officer closes the day's visit) ===
    // The records officer signs the patient out at the end of the
    // day: locks the chart from further edits, writes a discharge
    // summary, marks the appointment completed.
    Route::prefix('appointments/{appointment}')->name('appointments.')->group(function () {
        Route::post('/sign-out', [SignOutController::class, 'store'])
            ->middleware('permission:signout.complete')
            ->name('sign-out');
    });
});
