<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalStaff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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
            $patient = $this->createPatientForUser($user);
        }

        $appointments = collect();
        if ($patient) {
            try {
                $appointments = HospitalAppointment::where('patient_id', $patient->id)
                    ->orderBy('appointment_date', 'desc')
                    ->limit(5)
                    ->get();
            } catch (\Throwable $e) {
                \Log::error('Student medical index query failed: ' . $e->getMessage());
            }
        }

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
            $patient = $this->createPatientForUser($user);
        }

        if (!$patient) {
            return redirect()->route('student.medical.index')
                ->with('error', 'Unable to load your patient profile. Please try again or contact support.');
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

        $appointments = $this->emptyPaginator();
        if ($patient) {
            try {
                $appointments = HospitalAppointment::where('patient_id', $patient->id)
                    ->orderBy('appointment_date', 'desc')
                    ->paginate(10);
            } catch (\Throwable $e) {
                \Log::error('Student medical appointments failed: ' . $e->getMessage());
            }
        }

        return view('student.medical.appointments', compact('appointments'));
    }

    /**
     * View my medical history
     */
    public function myMedicalHistory()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        $appointments = $this->emptyPaginator();
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
     * View my prescriptions
     */
    public function myPrescriptions()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        $appointments = $this->emptyPaginator();
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
     * View my lab results
     */
    public function myLabResults()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        $appointments = $this->emptyPaginator();
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
     * View my admissions
     */
    public function myAdmissions()
    {
        $user = auth()->user();
        $patient = HospitalPatient::where('user_id', $user->id)->first();

        $appointments = $this->emptyPaginator();
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
     * Create a HospitalPatient for a user, returning null on failure rather than 500ing.
     */
    private function createPatientForUser(User $user): ?HospitalPatient
    {
        try {
            return HospitalPatient::create([
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
        } catch (\Throwable $e) {
            \Log::error('Student medical patient create failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Empty paginator so views calling ->links() never 500 when there's no patient/records.
     */
    private function emptyPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            collect(),
            0,
            10,
            1,
            [
                'path'     => request()->url(),
                'pageName' => 'page',
            ]
        );
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
