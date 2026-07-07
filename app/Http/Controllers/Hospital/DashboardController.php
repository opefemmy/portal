<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalAdmission;
use App\Models\Hospital\HospitalDrug;
use App\Models\Hospital\HospitalPrescription;
use App\Models\Hospital\HospitalLabRequest;
use App\Models\Hospital\HospitalStaff;
use Illuminate\Support\Facades\DB;

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
            'pending_appointments' => HospitalAppointment::whereIn('status', ['scheduled', 'confirmed'])
                ->count(),
            'active_patients' => HospitalPatient::where('is_active', true)->count(),
            'admitted_patients' => HospitalAdmission::where('status', 'admitted')->count(),
            'pending_prescriptions' => HospitalPrescription::where('status', 'pending')->count(),
            'pending_lab_tests' => HospitalLabRequest::whereIn('status', ['pending', 'sample_collected', 'in_progress'])
                ->count(),
            'low_stock_drugs' => HospitalDrug::whereRaw('current_stock <= reorder_level')->count(),
            'today_patients' => HospitalPatient::whereDate('created_at', today())->count(),
        ];

        // Today's appointments
        $todayAppointments = HospitalAppointment::with(['patient', 'doctor'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->limit(10)
            ->get();

        // Recent patients
        $recentPatients = HospitalPatient::with('registeredByUser')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Recent admissions
        $recentAdmissions = HospitalAdmission::with(['patient', 'doctor', 'bed.ward'])
            ->where('status', 'admitted')
            ->orderBy('admission_date', 'desc')
            ->limit(10)
            ->get();

        // Top diseases this month
        $topDiseases = DB::table('hospital_diagnoses')
            ->join('hospital_medical_records', 'hospital_diagnoses.medical_record_id', '=', 'hospital_medical_records.id')
            ->whereMonth('hospital_medical_records.consultation_date', date('m'))
            ->whereYear('hospital_medical_records.consultation_date', date('Y'))
            ->select('hospital_diagnoses.diagnosis', DB::raw('count(*) as count'))
            ->groupBy('hospital_diagnoses.diagnosis')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return view('hospital.dashboard', compact('stats', 'todayAppointments', 'recentPatients', 'recentAdmissions', 'topDiseases'));
    }

    /**
     * Doctor dashboard.
     */
    public function doctorDashboard()
    {
        $doctorId = auth()->user()->hospitalStaff->id ?? null;

        if (!$doctorId) {
            return redirect()->back()->with('error', 'Doctor profile not found');
        }

        $todayAppointments = HospitalAppointment::with('patient')
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['scheduled', 'confirmed', 'checked_in'])
            ->orderBy('appointment_time')
            ->get();

        $pendingConsultations = HospitalAppointment::with('patient')
            ->where('doctor_id', $doctorId)
            ->where('status', 'in_progress')
            ->get();

        $completedToday = HospitalAppointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', today())
            ->where('status', 'completed')
            ->count();

        $stats = [
            'today_appointments' => $todayAppointments->count(),
            'pending_consultations' => $pendingConsultations->count(),
            'completed_today' => $completedToday,
            'total_patients' => HospitalAppointment::where('doctor_id', $doctorId)
                ->distinct('patient_id')->count('patient_id'),
        ];

        return view('hospital.doctor-dashboard', compact('stats', 'todayAppointments', 'pendingConsultations'));
    }

    /**
     * Nurse dashboard.
     */
    public function nurseDashboard()
    {
        $todayAppointments = HospitalAppointment::with(['patient', 'doctor'])
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['checked_in'])
            ->orderBy('appointment_time')
            ->get();

        $admittedPatients = HospitalAdmission::with(['patient', 'doctor', 'bed.ward'])
            ->where('status', 'admitted')
            ->orderBy('admission_date', 'desc')
            ->get();

        $stats = [
            'today_appointments' => $todayAppointments->count(),
            'admitted_patients' => $admittedPatients->count(),
            'vitals_recorded_today' => 0, // Add query if needed
        ];

        return view('hospital.nurse-dashboard', compact('stats', 'todayAppointments', 'admittedPatients'));
    }

    /**
     * Receptionist dashboard.
     */
    public function receptionistDashboard()
    {
        $todayQueue = HospitalAppointment::with(['patient', 'doctor'])
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['scheduled', 'confirmed', 'checked_in'])
            ->orderBy('appointment_time')
            ->get();

        $checkedInToday = HospitalAppointment::whereDate('appointment_date', today())
            ->where('status', 'checked_in')
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
        $pendingPrescriptions = HospitalPrescription::with(['patient', 'doctor'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $lowStockDrugs = HospitalDrug::whereRaw('current_stock <= reorder_level')
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        $stats = [
            'pending_prescriptions' => HospitalPrescription::where('status', 'pending')->count(),
            'dispensed_today' => HospitalPrescription::whereDate('dispensed_at', today())->count(),
            'low_stock_items' => $lowStockDrugs->count(),
            'total_drugs' => HospitalDrug::count(),
        ];

        return view('hospital.pharmacy-dashboard', compact('stats', 'pendingPrescriptions', 'lowStockDrugs'));
    }

    /**
     * Laboratory dashboard.
     */
    public function labDashboard()
    {
        $pendingRequests = HospitalLabRequest::with(['patient', 'doctor'])
            ->whereIn('status', ['pending', 'sample_collected'])
            ->orderBy('requested_at')
            ->limit(10)
            ->get();

        $completedToday = HospitalLabRequest::whereDate('completed_at', today())->count();

        $stats = [
            'pending_requests' => HospitalLabRequest::whereIn('status', ['pending', 'sample_collected'])->count(),
            'in_progress' => HospitalLabRequest::where('status', 'in_progress')->count(),
            'completed_today' => $completedToday,
            'total_tests' => HospitalLabRequest::count(),
        ];

        return view('hospital.lab-dashboard', compact('stats', 'pendingRequests'));
    }
}