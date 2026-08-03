<?php

namespace App\Services\Hospital;

use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalPrescription;
use App\Models\Hospital\HospitalLabRequest;
use App\Models\Hospital\HospitalVitalSign;
use App\Models\Hospital\HospitalMedicalRecord;
use App\Models\Hospital\HospitalReferral;
use App\Models\Hospital\HospitalVisit;
use App\Models\Hospital\HospitalAdmission;
use App\Models\Hospital\HospitalClinicalNote;

/**
 * Aggregates every clinical event for a patient into a single
 * chronological timeline.
 *
 * Uses eager-loaded batched queries — no N+1 — and merges them in PHP
 * rather than running UNION queries (easier to extend with new event types).
 */
class PatientTimelineService
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function for(HospitalPatient $patient, int $limit = 50): array
    {
        $events = [];

        // Appointments
        HospitalAppointment::with(['staff'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('appointment_date')
            ->limit($limit)
            ->get()
            ->each(function (HospitalAppointment $a) use (&$events) {
                $events[] = [
                    'when'       => $a->appointment_date,
                    'type'       => 'appointment',
                    'label'      => 'Appointment',
                    'icon'       => 'fas fa-calendar-check',
                    'color'      => 'primary',
                    'actor'      => $a->staff?->full_name ?? '—',
                    'summary'    => ucfirst($a->status) . ' · ' . ($a->complaint ?? 'No complaint recorded'),
                    'detail_url' => route('hospital.appointments.show', $a),
                ];
            });

        // Vitals
        HospitalVitalSign::with(['staff'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->each(function (HospitalVitalSign $v) use (&$events) {
                $events[] = [
                    'when'    => $v->created_at,
                    'type'    => 'vital',
                    'label'   => 'Vital Signs',
                    'icon'    => 'fas fa-heartbeat',
                    'color'   => 'danger',
                    'actor'   => $v->staff?->full_name ?? '—',
                    'summary' => sprintf(
                        'Temp %s°C · BP %s/%s · Pulse %s · SpO₂ %s%%',
                        $v->temperature ?? '—',
                        $v->blood_pressure_systolic ?? '—',
                        $v->blood_pressure_diastolic ?? '—',
                        $v->pulse ?? '—',
                        $v->oxygen_level ?? '—'
                    ),
                ];
            });

        // Prescriptions
        HospitalPrescription::with(['staff'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->each(function (HospitalPrescription $p) use (&$events) {
                $events[] = [
                    'when'    => $p->created_at,
                    'type'    => 'prescription',
                    'label'   => 'Prescription',
                    'icon'    => 'fas fa-pills',
                    'color'   => 'warning',
                    'actor'   => $p->staff?->full_name ?? '—',
                    'summary' => ucfirst($p->status) . ' prescription',
                ];
            });

        // Lab requests
        HospitalLabRequest::with(['staff'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('requested_at')
            ->limit($limit)
            ->get()
            ->each(function (HospitalLabRequest $r) use (&$events) {
                $events[] = [
                    'when'    => $r->requested_at,
                    'type'    => 'lab',
                    'label'   => 'Lab Request',
                    'icon'    => 'fas fa-vial',
                    'color'   => 'info',
                    'actor'   => $r->staff?->full_name ?? '—',
                    'summary' => $r->test_type . ' · ' . ucfirst($r->status),
                ];
            });

        // Medical records / consultations
        HospitalMedicalRecord::with(['staff'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('consultation_date')
            ->limit($limit)
            ->get()
            ->each(function (HospitalMedicalRecord $r) use (&$events) {
                $events[] = [
                    'when'    => $r->consultation_date,
                    'type'    => 'consultation',
                    'label'   => 'Consultation',
                    'icon'    => 'fas fa-stethoscope',
                    'color'   => 'success',
                    'actor'   => $r->staff?->full_name ?? '—',
                    'summary' => $r->chief_complaint ?? 'Consultation entry',
                ];
            });

        // Clinical notes (SOAP etc.)
        HospitalClinicalNote::with(['staff'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->each(function (HospitalClinicalNote $n) use (&$events) {
                $events[] = [
                    'when'    => $n->created_at,
                    'type'    => 'note',
                    'label'   => 'Clinical Note (' . strtoupper($n->note_type) . ')',
                    'icon'    => 'fas fa-notes-medical',
                    'color'   => 'secondary',
                    'actor'   => $n->staff?->full_name ?? '—',
                    'summary' => $n->signed_at
                        ? 'Signed by ' . $n->signed_by_name
                        : 'Draft',
                ];
            });

        // Referrals
        HospitalReferral::with(['referrer', 'referredTo'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->each(function (HospitalReferral $r) use (&$events) {
                $events[] = [
                    'when'    => $r->created_at,
                    'type'    => 'referral',
                    'label'   => 'Referral',
                    'icon'    => 'fas fa-share-square',
                    'color'   => 'dark',
                    'actor'   => $r->referrer?->full_name ?? '—',
                    'summary' => '→ ' . ($r->referredTo?->full_name ?? 'External') . ' · ' . ucfirst($r->status ?? 'pending'),
                ];
            });

        // Visits
        HospitalVisit::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->each(function (HospitalVisit $v) use (&$events) {
                $events[] = [
                    'when'    => $v->created_at,
                    'type'    => 'visit',
                    'label'   => 'Visit',
                    'icon'    => 'fas fa-walking',
                    'color'   => 'primary',
                    'actor'   => '—',
                    'summary' => ucfirst($v->status ?? 'recorded'),
                ];
            });

        // Admissions
        HospitalAdmission::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->each(function (HospitalAdmission $a) use (&$events) {
                $events[] = [
                    'when'    => $a->created_at,
                    'type'    => 'admission',
                    'label'   => 'Admission',
                    'icon'    => 'fas fa-procedures',
                    'color'   => 'danger',
                    'actor'   => '—',
                    'summary' => ucfirst($a->status ?? 'admitted'),
                ];
            });

        // Sort descending by date
        usort($events, function ($a, $b) {
            return ($b['when']?->timestamp ?? 0) <=> ($a['when']?->timestamp ?? 0);
        });

        return array_slice($events, 0, $limit);
    }
}