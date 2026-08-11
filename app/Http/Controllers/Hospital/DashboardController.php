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

class DashboardController extends Controller
{
    /**
     * Hospital admin dashboard.
     */
    public function index()
    {
        $stats = [
            'today_appointments' => HospitalAppointment::whereDate('appointment_date', today())
                ->count(),
            'pending_appointments' => HospitalAppointment::where('status', 'scheduled')
                ->count(),
            'active_patients' => HospitalPatient::where('is_active', true)->count(),
            'total_staff' => HospitalStaff::count(),
            'today_patients' => HospitalPatient::whereDate('created_at', today())->count(),
        ];

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

        return view('hospital.dashboard', compact('stats', 'todayAppointments', 'recentPatients'));
    }

    /**
     * Doctor dashboard.
     */
    public function doctorDashboard()
    {
        $hospitalStaff = auth()->user()->hospitalStaff ?? null;

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

        $completedToday = HospitalAppointment::where('staff_id', $doctorId)
            ->whereDate('appointment_date', today())
            ->where('status', 'completed')
            ->count();

        $stats = [
            'today_appointments' => $todayAppointments->count(),
            'pending_consultations' => $pendingConsultations->count(),
            'completed_today' => $completedToday,
            'total_patients' => HospitalAppointment::where('staff_id', $doctorId)
                ->distinct('patient_id')->count('patient_id'),
        ];

        return view('hospital.doctor-dashboard', compact('stats', 'todayAppointments', 'pendingConsultations'));
    }

    /**
     * Nurse dashboard.
     */
    public function nurseDashboard()
    {
        $todayAppointments = HospitalAppointment::with(['patient', 'staff'])
            ->whereDate('appointment_date', today())
            ->where('status', 'scheduled')
            ->orderBy('appointment_date')
            ->get();

        $stats = [
            'today_appointments' => $todayAppointments->count(),
            'active_patients' => HospitalPatient::where('is_active', true)->count(),
        ];

        $admittedPatients = HospitalAdmission::with(['patient', 'bed.ward', 'doctor'])
            ->where('status', 'admitted')
            ->latest('admission_date')
            ->limit(10)
            ->get();

        return view('hospital.nurse-dashboard', compact('stats', 'todayAppointments', 'admittedPatients'));
    }

    /**
     * Receptionist dashboard.
     */
    public function receptionistDashboard()
    {
        $todayQueue = HospitalAppointment::with(['patient', 'staff'])
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['scheduled'])
            ->orderBy('appointment_date')
            ->get();

        $checkedInToday = HospitalAppointment::whereDate('appointment_date', today())
            ->where('status', 'completed')
            ->count();

        $stats = [
            'queue_count' => $todayQueue->count(),
            'checked_in_today' => $checkedInToday,
            'total_patients' => HospitalPatient::count(),
            'new_patients_today' => HospitalPatient::whereDate('created_at', today())->count(),
        ];

        return view('hospital.receptionist-dashboard', compact('stats', 'todayQueue'));
    }

    /**
     * Pharmacy dashboard.
     */
    public function pharmacyDashboard()
    {
        $stats = [
            'active_patients' => HospitalPatient::where('is_active', true)->count(),
            'total_staff' => HospitalStaff::count(),
            'pending_prescriptions' => HospitalPrescription::where('status', 'pending')->count(),
            'dispensed_today' => HospitalPrescription::where('status', 'dispensed')
                ->whereDate('dispensed_at', today())->count(),
            'low_stock_items' => HospitalDrug::where('current_stock', '<=', 10)->count(),
            'total_drugs' => HospitalDrug::count(),
        ];

        $pendingPrescriptions = HospitalPrescription::with(['patient', 'doctor', 'items'])
            ->where('status', 'pending')
            ->latest()
            ->limit(10)
            ->get();

        $lowStockDrugs = HospitalDrug::where('current_stock', '<=', 10)
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        return view('hospital.pharmacy-dashboard', compact('stats', 'pendingPrescriptions', 'lowStockDrugs'));
    }

    /**
     * Laboratory dashboard.
     */
    public function labDashboard()
    {
        $stats = [
            'today_appointments' => HospitalAppointment::whereDate('appointment_date', today())->count(),
            'total_patients' => HospitalPatient::count(),
            'pending_requests' => HospitalLabRequest::where('status', 'pending')->count(),
            'in_progress' => HospitalLabRequest::where('status', 'in_progress')->count(),
            'completed_today' => HospitalLabRequest::where('status', 'completed')
                ->whereDate('completed_at', today())->count(),
            'total_tests' => HospitalLabRequest::count(),
        ];

        $pendingRequests = HospitalLabRequest::with(['patient', 'doctor'])
            ->whereIn('status', ['pending', 'sample_collected'])
            ->latest('requested_at')
            ->limit(10)
            ->get();

        return view('hospital.lab-dashboard', compact('stats', 'pendingRequests'));
    }
}
