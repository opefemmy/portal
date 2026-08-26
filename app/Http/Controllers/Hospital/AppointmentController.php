<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalStaff;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    use EnforcesPermission;

    /**
     * Display appointment queue.
     */
    public function queue()
    {
        $this->requirePermission('appointments.view');

        $appointments = HospitalAppointment::with(['patient', 'doctor'])
            ->whereIn('status', ['scheduled', 'confirmed', 'checked_in', 'in_progress'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(20);

        return view('hospital.appointments.queue', compact('appointments'));
    }

    /**
     * Display a listing of appointments.
     */
    public function index(Request $request)
    {
        $this->requirePermission('appointments.view');

        $query = HospitalAppointment::with(['patient', 'doctor', 'scheduledByUser']);

        if ($request->date) {
            $query->whereDate('appointment_date', $request->date);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->paginate(20);

        $doctors = HospitalStaff::where('staff_type', 'doctor')
            ->where('is_active', true)
            ->where('is_available', true)
            ->get();

        return view('hospital.appointments.index', compact('appointments', 'doctors'));
    }

    /**
     * Show the form for creating a new appointment.
     *
     * `patientId` is a pre-fill from the patient show page
     * (`?patient_id=…`). The doctor dropdown is intentionally omitted
     * on a FRESH patient (no prior appointment history): per the
     * patient flow, a first-time patient cannot pick a doctor —
     * they walk in, the records officer certifies the chart, then a
     * doctor on duty is assigned. Subsequent appointments for a
     * returning patient still default to "any doctor on duty" but
     * can pin a preferred doctor if one was assigned previously.
     */
    public function create(Request $request)
    {
        $this->requirePermission('appointments.create');

        $patientId = $request->patient_id;
        $patients = HospitalPatient::where('is_active', true)->get();
        $doctors = HospitalStaff::where('staff_type', 'doctor')
            ->where('is_active', true)
            ->where('is_available', true)
            ->get();

        // Decide whether to expose the doctor dropdown. We hide it
        // when a patientId is pre-filled AND the patient has no
        // prior appointment history (truly a first-time visit).
        // Returning patients keep the dropdown so they can request
        // a specific doctor they've seen before.
        $isFirstVisit = false;
        if ($patientId) {
            $priorCount = HospitalAppointment::where('patient_id', $patientId)->count();
            $isFirstVisit = $priorCount === 0;
        }

        return view('hospital.appointments.create', compact(
            'patients', 'doctors', 'patientId', 'isFirstVisit'
        ));
    }

    /**
     * Store a newly created appointment.
     *
     * Doctor assignment is OPTIONAL on creation. On a first visit
     * the records officer desk picks a doctor later (via the
     * `appointments/{id}/assign-doctor` endpoint) once the chart
     * has been certified. Returning patients can still pin a
     * specific doctor up front.
     */
    public function store(Request $request)
    {
        $this->requirePermission('appointments.create');

        $validator = Validator::make($request->all(), [
            'patient_id'       => 'required|exists:hospital_patients,id',
            'doctor_id'        => 'nullable|exists:hospital_staff,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'complaint'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['scheduled_by'] = auth()->id();

        // When a doctor IS pinned at booking, capture that as an
        // assignment (records officer / system assigns the doctor
        // at scheduling time, not the patient).
        if (!empty($data['doctor_id'])) {
            $data['assigned_by'] = auth()->id();
            $data['assigned_doctor_at'] = now();
        }

        $appointment = HospitalAppointment::create($data);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'appointment_scheduled',
            'description' => "Scheduled appointment for patient ID: {$appointment->patient_id}",
            'entity_type' => 'hospital_appointments',
            'entity_id' => $appointment->id,
        ]);

        return redirect()->route('hospital.appointments.show', $appointment->id)
            ->with('success', 'Appointment scheduled. The records officer will certify the chart and assign a doctor.');
    }

    /**
     * Display the specified appointment.
     */
    public function show(HospitalAppointment $appointment)
    {
        $this->requirePermission('appointments.view');

        $appointment->load(['patient', 'doctor', 'scheduledByUser', 'medicalRecords']);

        return view('hospital.appointments.show', compact('appointment'));
    }

    /**
     * Show the form for editing the specified appointment.
     */
    public function edit(HospitalAppointment $appointment)
    {
        $this->requirePermission('appointments.update');

        $patients = HospitalPatient::where('is_active', true)->get();
        $doctors = HospitalStaff::where('staff_type', 'doctor')
            ->where('is_active', true)
            ->get();

        return view('hospital.appointments.edit', compact('appointment', 'patients', 'doctors'));
    }

    /**
     * Update the specified appointment.
     */
    public function update(Request $request, HospitalAppointment $appointment)
    {
        $this->requirePermission('appointments.update');

        $data = $request->validate([
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
            'doctor_id'        => 'required|exists:hospital_staff,id',
            'complaint'        => 'nullable|string',
            'notes'            => 'nullable|string',
            'status'           => 'nullable|in:scheduled,confirmed,checked_in,in_progress,completed,cancelled',
        ]);

        $appointment->update($data);

        return redirect()->route('hospital.appointments.show', $appointment)
            ->with('success', 'Appointment updated.');
    }

    /**
     * Check in patient.
     */
    public function checkIn(HospitalAppointment $appointment)
    {
        $this->requirePermission('appointments.check-in');
        if ($appointment->status !== 'confirmed' && $appointment->status !== 'scheduled') {
            return redirect()->back()->with('error', 'Appointment cannot be checked in');
        }

        $appointment->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'appointment_checked_in',
            'description' => "Patient checked in for appointment: {$appointment->id}",
            'entity_type' => 'hospital_appointments',
            'entity_id' => $appointment->id,
        ]);

        return redirect()->back()->with('success', 'Patient checked in successfully');
    }

    /**
     * Start consultation.
     */
    public function startConsultation(HospitalAppointment $appointment)
    {
        $this->requirePermission('appointments.start');
        $appointment->update(['status' => 'in_progress']);

        return redirect()->route('hospital.consultations.create', ['appointment_id' => $appointment->id]);
    }

    /**
     * Records-officer certification.
     *
     * Confirms the patient's chart is on file and ready for clinical
     * staff to act on. Once certified the appointment is moved to the
     * nurse queue (status `records_certified`).
     *
     * Idempotent: re-certifying a chart just refreshes the timestamp.
     */
    public function certify(HospitalAppointment $appointment)
    {
        $this->requirePermission('appointments.certify');

        $appointment->update([
            'certified_by' => auth()->id(),
            'certified_at' => now(),
            'status'       => 'records_certified',
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'appointment_certified',
            'description' => "Records officer certified appointment: {$appointment->id}",
            'entity_type' => 'hospital_appointments',
            'entity_id' => $appointment->id,
        ]);

        return redirect()->route('hospital.appointments.show', $appointment)
            ->with('success', 'Chart certified. Patient is now in the nurse queue.');
    }

    /**
     * Records-officer / system assigns a doctor to the appointment.
     *
     * If `doctor_id` is omitted, the controller picks the next
     * available doctor on duty (fewest in-progress appointments).
     */
    public function assignDoctor(Request $request, HospitalAppointment $appointment)
    {
        $this->requirePermission('appointments.assign-doctor');

        $doctorId = $request->input('doctor_id') ?: $this->pickAvailableDoctorId();

        if (! $doctorId) {
            return back()->with('error', 'No available doctor on duty. Try again later.');
        }

        $appointment->update([
            'doctor_id'           => $doctorId,
            'assigned_by'         => auth()->id(),
            'assigned_doctor_at'  => now(),
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'appointment_doctor_assigned',
            'description' => "Doctor assigned to appointment: {$appointment->id} → staff_id {$doctorId}",
            'entity_type' => 'hospital_appointments',
            'entity_id' => $appointment->id,
        ]);

        return redirect()->route('hospital.appointments.show', $appointment)
            ->with('success', 'Doctor assigned.');
    }

    /**
     * Nurse logs vitals on the appointment.
     *
     * This stamps the appointment (records who took vitals + when)
     * and then pivots the appointment into the doctor queue
     * (status `awaiting_doctor`). Vitals themselves are written
     * through the existing HospitalVitalSign table by the nurse
     * dashboard form; here we just record that the handoff happened.
     */
    public function recordVitals(HospitalAppointment $appointment)
    {
        $this->requirePermission('appointments.vitals');

        // Flow guard: nurse can only vitals AFTER the records officer
        // has certified the chart is on file. Without this guard a
        // patient could skip straight from booking to the nurse's
        // desk, which is the exact leak this patient-flow slice
        // closes.
        if (is_null($appointment->certified_at)) {
            return back()->withErrors([
                'flow' => 'Records officer must certify the chart before vitals can be taken.',
            ]);
        }

        $appointment->update([
            'vitals_recorded_by' => auth()->id(),
            'vitals_recorded_at' => now(),
            'status'             => 'awaiting_doctor',
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'appointment_vitals_taken',
            'description' => "Nurse recorded vitals for appointment: {$appointment->id}",
            'entity_type' => 'hospital_appointments',
            'entity_id' => $appointment->id,
        ]);

        return redirect()->route('hospital.appointments.show', $appointment)
            ->with('success', 'Vitals recorded. Patient is now in the doctor queue.');
    }

    /**
     * Pick the doctor with the lightest in-progress load who is also
     * marked `is_available = true` on the staff table. Returns null
     * if no doctor is on duty.
     */
    private function pickAvailableDoctorId(): ?int
    {
        return HospitalStaff::where('staff_type', 'doctor')
            ->where('is_active', true)
            ->where('is_available', true)
            ->withCount(['appointments as in_progress_count' => function ($q) {
                $q->whereIn('status', ['in_progress', 'awaiting_doctor', 'records_certified']);
            }])
            ->orderBy('in_progress_count')
            ->orderBy('id')
            ->value('id');
    }

    /**
     * Complete appointment.
     */
    public function complete(Request $request, HospitalAppointment $appointment)
    {
        $this->requirePermission('appointments.update');

        $appointment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $request->notes,
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'appointment_completed',
            'description' => "Completed appointment: {$appointment->id}",
            'entity_type' => 'hospital_appointments',
            'entity_id' => $appointment->id,
        ]);

        return redirect()->route('hospital.appointments.index')
            ->with('success', 'Appointment completed successfully');
    }

    /**
     * Cancel appointment.
     */
    public function cancel(Request $request, HospitalAppointment $appointment)
    {
        $this->requirePermission('appointments.update');
        $appointment->update([
            'status' => 'cancelled',
            'notes' => $request->reason,
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'appointment_cancelled',
            'description' => "Cancelled appointment: {$appointment->id}",
            'entity_type' => 'hospital_appointments',
            'entity_id' => $appointment->id,
        ]);

        return redirect()->route('hospital.appointments.index')
            ->with('success', 'Appointment cancelled');
    }

    /**
     * Get available time slots for a doctor on a given date.
     */
    public function availableSlots(Request $request)
    {
        $this->requirePermission('appointments.view');
        $date = $request->date;
        $doctorId = $request->doctor_id;

        // Get all appointments for the doctor on that date
        $bookedSlots = HospitalAppointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->pluck('appointment_time')
            ->toArray();

        // Generate available slots (9 AM to 5 PM, 30-minute intervals)
        $allSlots = [];
        for ($hour = 9; $hour < 17; $hour++) {
            $allSlots[] = sprintf('%02d:00', $hour);
            $allSlots[] = sprintf('%02d:30', $hour);
        }

        $availableSlots = array_diff($allSlots, $bookedSlots);

        return response()->json(array_values($availableSlots));
    }
}