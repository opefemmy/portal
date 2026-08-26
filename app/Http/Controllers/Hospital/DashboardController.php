<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalStaff;
use App\Models\Hospital\HospitalAdmission;
use App\Models\Hospital\HospitalPrescription;
use App\Models\Hospital\HospitalDrug;
use App\Models\Hospital\HospitalLabRequest;
use App\Services\Dashboard\DashboardResolver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Hospital admin dashboard.
     *
     * Stat tiles are widget-rendered (cmd / hospital_admin
     * audience). The Today's Appointments + Recent Patients tables
     * stay in chrome — they use the controller's eager-loaded
     * collections that the widget closures would otherwise have to
     * recreate.
     */
    public function index(Request $request)
    {
        $widgets = DashboardResolver::widgetsForUser($request->user());

        // Today's appointments
        $todayAppointments = HospitalAppointment::with(['patient', 'staff'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_date')
            ->limit(10)
            ->get();

        // Recent patients
        $recentPatients = HospitalPatient::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('hospital.dashboard', compact('widgets', 'todayAppointments', 'recentPatients'));
    }

    /**
     * Doctor dashboard.
     */
    public function doctorDashboard(Request $request)
    {
        $hospitalStaff = $request->user()->hospitalStaff ?? null;

        if (!$hospitalStaff) {
            return redirect()->back()->with('error', 'Hospital staff profile not found');
        }

        $doctorId = $hospitalStaff->id;

        $todayAppointments = HospitalAppointment::with('patient')
            ->where('staff_id', $doctorId)
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('appointment_date')
            ->get();

        $pendingConsultations = HospitalAppointment::with('patient')
            ->where('staff_id', $doctorId)
            ->where('status', 'in_progress')
            ->get();

        $widgets = DashboardResolver::widgetsForUser($request->user());

        return view('hospital.doctor-dashboard', compact('widgets', 'todayAppointments', 'pendingConsultations'));
    }

    /**
     * Nurse dashboard.
     *
     * The "Patients Waiting for Vitals" tile is the nurse's primary
     * queue: today's appointments that have been records-certified
     * (chart on file) but have not yet had vitals taken.
     */
    public function nurseDashboard(Request $request)
    {
        // Records-certified today, no vitals yet → nurse's queue.
        $todayAppointments = HospitalAppointment::with(['patient', 'staff', 'doctor'])
            ->whereDate('appointment_date', today())
            ->whereNotNull('certified_at')
            ->whereNull('vitals_recorded_at')
            ->whereIn('status', ['records_certified', 'checked_in', 'scheduled'])
            ->orderBy('appointment_time')
            ->get();

        $admittedPatients = HospitalAdmission::with(['patient', 'bed.ward', 'doctor'])
            ->where('status', 'admitted')
            ->latest('admission_date')
            ->limit(10)
            ->get();

        $widgets = DashboardResolver::widgetsForUser($request->user());

        return view('hospital.nurse-dashboard', compact('widgets', 'todayAppointments', 'admittedPatients'));
    }

    /**
     * Receptionist dashboard.
     */
    public function receptionistDashboard(Request $request)
    {
        $todayQueue = HospitalAppointment::with(['patient', 'staff'])
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['scheduled'])
            ->orderBy('appointment_date')
            ->get();

        $widgets = DashboardResolver::widgetsForUser($request->user());

        return view('hospital.receptionist-dashboard', compact('widgets', 'todayQueue'));
    }

    /**
     * Pharmacy dashboard.
     */
    public function pharmacyDashboard(Request $request)
    {
        $pendingPrescriptions = HospitalPrescription::with(['patient', 'doctor', 'items'])
            ->where('status', 'pending')
            ->latest()
            ->limit(10)
            ->get();

        $lowStockDrugs = HospitalDrug::whereColumn('current_stock', '<=', 'reorder_level')
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        $widgets = DashboardResolver::widgetsForUser($request->user());

        return view('hospital.pharmacy-dashboard', compact('widgets', 'pendingPrescriptions', 'lowStockDrugs'));
    }

    /**
     * Laboratory dashboard.
     */
    public function labDashboard(Request $request)
    {
        $pendingRequests = HospitalLabRequest::with(['patient', 'doctor'])
            ->whereIn('status', ['pending', 'sample_collected'])
            ->latest('requested_at')
            ->limit(10)
            ->get();

        $widgets = DashboardResolver::widgetsForUser($request->user());

        return view('hospital.lab-dashboard', compact('widgets', 'pendingRequests'));
    }
}