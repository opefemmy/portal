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

        // Safety check: Prompt user to complete medical profile if critical info is missing.
        if ($patient) {
            $bloodGroup = trim($patient->blood_group ?? '');
            $allergies = trim($patient->allergies ?? '');

            $missingBloodGroup = empty($bloodGroup) || strcasecmp($bloodGroup, 'Not Set') === 0;
            $missingAllergies = empty($allergies) || strcasecmp($allergies, 'None') === 0;

            if ($missingBloodGroup || $missingAllergies) {
                session()->flash('warning', 'Your medical profile is incomplete. Please update your Blood Type and Allergies to ensure patient safety.');
            }
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
            'appointment_date' => 'required|date|after_or_equal:today',
            'symptoms' => 'required|string',
        ]);

        // Automatic Doctor Allocation
        $doctorId = $this->pickAvailableDoctorId();

        if (!$doctorId) {
            return redirect()->route('student.medical.index')
                ->with('error', 'No available doctors on duty. Please try again later or contact the medical center.');
        }

        $appointment = HospitalAppointment::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => $doctorId,
            'scheduled_by'     => $user->id,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => '09:00:00',
            'complaint'        => $request->symptoms,
            'status'           => 'scheduled',
        ]);

        return redirect()->route('student.medical.appointments')
            ->with('success', 'Appointment booked successfully');
    }

    /**
     * Pick the doctor with the lightest in-progress load who is also
     * marked `is_available = true` on the staff table. Returns null
     * if no doctor is on duty.
     */
    private function pickAvailableDoctorId(): ?int
    {
        return HospitalStaff::where('staff_type', 'doctor')
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
