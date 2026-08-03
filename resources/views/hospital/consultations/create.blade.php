@extends('layouts.app')

@section('title', 'Create Consultation')

@section('content')
<div class="container-fluid">
    <h3 class="mb-3"><i class="fas fa-stethoscope me-2"></i>New Consultation</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('hospital.consultations.store') }}">
        @csrf
        <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <input type="hidden" name="doctor_id" value="{{ $doctors->first()->id ?? auth()->user()?->hospitalStaff?->id ?? '' }}">

        <div class="card mb-3">
            <div class="card-header bg-primary text-white"><i class="fas fa-user me-1"></i> Patient</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><strong>Name:</strong> {{ $patient->full_name }}</div>
                    <div class="col-md-3"><strong>Number:</strong> {{ $patient->patient_number }}</div>
                    <div class="col-md-3"><strong>Gender:</strong> {{ ucfirst($patient->gender) }}</div>
                    <div class="col-md-3"><strong>Age:</strong> {{ optional($patient->date_of_birth)->age ?? '—' }}</div>
                </div>
            </div>
        </div>

        @if($medicalHistory && $medicalHistory->count())
            <div class="card mb-3">
                <div class="card-header bg-light"><i class="fas fa-history me-1"></i> Recent Medical History</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Date</th><th>Doctor</th><th>Chief complaint</th></tr></thead>
                        <tbody>
                            @foreach($medicalHistory as $h)
                                <tr>
                                    <td>{{ optional($h->consultation_date)->format('d M Y') ?? '—' }}</td>
                                    <td>Dr. {{ $h->doctor?->full_name ?? '—' }}</td>
                                    <td>{{ $h->chief_complaint ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-header bg-success text-white"><i class="fas fa-notes-medical me-1"></i> Clinical Details</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Visit Type</label>
                        <select name="visit_type" class="form-select" required>
                            <option value="new">New</option>
                            <option value="follow_up">Follow Up</option>
                            <option value="emergency">Emergency</option>
                            <option value="referral">Referral</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Doctor</label>
                        <select name="doctor_id_explicit" class="form-select" disabled>
                            @foreach($doctors as $d)
                                <option>{{ $d->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label">Chief Complaint</label>
                        <textarea name="chief_complaint" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label">Symptoms</label>
                        <textarea name="symptoms" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label">Examination Findings</label>
                        <textarea name="examination_findings" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label">Doctor Notes</label>
                        <textarea name="doctor_notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label">Treatment Plan</label>
                        <textarea name="treatment_plan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('hospital.appointments.show', $appointment->id) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Consultation</button>
            </div>
        </div>
    </form>
</div>
@endsection