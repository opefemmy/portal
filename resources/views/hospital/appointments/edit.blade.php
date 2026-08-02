@extends('layouts.app')

@section('title', 'Edit Appointment')

@section('content')
<div class="page-header">
    <h4>Edit Appointment</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('hospital.appointments.update', $appointment) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Patient</label>
                    <input type="text" class="form-control" value="{{ $appointment->patient->first_name ?? '' }} {{ $appointment->patient->last_name ?? '' }}" disabled>
                    <small class="text-muted">Patient cannot be changed for an existing appointment.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Doctor *</label>
                    <select name="doctor_id" class="form-select" required>
                        <option value="">Select Doctor</option>
                        @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}" {{ old('doctor_id', $appointment->doctor_id) == $doctor->id ? 'selected' : '' }}>
                            Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Appointment Date *</label>
                    <input type="date" name="appointment_date" class="form-control" value="{{ old('appointment_date', optional($appointment->appointment_date)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Time *</label>
                    <input type="time" name="appointment_time" class="form-control" value="{{ old('appointment_time', $appointment->appointment_time) }}" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['scheduled','confirmed','checked_in','in_progress','completed','cancelled'] as $status)
                        <option value="{{ $status }}" {{ old('status', $appointment->status) === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Complaint/Reason</label>
                <textarea name="complaint" class="form-control" rows="3">{{ old('complaint', $appointment->complaint) }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $appointment->notes) }}</textarea>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary" title="Save appointment changes">Save Changes</button>
                <a href="{{ route('hospital.appointments.show', $appointment) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection