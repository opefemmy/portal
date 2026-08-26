<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalStaffNote;
use Illuminate\Http\Request;

/**
 * Staff notes on a patient's chart.
 *
 * Any clinical or administrative staff can drop a time-stamped
 * note on a patient file. Notes are the "commentary" layer the
 * user wants on top of structured clinical data (prescriptions,
 * vitals, lab orders). A doctor can write "Nurse Mary should
 * recheck BP in 30 minutes", the nurse reads it on the chart,
 * timestamps the work, and the audit trail stays intact.
 */
class StaffNoteController extends Controller
{
    use EnforcesPermission;

    /**
     * Persist a new note on a patient file.
     */
    public function store(Request $request, HospitalPatient $patient)
    {
        $this->requirePermission('notes.create');

        $data = $request->validate([
            'audience'      => 'nullable|string|in:' . implode(',', HospitalStaffNote::AUDIENCES),
            'note_type'     => 'nullable|string|in:handover,instruction,commentary,alert',
            'body'          => 'required|string|max:2000',
            'appointment_id'=> 'nullable|exists:hospital_appointments,id',
            'is_pinned'     => 'nullable|boolean',
        ]);

        $note = HospitalStaffNote::create([
            'patient_id'     => $patient->id,
            'author_id'      => $request->user()->id,
            'appointment_id' => $data['appointment_id'] ?? null,
            'audience'       => $data['audience']      ?? 'all',
            'note_type'      => $data['note_type']     ?? 'handover',
            'body'           => $data['body'],
            'is_pinned'      => (bool) ($data['is_pinned'] ?? false),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'note' => $note->load('author')]);
        }

        return back()->with('success', 'Note added to patient file.');
    }

    /**
     * Delete a note (author or records officer only).
     */
    public function destroy(Request $request, HospitalStaffNote $note)
    {
        $this->requirePermission('notes.delete');

        if ($note->author_id !== $request->user()->id) {
            // Records officer can delete any note; everyone else
            // can only retract their own.
            $role = $request->user()->role?->slug;
            if (! in_array($role, ['medical_records_officer', 'cmd', 'super_admin', 'admin'], true)) {
                abort(403, 'Only the author or records officer can delete this note.');
            }
        }

        $note->delete();

        return back()->with('success', 'Note removed.');
    }

    /**
     * Toggle the pinned flag on a note (so a key instruction stays at
     * the top of the patient timeline).
     */
    public function togglePin(Request $request, HospitalStaffNote $note)
    {
        $this->requirePermission('notes.pin');

        $note->update(['is_pinned' => ! $note->is_pinned]);

        return back()->with('success', $note->is_pinned ? 'Note pinned.' : 'Note unpinned.');
    }
}
