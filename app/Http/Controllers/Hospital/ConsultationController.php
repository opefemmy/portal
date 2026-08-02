<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Concerns\EnforcesHospitalPermission;
use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalMedicalRecord;
use App\Models\Hospital\HospitalDiagnosis;
use App\Models\Hospital\HospitalPrescription;
use App\Models\Hospital\HospitalPrescriptionItem;
use App\Models\Hospital\HospitalDrug;
use App\Models\Hospital\HospitalLabRequest;
use App\Models\Hospital\HospitalOrderItem;
use App\Models\Hospital\ExternalPatient;
use App\Models\Hospital\HospitalVitalSign;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalStaff;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsultationController extends Controller
{
    use EnforcesHospitalPermission;

    /**
     * List consultations (records created today, paginated).
     */
    public function index(Request $request)
    {
        $this->requirePermission('consultations.view');

        $records = HospitalMedicalRecord::with(['patient', 'doctor'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('hospital.consultations.index', compact('records'));
    }

    /**
     * Create new consultation.
     */
    public function create(Request $request)
    {
        $this->requirePermission('consultations.create');

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
        $this->requirePermission('consultations.create');

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
        $this->requirePermission('consultations.view');

        $consultation->load(['patient', 'doctor', 'diagnoses', 'prescriptions.doctor', 'labRequests']);

        return view('hospital.consultations.show', compact('consultation'));
    }

    /**
     * Get patient medical timeline.
     */
    public function timeline(HospitalPatient $patient)
    {
        $this->requirePermission('patients.view');

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
        $this->requirePermission('visits.vitals');
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

    /**
     * Add a prescription to this consultation.
     *
     * Each prescribed drug becomes a HospitalPrescriptionItem and a
     * HospitalOrderItem awaiting payment. Once the patient pays, the
     * pharmacist sees the prescription on the pharmacy queue.
     */
    public function addPrescription(Request $request, HospitalMedicalRecord $record)
    {
        $this->requirePermission('prescriptions.create');

        $data = $request->validate([
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.drug_id'        => 'required|exists:hospital_drugs,id',
            'items.*.dosage'         => 'required|string|max:100',
            'items.*.frequency'      => 'required|string|max:100',
            'items.*.duration'       => 'required|string|max:100',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.instructions'   => 'nullable|string',
        ]);

        $externalPatientId = $this->resolveExternalPatientId($record->patient);

        $prescription = HospitalPrescription::create([
            'patient_id'        => $record->patient_id,
            'doctor_id'         => $record->doctor_id,
            'medical_record_id' => $record->id,
            'notes'             => $data['notes'] ?? null,
            'status'            => 'pending',
        ]);

        foreach ($data['items'] as $row) {
            $drug = HospitalDrug::findOrFail($row['drug_id']);

            $item = HospitalPrescriptionItem::create([
                'prescription_id' => $prescription->id,
                'drug_id'         => $drug->id,
                'drug_name'       => $drug->name,
                'dosage'          => $row['dosage'],
                'frequency'       => $row['frequency'],
                'duration'        => $row['duration'],
                'quantity'        => $row['quantity'],
                'instructions'    => $row['instructions'] ?? null,
                'is_dispensed'    => false,
            ]);

            HospitalOrderItem::create([
                'orderable_type'      => HospitalPrescriptionItem::class,
                'orderable_id'        => $item->id,
                'patient_id'          => $record->patient_id,
                'external_patient_id' => $externalPatientId,
                'item_name'           => $drug->name . ' × ' . (int) $row['quantity'],
                'amount'              => (float) $drug->selling_price * (int) $row['quantity'],
                'status'              => HospitalOrderItem::STATUS_AWAITING_PAYMENT,
                'created_by'          => auth()->id(),
            ]);
        }

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'prescription_created',
            'description' => "Doctor prescribed " . count($data['items']) . " item(s) for medical record #{$record->id}",
            'entity_type' => 'hospital_prescriptions',
            'entity_id' => $prescription->id,
        ]);

        return back()->with('success', 'Prescription added. Patient will see it on their dashboard for payment.');
    }

    /**
     * Suggest a test / x-ray scan for this consultation.
     *
     * Creates a HospitalLabRequest plus a HospitalOrderItem awaiting payment.
     * Once paid, the request shows up in the lab queue.
     */
    public function addLabRequest(Request $request, HospitalMedicalRecord $record)
    {
        $this->requirePermission('lab.create');

        $data = $request->validate([
            'test_type'     => 'required|string|max:200',
            'clinical_notes'=> 'nullable|string',
            'amount'        => 'required|numeric|min:0',
        ]);

        $externalPatientId = $this->resolveExternalPatientId($record->patient);

        $lab = HospitalLabRequest::create([
            'patient_id'        => $record->patient_id,
            'doctor_id'         => $record->doctor_id,
            'medical_record_id' => $record->id,
            'test_type'         => $data['test_type'],
            'clinical_notes'    => $data['clinical_notes'] ?? null,
            'status'            => 'pending',
            'amount'            => $data['amount'],
            'requested_at'      => now(),
        ]);

        HospitalOrderItem::create([
            'orderable_type'      => HospitalLabRequest::class,
            'orderable_id'        => $lab->id,
            'patient_id'          => $record->patient_id,
            'external_patient_id' => $externalPatientId,
            'item_name'           => $data['test_type'],
            'amount'              => $data['amount'],
            'status'              => HospitalOrderItem::STATUS_AWAITING_PAYMENT,
            'created_by'          => auth()->id(),
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'lab_request_created',
            'description' => "Doctor suggested test '{$data['test_type']}' for medical record #{$record->id}",
            'entity_type' => 'hospital_lab_requests',
            'entity_id' => $lab->id,
        ]);

        return back()->with('success', 'Test request added. Patient will see it on their dashboard for payment.');
    }

    /**
     * Look up an ExternalPatient by phone/email so we can surface
     * prescribed items on the patient-portal dashboard.
     */
    private function resolveExternalPatientId(?HospitalPatient $patient): ?int
    {
        if (!$patient) {
            return null;
        }

        return ExternalPatient::query()
            ->when($patient->phone, fn ($q) => $q->orWhere('phone', $patient->phone))
            ->when($patient->email, fn ($q) => $q->orWhere('email', $patient->email))
            ->value('id');
    }
}