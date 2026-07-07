<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalPatient;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    /**
     * Display a listing of patients.
     */
    public function index(Request $request)
    {
        $query = HospitalPatient::with(['registeredByUser']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('patient_number', 'like', "%{$request->search}%")
                  ->orWhere('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->patient_type) {
            $query->where('patient_type', $request->patient_type);
        }

        if ($request->status) {
            $query->where('is_active', $request->status === 'active');
        }

        $patients = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('hospital.patients.index', compact('patients'));
    }

    /**
     * Show the form for creating a new patient.
     */
    public function create()
    {
        return view('hospital.patients.create');
    }

    /**
     * Store a newly created patient.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'other_name' => 'nullable|string|max:255',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date|before:today',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'required|string',
            'state' => 'nullable|string|max:255',
            'lga' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'next_of_kin_name' => 'required|string|max:255',
            'next_of_kin_phone' => 'required|string|max:20',
            'next_of_kin_relationship' => 'required|string|max:255',
            'next_of_kin_address' => 'nullable|string',
            'patient_type' => 'required|in:student,staff,visitor,dependent',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $patientNumber = $this->generatePatientNumber();

        $patient = HospitalPatient::create([
            'patient_number' => $patientNumber,
            'registered_by' => auth()->id(),
            ...$request->all()
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'patient_registered',
            'description' => "Registered new patient: {$patient->patient_number}",
            'entity_type' => 'hospital_patients',
            'entity_id' => $patient->id,
        ]);

        return redirect()->route('hospital.patients.show', $patient->id)
            ->with('success', 'Patient registered successfully. Patient Number: ' . $patientNumber);
    }

    /**
     * Display the specified patient.
     */
    public function show(HospitalPatient $patient)
    {
        $patient->load([
            'appointments.doctor',
            'medicalRecords.doctor',
            'prescriptions.doctor',
            'labRequests.doctor',
            'admissions.doctor',
            'vitalSigns.recordedBy',
            'referrals.referrer',
        ]);

        return view('hospital.patients.show', compact('patient'));
    }

    /**
     * Show the form for editing the patient.
     */
    public function edit(HospitalPatient $patient)
    {
        return view('hospital.patients.edit', compact('patient'));
    }

    /**
     * Update the specified patient.
     */
    public function update(Request $request, HospitalPatient $patient)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $oldValues = $patient->toArray();
        $patient->update($request->all());

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'patient_updated',
            'description' => "Updated patient: {$patient->patient_number}",
            'entity_type' => 'hospital_patients',
            'entity_id' => $patient->id,
            'old_values' => $oldValues,
            'new_values' => $patient->fresh()->toArray(),
        ]);

        return redirect()->route('hospital.patients.show', $patient->id)
            ->with('success', 'Patient updated successfully');
    }

    /**
     * Search patients.
     */
    public function search(Request $request)
    {
        $term = $request->term;
        $patients = HospitalPatient::where('patient_number', 'like', "%{$term}%")
            ->orWhere('first_name', 'like', "%{$term}%")
            ->orWhere('last_name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->limit(10)
            ->get(['id', 'patient_number', 'first_name', 'last_name', 'phone', 'patient_type']);

        return response()->json($patients);
    }

    /**
     * Generate unique patient number.
     */
    private function generatePatientNumber(): string
    {
        $year = date('Y');
        $prefix = 'PAT';

        $lastPatient = HospitalPatient::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastPatient ? (int)substr($lastPatient->patient_number, -6) + 1 : 1;

        return $prefix . $year . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}