<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalStaff;
use App\Models\User;
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
                'user_id'         => $user->id,
                'patient_number'  => $this->generatePatientNumber(),
                'registered_by'   => $user->id,
                'first_name'      => explode(' ', $user->name)[0] ?? 'Unknown',
                'last_name'       => implode(' ', array_slice(explode(' ', $user->name ?? ''), 1)) ?: 'Patient',
                'gender'          => $user->gender ?? 'male',
                'date_of_birth'   => $user->date_of_birth ?? now()->subYears(18)->format('Y-m-d'),
                'phone'           => $user->phone ?? 'N/A',
                'address'         => $user->address ?? 'N/A',
                'next_of_kin_name'         => 'Self',
                'next_of_kin_phone'        => $user->phone ?? 'N/A',
                'next_of_kin_relationship' => 'self',
                'is_active'       => true,
            ]);
        }

        // Get patient's appointments
        $appointments = HospitalAppointment::where('patient_id', $patient->id)
            ->orderBy('appointment_date', 'desc')
            ->limit(5)
            ->get();

        return view('student.medical.index', compact('patient', 'appointments'));
    }

    /**
     * Book appointment
     */
    public function bookAppointment(Request $request)
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        if (!$patient) {
            // Create patient record
            $patient = HospitalPatient::create([
                'user_id'                => $user->id,
                'patient_number'         => $this->generatePatientNumber(),
                'registered_by'          => $user->id,
                'first_name'             => explode(' ', $user->name)[0] ?? 'Unknown',
                'last_name'              => implode(' ', array_slice(explode(' ', $user->name ?? ''), 1)) ?: 'Patient',
                'gender'                 => $user->gender ?? 'male',
                'date_of_birth'          => $user->date_of_birth ?? now()->subYears(18)->format('Y-m-d'),
                'phone'                  => $user->phone ?? 'N/A',
                'address'                => $user->address ?? 'N/A',
                'next_of_kin_name'       => 'Self',
                'next_of_kin_phone'      => $user->phone ?? 'N/A',
                'next_of_kin_relationship' => 'self',
                'is_active'              => true,
            ]);
        }

        $request->validate([
            'doctor_id' => 'required|exists:hospital_staff,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'symptoms' => 'required|string',
        ]);

        // Generate appointment number
        $appointmentNumber = 'APT-' . strtoupper(uniqid());

        $appointment = HospitalAppointment::create([
            'patient_id' => $patient->id,
            'staff_id' => $request->doctor_id,
            'appointment_number' => $appointmentNumber,
            'appointment_date' => $request->appointment_date,
            'symptoms' => $request->symptoms,
            'status' => 'scheduled',
        ]);

        return redirect()->route('student.medical.appointments')
            ->with('success', 'Appointment booked successfully');
    }

    /**
     * View my appointments
     */
    public function myAppointments()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        $appointments = collect();
        if ($patient) {
            try {
                $appointments = HospitalAppointment::where('patient_id', $patient->id)
                    ->with('staff')
                    ->orderBy('appointment_date', 'desc')
                    ->paginate(10);
            } catch (\Throwable $e) {
                \Log::error('Student medical appointments failed: ' . $e->getMessage());
            }
        }

        return view('student.medical.appointments', compact('appointments'));
    }

    /**
     * View my medical history (placeholder - table may not exist)
     */
    public function myMedicalHistory()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        $appointments = collect();
        if ($patient) {
            try {
                $appointments = HospitalAppointment::where('patient_id', $patient->id)
                    ->orderBy('appointment_date', 'desc')
                    ->paginate(10);
            } catch (\Throwable $e) {
                \Log::error('Student medical history failed: ' . $e->getMessage());
            }
        }

        return view('student.medical.history', compact('patient', 'appointments'));
    }

    /**
     * View my prescriptions (placeholder - table may not exist)
     */
    public function myPrescriptions()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        $appointments = collect();
        if ($patient) {
            try {
                $appointments = HospitalAppointment::where('patient_id', $patient->id)
                    ->orderBy('appointment_date', 'desc')
                    ->paginate(10);
            } catch (\Throwable $e) {
                \Log::error('Student medical prescriptions failed: ' . $e->getMessage());
            }
        }

        return view('student.medical.prescriptions', compact('appointments'));
    }

    /**
     * View my lab results (placeholder - table may not exist)
     */
    public function myLabResults()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        $appointments = collect();
        if ($patient) {
            try {
                $appointments = HospitalAppointment::where('patient_id', $patient->id)
                    ->orderBy('appointment_date', 'desc')
                    ->paginate(10);
            } catch (\Throwable $e) {
                \Log::error('Student medical lab-results failed: ' . $e->getMessage());
            }
        }

        return view('student.medical.lab-results', compact('appointments'));
    }

    /**
     * View my admissions (placeholder - table may not exist)
     */
    public function myAdmissions()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        $appointments = collect();
        if ($patient) {
            try {
                $appointments = HospitalAppointment::where('patient_id', $patient->id)
                    ->orderBy('appointment_date', 'desc')
                    ->paginate(10);
            } catch (\Throwable $e) {
                \Log::error('Student medical admissions failed: ' . $e->getMessage());
            }
        }

        return view('student.medical.admissions', compact('appointments'));
    }

    /**
     * Generate a unique patient number
     */
    private function generatePatientNumber(): string
    {
        // Format: P-YYYYMMDD-XXXXX
        $prefix = 'P-' . now()->format('Ymd') . '-';
        $lastPatient = HospitalPatient::where('patient_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastPatient) {
            $numericPart = (int) substr($lastPatient->patient_number, strlen($prefix));
            $sequence = $numericPart + 1;
        }

        return $prefix . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
