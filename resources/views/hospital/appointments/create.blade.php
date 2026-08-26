@extends('layouts.app')

@section('title', 'Create Appointment')

@section('content')
<div class="page-header">
    <h4>Create Appointment</h4>
</div>

@if($isFirstVisit ?? false)
    <div class="alert alert-info d-flex align-items-start gap-2">
        <i class="fas fa-info-circle mt-1"></i>
        <div>
            <strong>First-visit patient flow.</strong>
            The patient cannot pick a doctor — once they arrive at the records-officer desk,
            the records officer certifies the chart and assigns a doctor on duty.
            You can leave the doctor field blank; the records officer will assign one.
        </div>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('hospital.appointments.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Patient *</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select Patient</option>
                        @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ (string)$patientId === (string)$patient->id ? 'selected' : '' }}>
                            {{ $patient->first_name }} {{ $patient->last_name }} - {{ $patient->phone }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Doctor
                        @if($isFirstVisit ?? false)
                            <span class="text-muted small">(optional — assigned by records officer)</span>
                        @else
                            <span class="text-muted small">(optional)</span>
                        @endif
                    </label>
                    <select name="doctor_id" class="form-select">
                        <option value="">Any available doctor on duty</option>
                        @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}">Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Appointment Date *</label>
                    <input type="date" name="appointment_date" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Time *</label>
                    <input type="time" name="appointment_time" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Complaint/Reason</label>
                <textarea name="complaint" class="form-control" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Create Appointment</button>
                <a href="{{ route('hospital.appointments.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
