<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalStaff;

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

        return view('hospital.nurse-dashboard', compact('stats', 'todayAppointments'));
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
        ];

        return view('hospital.pharmacy-dashboard', compact('stats'));
    }

    /**
     * Laboratory dashboard.
     */
    public function labDashboard()
    {
        $stats = [
            'today_appointments' => HospitalAppointment::whereDate('appointment_date', today())->count(),
            'total_patients' => HospitalPatient::count(),
        ];

        return view('hospital.lab-dashboard', compact('stats'));
    }
}
