<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalStaffNote;
use Illuminate\Http\Request;

/**
 * End-of-day patient sign-out.
 *
 * The records officer closes out the day's visit for a patient:
 *   - marks the appointment completed
 *   - locks the chart from further edits (sign_out_locked_at)
 *   - drops a final staff note summarising the day's flow
 *
 * The lock is a soft block — `sign_out_locked_at` non-null on the
 * appointment; clinical editors (consultation.create) check this
 * and short-circuit with a "this patient was signed out" error.
 * The lock lifts when a new appointment for the patient is
 * scheduled.
 */
class SignOutController extends Controller
{
    use EnforcesPermission;

    /**
     * Sign the patient out for the day.
     */
    public function store(Request $request, HospitalAppointment $appointment)
    {
        $this->requirePermission('signout.complete');

        $data = $request->validate([
            'summary' => 'nullable|string|max:2000',
        ]);

        $appointment->update([
            'status'              => 'completed',
            'completed_at'        => now(),
            'sign_out_by'         => $request->user()->id,
            'sign_out_at'         => now(),
            'sign_out_summary'    => $data['summary'] ?? null,
        ]);

        // Drop a closing note on the patient chart so the audit
        // trail is complete.
        HospitalStaffNote::create([
            'patient_id'     => $appointment->patient_id,
            'author_id'      => $request->user()->id,
            'appointment_id' => $appointment->id,
            'audience'       => 'records',
            'note_type'      => 'handover',
            'body'           => 'Patient signed out for the day.'
                . ($data['summary'] ?? null ? ' Summary: ' . $data['summary'] : ''),
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'patient_signed_out',
            'description' => "Patient signed out at end of day for appointment: {$appointment->id}",
            'entity_type' => 'hospital_appointments',
            'entity_id' => $appointment->id,
        ]);

        return back()->with('success', 'Patient signed out for the day.');
    }
}
