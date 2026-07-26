<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalStaff;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HealthController extends Controller
{
    /**
     * Get or create hospital patient record for the student.
     */
    private function getOrCreatePatient()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return null;
        }

        // Check if hospital patient record exists
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        if (!$patient) {
            // Create hospital patient record
            $patient = HospitalPatient::create([
                'user_id' => $user->id,
                'first_name' => $user->name,
                'last_name' => '',
                'gender' => $user->gender ?? 'male',
                'date_of_birth' => $user->date_of_birth ?? now()->subYears(18),
                'phone' => $user->phone ?? '',
                'address' => $user->address ?? '',
                'is_active' => true,
            ]);
        }

        return $patient;
    }

    /**
     * Show student's health dashboard.
     */
    public function index()
    {
        $patient = $this->getOrCreatePatient();

        if (!$patient) {
            return redirect()->back()->with('error', 'Student record not found');
        }

        // Get patient's appointments
        $appointments = HospitalAppointment::where('patient_id', $patient->id)
            ->orderBy('appointment_date', 'desc')
            ->limit(10)
            ->get();

        // Get available doctors
        $doctors = HospitalStaff::where('staff_type', 'doctor')
            ->where('is_active', true)
            ->get();

        return view('student.health', compact('patient', 'appointments', 'doctors'));
    }

    /**
     * Book a new appointment.
     */
    public function storeAppointment(Request $request)
    {
        $patient = $this->getOrCreatePatient();

        if (!$patient) {
            return back()->with('error', 'Student record not found');
        }

        $validated = $request->validate([
            'staff_id' => 'required|exists:hospital_staff,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'symptoms' => 'required|string',
        ]);

        // Generate appointment number
        $appointmentNumber = 'APT-' . strtoupper(uniqid());

        HospitalAppointment::create([
            'patient_id' => $patient->id,
            'staff_id' => $validated['staff_id'],
            'appointment_number' => $appointmentNumber,
            'appointment_date' => $validated['appointment_date'],
            'status' => 'scheduled',
            'symptoms' => $validated['symptoms'],
        ]);

        return back()->with('success', 'Appointment booked successfully!');
    }

    /**
     * View appointment details.
     */
    public function showAppointment($id)
    {
        $patient = $this->getOrCreatePatient();

        $appointment = HospitalAppointment::where('id', $id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        return view('student.health-appointment', compact('appointment'));
    }
}
