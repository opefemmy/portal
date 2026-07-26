<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalVisit;
use App\Models\Hospital\ExternalPrescription;
use App\Models\Hospital\ExternalLabOrder;
use Illuminate\Http\Request;

class ExternalVisitController extends Controller
{
    /**
     * Edit/Manage a visit
     */
    public function edit(HospitalVisit $visit)
    {
        $visit->load(['patient', 'prescriptions', 'labOrders', 'doctor']);
        return view('hospital.external-visits.edit', compact('visit'));
    }

    /**
     * Update visit with diagnosis and treatment
     */
    public function update(Request $request, HospitalVisit $visit)
    {
        $request->validate([
            'diagnosis' => 'nullable|string',
            'treatment' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $visit->update([
            'diagnosis' => $request->diagnosis,
            'treatment' => $request->treatment,
            'status' => $request->status,
            'next_visit_date' => $request->next_visit_date,
            'next_visit_notes' => $request->next_visit_notes,
        ]);

        return back()->with('success', 'Visit updated successfully!');
    }

    /**
     * Add vital signs
     */
    public function addVitals(Request $request, HospitalVisit $visit)
    {
        $request->validate([
            'vital_signs_temperature' => 'nullable|string',
            'vital_signs_bp' => 'nullable|string',
            'vital_signs_pulse' => 'nullable|string',
            'vital_signs_respiration' => 'nullable|string',
            'vital_signs_oxygen' => 'nullable|string',
            'height' => 'nullable|string',
            'weight' => 'nullable|string',
        ]);

        $visit->update($request->only([
            'vital_signs_temperature', 'vital_signs_bp', 'vital_signs_pulse',
            'vital_signs_respiration', 'vital_signs_oxygen', 'height', 'weight'
        ]));

        return back()->with('success', 'Vitals recorded!');
    }

    /**
     * Add prescription
     */
    public function addPrescription(Request $request, HospitalVisit $visit)
    {
        $request->validate([
            'medication_name' => 'required|string',
            'dosage' => 'nullable|string',
            'frequency' => 'nullable|string',
            'duration' => 'nullable|string',
            'instructions' => 'nullable|string',
            'quantity' => 'nullable|integer',
        ]);

        ExternalPrescription::create([
            'visit_id' => $visit->id,
            'medication_name' => $request->medication_name,
            'dosage' => $request->dosage,
            'frequency' => $request->frequency,
            'duration' => $request->duration,
            'instructions' => $request->instructions,
            'quantity' => $request->quantity ?? 1,
        ]);

        return back()->with('success', 'Prescription added!');
    }

    /**
     * Add lab order
     */
    public function addLabOrder(Request $request, HospitalVisit $visit)
    {
        $request->validate([
            'test_name' => 'required|string',
            'test_type' => 'nullable|string',
            'urgency' => 'nullable|in:routine,urgent,emergency',
        ]);

        ExternalLabOrder::create([
            'visit_id' => $visit->id,
            'test_name' => $request->test_name,
            'test_type' => $request->test_type,
            'urgency' => $request->urgency ?? 'routine',
            'status' => 'pending',
        ]);

        return back()->with('success', 'Lab order added!');
    }

    /**
     * Complete visit
     */
    public function complete(HospitalVisit $visit)
    {
        $visit->update(['status' => 'completed']);

        // Create communication
        \App\Models\Hospital\ExternalCommunication::create([
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'staff_id' => auth()->id(),
            'type' => 'note',
            'subject' => 'Visit Completed',
            'message' => 'Visit ' . $visit->visit_number . ' completed. Diagnosis: ' . ($visit->diagnosis ?? 'Not recorded'),
        ]);

        return back()->with('success', 'Visit completed!');
    }
}
