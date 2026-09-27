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

        // Safety check: Prompt user to complete medical profile if critical info is missing.
        // We check for null, empty string, or the specific default values "Not Set" and "None".
        $bloodGroup = trim($patient->blood_group ?? '');
        $allergies = trim($patient->allergies ?? '');

        $missingBloodGroup = empty($bloodGroup) || strcasecmp($bloodGroup, 'Not Set') === 0;
        $missingAllergies = empty($allergies) || strcasecmp($allergies, 'None') === 0;

        if ($missingBloodGroup || $missingAllergies) {
            // Use with() instead of session()->flash() to ensure it's passed to the view
            return view('student.health', [
                'patient' => $patient,
                'appointments' => HospitalAppointment::where('patient_id', $patient->id)
                    ->orderBy('appointment_date', 'desc')
                    ->limit(10)
                    ->get(),
                'warning' => 'Your medical profile is incomplete. Please update your Blood Type and Allergies to ensure patient safety.'
            ]);
        }

        // Get patient's appointments
        $appointments = HospitalAppointment::where('patient_id', $patient->id)
            ->orderBy('appointment_date', 'desc')
            ->limit(10)
            ->get();

        return view('student.health', compact('patient', 'appointments'));
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

        // Safety check: Ensure critical medical information is set before booking.
        $missingBloodGroup = empty($patient->blood_group) || $patient->blood_group === 'Not Set';
        $missingAllergies = empty($patient->allergies) || $patient->allergies === 'None';

        if ($missingBloodGroup || $missingAllergies) {
            return redirect()->route('student.medical.index')
                ->with('warning', 'Please update your Blood Type and Allergies in your medical profile before booking an appointment.');
        }

        $validated = $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'symptoms' => 'required|string',
        ]);

        // Automatic Doctor Allocation
        $doctorId = $this->pickAvailableDoctorId();

        if (!$doctorId) {
            return back()->with('error', 'No available doctors on duty. Please try again later or contact the medical center.');
        }

        // Generate appointment number
        $appointmentNumber = 'APT-' . strtoupper(uniqid());

        HospitalAppointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorId,
            'appointment_number' => $appointmentNumber,
            'appointment_date' => $validated['appointment_date'],
            'status' => 'scheduled',
            'symptoms' => $validated['symptoms'],
        ]);

        return back()->with('success', 'Appointment booked successfully!');
    }

    /**
     * Pick the doctor with the lightest in-progress load who is also
     * marked `is_available = true` on the staff table. Returns null
     * if no doctor is on duty.
     */
    private function pickAvailableDoctorId(): ?int
    {
        return \App\Models\Hospital\HospitalStaff::where('staff_type', 'doctor')
            ->where('is_active', true)
            ->where('is_available', true)
            ->withCount(['appointments as in_progress_count' => function ($q) {
                $q->whereIn('status', ['in_progress', 'awaiting_doctor', 'records_certified']);
            }])
            ->orderBy('in_progress_count')
            ->orderBy('id')
            ->value('id');
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
