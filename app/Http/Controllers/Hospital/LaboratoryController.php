<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalLabRequest;
use App\Models\Hospital\HospitalLabResult;
use App\Models\Hospital\HospitalPatient;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LaboratoryController extends Controller
{
    /**
     * Display lab requests.
     */
    public function index(Request $request)
    {
        $query = HospitalLabRequest::with(['patient', 'doctor']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date) {
            $query->whereDate('requested_at', $request->date);
        }

        $requests = $query->orderBy('requested_at', 'desc')->paginate(20);

        return view('hospital.lab.requests', compact('requests'));
    }

    /**
     * Show lab request details.
     */
    public function show(HospitalLabRequest $labRequest)
    {
        $labRequest->load(['patient', 'doctor', 'results', 'medicalRecord']);

        return view('hospital.lab.request-show', compact('labRequest'));
    }

    /**
     * Collect sample.
     */
    public function collectSample(HospitalLabRequest $labRequest)
    {
        if ($labRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Sample already collected');
        }

        $labRequest->update(['status' => 'sample_collected']);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'lab_sample_collected',
            'description' => "Sample collected for lab request #{$labRequest->id}",
            'entity_type' => 'hospital_lab_requests',
            'entity_id' => $labRequest->id,
        ]);

        return redirect()->back()->with('success', 'Sample collected');
    }

    /**
     * Start processing.
     */
    public function startProcessing(HospitalLabRequest $labRequest)
    {
        if (!in_array($labRequest->status, ['pending', 'sample_collected'])) {
            return redirect()->back()->with('error', 'Cannot start processing');
        }

        $labRequest->update(['status' => 'in_progress']);

        return redirect()->back()->with('success', 'Processing started');
    }

    /**
     * Record results.
     */
    public function recordResults(Request $request, HospitalLabRequest $labRequest)
    {
        $validator = Validator::make($request->all(), [
            'results' => 'required|array',
            'results.*.test_name' => 'required|string',
            'results.*.result' => 'required|string',
            'results.*.unit' => 'nullable|string',
            'results.*.reference_range' => 'nullable|string',
            'results.*.status' => 'required|in:normal,abnormal,critical',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        foreach ($request->results as $result) {
            HospitalLabResult::create([
                'lab_request_id' => $labRequest->id,
                'recorded_by' => auth()->user()->hospitalStaff->id ?? null,
                'test_name' => $result['test_name'],
                'result' => $result['result'],
                'unit' => $result['unit'] ?? null,
                'reference_range' => $result['reference_range'] ?? null,
                'status' => $result['status'],
                'notes' => $result['notes'] ?? null,
                'recorded_at' => now(),
            ]);
        }

        $labRequest->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'lab_results_recorded',
            'description' => "Recorded results for lab request #{$labRequest->id}",
            'entity_type' => 'hospital_lab_requests',
            'entity_id' => $labRequest->id,
        ]);

        return redirect()->route('hospital.lab.show', $labRequest->id)
            ->with('success', 'Results recorded successfully');
    }

    /**
     * Cancel request.
     */
    public function cancel(Request $request, HospitalLabRequest $labRequest)
    {
        $labRequest->update([
            'status' => 'cancelled',
            'notes' => $request->reason,
        ]);

        return redirect()->back()->with('success', 'Request cancelled');
    }

    /**
     * My lab results (for patients).
     */
    public function myResults(HospitalPatient $patient)
    {
        $labRequests = HospitalLabRequest::where('patient_id', $patient->id)
            ->with('results', 'doctor')
            ->orderBy('requested_at', 'desc')
            ->paginate(20);

        return view('hospital.patients.lab-results', compact('patient', 'labRequests'));
    }
}