<?php

namespace App\Http\Controllers\Hospital;

use App\Events\Hospital\PatientArchived;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Hospital\HospitalAuditTrail;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalRecordRequest;
use App\Models\Hospital\HospitalRecordTransfer;
use App\Services\Hospital\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Patient-records archive workflow for the medical_records_officer role.
 *
 * Reuses the existing HospitalPatient, HospitalAuditTrail models and the
 * audit-trail service for every action. Adds three new tables:
 *   - hospital_record_transfers  (chart transfer log)
 *   - hospital_record_requests   (clinician chart request queue)
 *   - hospital_patients.archived_at / archived_by
 */
class RecordsController extends Controller
{
    use EnforcesPermission;

    /**
     * Default landing page: list of patients with last-visit date and
     * archive state. Acts as the records officer's "today's queue".
     */
    public function index()
    {
        $this->requirePermission('records.view');

        $patients = HospitalPatient::with('admissions')
            ->withCount(['medicalRecords', 'prescriptions', 'labRequests'])
            ->orderByDesc('updated_at')
            ->paginate(25);

        $pendingRequests = HospitalRecordRequest::where('status', HospitalRecordRequest::STATUS_PENDING)->count();

        return view('hospital.records.index', compact('patients', 'pendingRequests'));
    }

    /**
     * Search by patient number, name, phone or blood group. Records every
     * hit in the audit trail so chart access is reviewable later.
     */
    public function search(Request $request)
    {
        $this->requirePermission('records.view');

        $query = HospitalPatient::query();

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('patient_number', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('blood_group', $q);
            });
        }

        $results = $query->orderBy('last_name')->limit(50)->get();

        if ($request->filled('q')) {
            foreach ($results as $patient) {
                AuditTrail::record(
                    'records.search',
                    $patient,
                    $patient->id,
                    [],
                    [],
                    ['query' => $q]
                );
            }
        }

        return view('hospital.records.search', compact('results', 'q'));
    }

    /**
     * Full chart view (read-only). Reuses the existing patient.timeline
     * partial for vitals/notes/lab/prescriptions.
     */
    public function show(HospitalPatient $patient)
    {
        $this->requirePermission('records.view');

        $patient->load(['admissions.bed.ward', 'admissions.doctor', 'vitalSigns', 'prescriptions.items', 'labRequests']);

        AuditTrail::record('records.view', $patient, $patient->id);

        $recentAudit = AuditTrail::forPatient($patient->id, 25);
        $transfers    = $patient->id ? HospitalRecordTransfer::where('patient_id', $patient->id)
            ->with('transferredByUser')->latest('transferred_at')->limit(10)->get() : collect();

        return view('hospital.records.show', compact('patient', 'recentAudit', 'transfers'));
    }

    /**
     * Mark a chart archived. The patient row stays visible to clinical
     * staff (legal retention) but the records officer's archive flag
     * suppresses it from active queues.
     */
    public function archive(Request $request, HospitalPatient $patient)
    {
        $this->requirePermission('records.archive');

        if ($patient->archived_at) {
            return back()->with('error', "Patient {$patient->full_name} is already archived.");
        }

        $data = $request->validate([
            'archive_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $patient->update([
            'archived_at' => now(),
            'archived_by' => $request->user()->id,
        ]);

        AuditTrail::record('records.archive', $patient, $patient->id, [], [], $data);

        event(new PatientArchived($patient->id));

        return redirect()
            ->route('hospital.records.index')
            ->with('success', "Patient {$patient->full_name} archived.");
    }

    /**
     * Restore a previously archived chart. Only used when the records
     * officer mistakenly archived the wrong patient.
     */
    public function unarchive(HospitalPatient $patient)
    {
        $this->requirePermission('records.archive');

        if (! $patient->archived_at) {
            return back()->with('error', 'Patient is not archived.');
        }

        $patient->update([
            'archived_at' => null,
            'archived_by' => null,
        ]);

        AuditTrail::record('records.unarchive', $patient, $patient->id);

        return redirect()->route('hospital.records.index')->with('success', 'Archive reverted.');
    }

    /**
     * Log a chart transfer (intra-department or to an external facility).
     */
    public function transfer(Request $request, HospitalPatient $patient)
    {
        $this->requirePermission('records.transfer');

        $data = $request->validate([
            'transfer_to'     => ['required', 'string', 'max:150'],
            'transfer_reason' => ['nullable', Rule::in(['internal', 'external_facility', 'court_order', 'insurance', 'other'])],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        $row = HospitalRecordTransfer::create([
            'patient_id'      => $patient->id,
            'transfer_to'     => $data['transfer_to'],
            'transfer_reason' => $data['transfer_reason'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'transferred_by'  => $request->user()->id,
            'transferred_at'  => now(),
        ]);

        AuditTrail::record('records.transfer', $patient, $patient->id, [], [], [
            'transfer_id' => $row->id,
            'to' => $row->transfer_to,
        ]);

        return redirect()
            ->route('hospital.records.show', $patient)
            ->with('success', 'Transfer logged.');
    }

    /**
     * Audit-trail viewer — filterable by patient and action.
     */
    public function auditLog(Request $request)
    {
        $this->requirePermission('audit.view');

        $query = HospitalAuditTrail::with('user', 'patient');

        if ($patientId = $request->query('patient_id')) {
            $query->where('patient_id', (int) $patientId);
        }
        if ($action = $request->query('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        $entries = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        return view('hospital.records.audit', compact('entries'));
    }

    /**
     * Clinician chart-request queue. Records officers fulfill or reject
     * requests; status transitions are recorded.
     */
    public function requests(Request $request)
    {
        $this->requirePermission('records.request');

        $query = HospitalRecordRequest::with(['patient', 'requestedByUser', 'fulfilledByUser']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $requests = $query->latest('requested_at')->paginate(25)->withQueryString();

        return view('hospital.records.requests', compact('requests'));
    }

    /**
     * Mark a clinician's chart request as fulfilled (chart handed over).
     */
    public function fulfillRequest(Request $request, HospitalRecordRequest $recordRequest)
    {
        $this->requirePermission('records.request');

        if ($recordRequest->status === HospitalRecordRequest::STATUS_FULFILLED) {
            return back()->with('error', 'Request already fulfilled.');
        }

        $recordRequest->update([
            'status'       => HospitalRecordRequest::STATUS_FULFILLED,
            'fulfilled_by' => $request->user()->id,
            'fulfilled_at' => now(),
        ]);

        AuditTrail::record(
            'records.request.fulfill',
            $recordRequest->patient,
            $recordRequest->patient_id,
            [],
            [],
            ['request_id' => $recordRequest->id]
        );

        return back()->with('success', 'Request marked fulfilled.');
    }

    /**
     * Reject a clinician's chart request with a reason.
     */
    public function rejectRequest(Request $request, HospitalRecordRequest $recordRequest)
    {
        $this->requirePermission('records.request');

        $data = $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $recordRequest->update([
            'status'       => HospitalRecordRequest::STATUS_REJECTED,
            'fulfilled_by' => $request->user()->id,
            'fulfilled_at' => now(),
            'notes'        => $data['notes'],
        ]);

        AuditTrail::record(
            'records.request.reject',
            $recordRequest->patient,
            $recordRequest->patient_id,
            [],
            [],
            ['request_id' => $recordRequest->id, 'reason' => $data['notes']]
        );

        return back()->with('success', 'Request rejected.');
    }
}