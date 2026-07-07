<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalMedicalRecord;
use App\Models\Hospital\HospitalPrescription;
use App\Models\Hospital\HospitalLabRequest;
use App\Models\Hospital\HospitalAdmission;
use App\Models\Hospital\HospitalReport;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;

class PatientPortalController extends Controller
{
    /**
     * Student medical portal dashboard
     */
    public function index()
    {
        $user = auth()->user();

        // Find or create hospital patient record
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        if (!$patient) {
            // Create patient record from user data
            $patient = HospitalPatient::create([
                'user_id' => $user->id,
                'patient_number' => $this->generatePatientNumber(),
                'first_name' => explode(' ', $user->name)[0],
                'last_name' => implode(' ', array_slice(explode(' ', $user->name), 1)),
                'gender' => $user->gender,
                'date_of_birth' => $user->date_of_birth,
                'phone' => $user->phone,
                'email' => $user->email,
                'address' => $user->address ?? 'N/A',
                'next_of_kin_name' => $user->next_of_kin ?? 'N/A',
                'next_of_kin_phone' => $user->next_of_kin_phone ?? 'N/A',
                'next_of_kin_relationship' => 'N/A',
                'patient_type' => 'student',
                'registered_by' => $user->id,
            ]);
        }

        // Get patient data
        $appointments = HospitalAppointment::where('patient_id', $patient->id)
            ->orderBy('appointment_date', 'desc')
            ->limit(5)
            ->get();

        $medicalRecords = HospitalMedicalRecord::where('patient_id', $patient->id)
            ->with('doctor')
            ->orderBy('consultation_date', 'desc')
            ->limit(5)
            ->get();

        $prescriptions = HospitalPrescription::where('patient_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $labResults = HospitalLabRequest::where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        $admissions = HospitalAdmission::where('patient_id', $patient->id)
            ->orderBy('admission_date', 'desc')
            ->limit(5)
            ->get();

        return view('student.medical.index', compact(
            'patient', 'appointments', 'medicalRecords',
            'prescriptions', 'labResults', 'admissions'
        ));
    }

    /**
     * Book appointment
     */
    public function bookAppointment(Request $request)
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        $request->validate([
            'doctor_id' => 'required|exists:hospital_staff,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'complaint' => 'required|string',
        ]);

        $appointment = HospitalAppointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $request->doctor_id,
            'scheduled_by' => $user->id,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'complaint' => $request->complaint,
            'status' => 'scheduled',
        ]);

        // Create notification
        $this->createNotification($user->id, 'Appointment Scheduled',
            "Your appointment has been scheduled for {$request->appointment_date}");

        return redirect()->route('student.medical.appointments')
            ->with('success', 'Appointment booked successfully');
    }

    /**
     * View my appointments
     */
    public function myAppointments()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->firstOrFail();

        $appointments = HospitalAppointment::where('patient_id', $patient->id)
            ->with('doctor')
            ->orderBy('appointment_date', 'desc')
            ->paginate(10);

        return view('student.medical.appointments', compact('appointments'));
    }

    /**
     * View my medical history
     */
    public function myMedicalHistory()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->firstOrFail();

        $records = HospitalMedicalRecord::where('patient_id', $patient->id)
            ->with(['doctor', 'diagnoses'])
            ->orderBy('consultation_date', 'desc')
            ->paginate(10);

        return view('student.medical.history', compact('patient', 'records'));
    }

    /**
     * View my prescriptions
     */
    public function myPrescriptions()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->firstOrFail();

        $prescriptions = HospitalPrescription::where('patient_id', $patient->id)
            ->with(['doctor', 'items'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('student.medical.prescriptions', compact('prescriptions'));
    }

    /**
     * View my lab results
     */
    public function myLabResults()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->firstOrFail();

        $labRequests = HospitalLabRequest::where('patient_id', $patient->id)
            ->with(['doctor', 'results'])
            ->orderBy('requested_at', 'desc')
            ->paginate(10);

        return view('student.medical.lab-results', compact('labRequests'));
    }

    /**
     * View my admissions
     */
    public function myAdmissions()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->firstOrFail();

        $admissions = HospitalAdmission::where('patient_id', $patient->id)
            ->with(['doctor', 'bed'])
            ->orderBy('admission_date', 'desc')
            ->paginate(10);

        return view('student.medical.admissions', compact('admissions'));
    }

    /**
     * Request medical report
     */
    public function requestReport(Request $request)
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'report_type' => 'required|string',
            'purpose' => 'required|string',
        ]);

        $report = HospitalReport::create([
            'patient_id' => $patient->id,
            'generated_by' => $user->id,
            'report_type' => $request->report_type,
            'title' => "Medical {$request->report_type}",
            'status' => 'draft',
        ]);

        $this->createNotification($user->id, 'Report Requested',
            "Your medical report request has been submitted");

        return back()->with('success', 'Report requested successfully');
    }

    /**
     * Generate unique patient number
     */
    private function generatePatientNumber(): string
    {
        $year = date('Y');
        $lastPatient = HospitalPatient::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastPatient ? (int)substr($lastPatient->patient_number, -6) + 1 : 1;
        return 'PAT' . $year . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create notification
     */
    private function createNotification($userId, $title, $message)
    {
        \App\Models\Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => 'info',
        ]);
    }
}