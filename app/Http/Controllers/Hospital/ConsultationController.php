<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalMedicalRecord;
use App\Models\Hospital\HospitalDiagnosis;
use App\Models\Hospital\HospitalPrescription;
use App\Models\Hospital\HospitalVitalSign;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalStaff;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsultationController extends Controller
{
    /**
     * Create new consultation.
     */
    public function create(Request $request)
    {
        $appointmentId = $request->appointment_id;
        $appointment = HospitalAppointment::with('patient')->findOrFail($appointmentId);

        $patient = $appointment->patient;
        $doctors = HospitalStaff::where('staff_type', 'doctor')
            ->where('is_active', true)
            ->get();

        // Get patient's recent medical history
        $medicalHistory = HospitalMedicalRecord::where('patient_id', $patient->id)
            ->with('doctor')
            ->orderBy('consultation_date', 'desc')
            ->limit(10)
            ->get();

        return view('hospital.consultations.create', compact('appointment', 'patient', 'doctors', 'medicalHistory'));
    }

    /**
     * Store consultation record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:hospital_patients,id',
            'doctor_id' => 'required|exists:hospital_staff,id',
            'appointment_id' => 'nullable|exists:hospital_appointments,id',
            'chief_complaint' => 'nullable|string',
            'symptoms' => 'nullable|string',
            'examination_findings' => 'nullable|string',
            'doctor_notes' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'visit_type' => 'required|in:new,follow_up,emergency,referral',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Create medical record
        $medicalRecord = HospitalMedicalRecord::create([
            'consultation_date' => now(),
            ...$request->all()
        ]);

        // Record diagnoses if provided
        if ($request->diagnoses && is_array($request->diagnoses)) {
            foreach ($request->diagnoses as $diagnosis) {
                HospitalDiagnosis::create([
                    'medical_record_id' => $medicalRecord->id,
                    'patient_id' => $request->patient_id,
                    'diagnosis' => $diagnosis['diagnosis'] ?? '',
                    'icd_code' => $diagnosis['icd_code'] ?? null,
                    'type' => $diagnosis['type'] ?? 'primary',
                    'severity' => $diagnosis['severity'] ?? null,
                ]);
            }
        }

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'consultation_created',
            'description' => "Created consultation for patient ID: {$request->patient_id}",
            'entity_type' => 'hospital_medical_records',
            'entity_id' => $medicalRecord->id,
        ]);

        // Update appointment status if exists
        if ($request->appointment_id) {
            HospitalAppointment::where('id', $request->appointment_id)
                ->update(['status' => 'completed', 'completed_at' => now()]);
        }

        return redirect()->route('hospital.consultations.show', $medicalRecord->id)
            ->with('success', 'Consultation recorded successfully');
    }

    /**
     * Show consultation details.
     */
    public function show(HospitalMedicalRecord $consultation)
    {
        $consultation->load(['patient', 'doctor', 'diagnoses', 'prescriptions.doctor', 'labRequests']);

        return view('hospital.consultations.show', compact('consultation'));
    }

    /**
     * Get patient medical timeline.
     */
    public function timeline(HospitalPatient $patient)
    {
        $patient->load([
            'medicalRecords.doctor',
            'diagnoses',
            'prescriptions.items.drug',
            'labRequests.results',
            'admissions',
            'vitalSigns',
        ]);

        $timeline = collect();

        // Merge all records and sort by date
        $records = $patient->medicalRecords->map(function($r) {
            return (object)['type' => 'consultation', 'date' => $r->consultation_date, 'data' => $r];
        });

        $prescriptions = $patient->prescriptions->map(function($r) {
            return (object)['type' => 'prescription', 'date' => $r->created_at, 'data' => $r];
        });

        $labRequests = $patient->labRequests->map(function($r) {
            return (object)['type' => 'lab', 'date' => $r->requested_at, 'data' => $r];
        });

        $admissions = $patient->admissions->map(function($r) {
            return (object)['type' => 'admission', 'date' => $r->admission_date, 'data' => $r];
        });

        $timeline = $records->concat($prescriptions)->concat($labRequests)->concat($admissions)
            ->sortByDesc('date')
            ->paginate(20);

        return view('hospital.patients.timeline', compact('patient', 'timeline'));
    }

    /**
     * Record vital signs.
     */
    public function recordVitals(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:hospital_patients,id',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'blood_pressure_systolic' => 'nullable|integer|min:60|max:250',
            'blood_pressure_diastolic' => 'nullable|integer|min:30|max:150',
            'weight' => 'nullable|numeric|min:1|max:500',
            'height' => 'nullable|numeric|min:20|max:300',
            'pulse' => 'nullable|integer|min:30|max:200',
            'oxygen_level' => 'nullable|integer|min:50|max:100',
            'blood_sugar' => 'nullable|numeric|min:20|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $vitalSign = HospitalVitalSign::create([
            'recorded_by' => auth()->user()->hospitalStaff->id ?? null,
            ...$request->all()
        ]);

        return redirect()->back()->with('success', 'Vital signs recorded successfully');
    }
}