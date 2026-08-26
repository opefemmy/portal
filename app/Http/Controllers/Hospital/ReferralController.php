<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalLabRequest;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalPrescription;
use App\Models\Hospital\HospitalStaff;
use App\Models\Hospital\HospitalStaffNote;
use Illuminate\Http\Request;

/**
 * Doctor referrals — the actions a doctor can take after seeing a
 * patient: send to lab, send to pharmacy, send to x-ray, send back
 * to a nurse, set a follow-up date.
 *
 * Each referral also drops a timestamped staff note on the patient
 * chart so the receiving team sees it on their queue without having
 * to leave their normal surface.
 */
class ReferralController extends Controller
{
    use EnforcesPermission;

    /**
     * Send the patient to the lab.
     */
    public function toLab(Request $request, HospitalPatient $patient)
    {
        $this->requirePermission('referrals.send.lab');

        $data = $request->validate([
            'appointment_id' => 'nullable|exists:hospital_appointments,id',
            'test_type'      => 'required|string|max:150',
            'clinical_notes' => 'nullable|string|max:2000',
        ]);

        HospitalLabRequest::create([
            'patient_id'     => $patient->id,
            'doctor_id'      => $this->resolveDoctorId($request),
            'medical_record_id' => null,
            'test_type'      => $data['test_type'],
            'clinical_notes' => $data['clinical_notes'] ?? null,
            'status'         => 'pending',
            'requested_at'   => now(),
        ]);

        $this->dropNote($patient, $request, $data, 'lab',
            "Sent to lab: {$data['test_type']}.",
            $data['appointment_id'] ?? null,
        );

        return back()->with('success', "Patient sent to lab for {$data['test_type']}.");
    }

    /**
     * Send the patient to pharmacy with a prescription.
     */
    public function toPharmacy(Request $request, HospitalPatient $patient)
    {
        $this->requirePermission('referrals.send.pharmacy');

        $data = $request->validate([
            'appointment_id' => 'nullable|exists:hospital_appointments,id',
            'drug_name'      => 'required|string|max:200',
            'dosage'         => 'required|string|max:100',
            'frequency'      => 'required|string|max:100',
            'duration'       => 'required|string|max:100',
            'notes'          => 'nullable|string|max:2000',
        ]);

        $rx = HospitalPrescription::create([
            'patient_id' => $patient->id,
            'doctor_id'  => $this->resolveDoctorId($request),
            'notes'      => $data['notes'] ?? null,
            'status'     => 'pending',
        ]);

        $this->dropNote($patient, $request, $data, 'pharmacy',
            "Prescribed: {$data['drug_name']} ({$data['dosage']}, {$data['frequency']}, {$data['duration']}).",
            $data['appointment_id'] ?? null,
        );

        return back()->with('success', "Prescription queued at pharmacy: {$data['drug_name']}.");
    }

    /**
     * Send the patient to radiology (x-ray).
     */
    public function toRadiology(Request $request, HospitalPatient $patient)
    {
        $this->requirePermission('referrals.send.radiology');

        $data = $request->validate([
            'appointment_id' => 'nullable|exists:hospital_appointments,id',
            'imaging_type'   => 'required|string|max:150',
            'clinical_notes' => 'nullable|string|max:2000',
        ]);

        // Use a staff-note as the radiology queue entry — we don't
        // have a dedicated radiology_requests table, so the note is
        // the lightweight handoff (matches how pharmacy/lab work).
        $this->dropNote($patient, $request, $data, 'radiology',
            "Sent to radiology: {$data['imaging_type']}."
            . ($data['clinical_notes'] ?? null ? ' ' . $data['clinical_notes'] : ''),
            $data['appointment_id'] ?? null,
            'instruction',
        );

        return back()->with('success', "Patient sent to radiology: {$data['imaging_type']}.");
    }

    /**
     * Send the patient back to the nurse for a procedure / recheck.
     */
    public function toNurse(Request $request, HospitalPatient $patient)
    {
        $this->requirePermission('referrals.send.nurse');

        $data = $request->validate([
            'appointment_id' => 'nullable|exists:hospital_appointments,id',
            'instruction'    => 'required|string|max:2000',
        ]);

        $this->dropNote($patient, $request, $data, 'nurse',
            "Returned to nurse: {$data['instruction']}",
            $data['appointment_id'] ?? null,
            'instruction',
        );

        return back()->with('success', 'Patient returned to nurse.');
    }

    /**
     * Schedule a follow-up appointment for the patient.
     */
    public function followUp(Request $request, HospitalPatient $patient)
    {
        $this->requirePermission('appointments.create');

        $data = $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'doctor_id'        => 'nullable|exists:hospital_staff,id',
            'notes'            => 'nullable|string|max:500',
        ]);

        $appt = HospitalAppointment::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => $data['doctor_id'] ?? null,
            'scheduled_by'     => $request->user()->id,
            'assigned_by'      => ! empty($data['doctor_id']) ? $request->user()->id : null,
            'assigned_doctor_at' => ! empty($data['doctor_id']) ? now() : null,
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $data['appointment_time'],
            'status'           => 'scheduled',
            'notes'            => $data['notes'] ?? null,
        ]);

        $this->dropNote($patient, $request, $data, 'all',
            "Follow-up appointment scheduled for {$data['appointment_date']} at {$data['appointment_time']}.",
            $appt->id,
        );

        return back()->with('success', "Follow-up scheduled for {$data['appointment_date']}.");
    }

    /**
     * Drop a staff note on the patient file so the receiving team
     * sees the referral without leaving their queue.
     */
    private function dropNote(
        HospitalPatient $patient,
        Request $request,
        array $data,
        string $audience,
        string $body,
        ?int $appointmentId,
        string $noteType = 'instruction',
    ): void {
        HospitalStaffNote::create([
            'patient_id'     => $patient->id,
            'author_id'      => $request->user()->id,
            'appointment_id' => $appointmentId,
            'audience'       => $audience,
            'note_type'      => $noteType,
            'body'           => $body,
        ]);
    }

    /**
     * Resolve the doctor id for outgoing referrals. If the actor is a
     * doctor, use their staff row; otherwise let the queue pick one
     * up later (the doctor_id column on the new lab/rx row is a
     * hint, not the assigned doctor).
     */
    private function resolveDoctorId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }
        $staff = HospitalStaff::where('user_id', $user->id)
            ->where('staff_type', 'doctor')
            ->first();
        return $staff?->id;
    }
}
