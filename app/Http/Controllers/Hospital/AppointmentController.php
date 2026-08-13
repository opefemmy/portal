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

        return view('hospital.appointments.create', compact('patients', 'doctors', 'patientId'));
    }

    /**
     * Store a newly created appointment.
     */
    public function store(Request $request)
    {
        $this->requirePermission('appointments.create');

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:hospital_patients,id',
            'doctor_id' => 'required|exists:hospital_staff,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'complaint' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $appointment = HospitalAppointment::create([
            'scheduled_by' => auth()->id(),
            ...$request->all()
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'appointment_scheduled',
            'description' => "Scheduled appointment for patient ID: {$appointment->patient_id}",
            'entity_type' => 'hospital_appointments',
            'entity_id' => $appointment->id,
        ]);

        return redirect()->route('hospital.appointments.show', $appointment->id)
            ->with('success', 'Appointment scheduled successfully');
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