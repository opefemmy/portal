@extends('layouts.app')

@section('title', 'Appointment #' . $appointment->id)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Appointment #{{ $appointment->id }}</h3>
            <small class="text-muted">
                {{ $appointment->patient?->full_name ?? '—' }} ·
                {{ optional($appointment->appointment_date)->format('d M Y, h:i A') ?? 'N/A' }}
            </small>
        </div>
        <a href="{{ route('hospital.appointments.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white"><i class="fas fa-user me-1"></i> Patient</div>
                <div class="card-body">
                    <p class="mb-2"><strong>Name:</strong> {{ $appointment->patient?->full_name ?? '—' }}</p>
                    <p class="mb-2"><strong>Number:</strong> {{ $appointment->patient?->patient_number ?? '—' }}</p>
                    <p class="mb-2"><strong>Phone:</strong> {{ $appointment->patient?->phone ?? '—' }}</p>
                    <p class="mb-0">
                        <a href="{{ route('hospital.patients.show', $appointment->patient_id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt"></i> View Patient
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white"><i class="fas fa-calendar me-1"></i> Appointment</div>
                <div class="card-body">
                    <p class="mb-2"><strong>Doctor:</strong> Dr. {{ $appointment->doctor?->full_name ?? '—' }}</p>
                    <p class="mb-2"><strong>Date / Time:</strong> {{ optional($appointment->appointment_date)->format('d M Y, h:i A') ?? '—' }}</p>
                    <p class="mb-2"><strong>Status:</strong>
                        <span class="badge bg-{{ $appointment->status === 'completed' ? 'success' : ($appointment->status === 'cancelled' ? 'danger' : ($appointment->status === 'in_progress' ? 'primary' : 'warning')) }}">
                            {{ ucfirst(str_replace('_', ' ', $appointment->status ?? 'scheduled')) }}
                        </span>
                    </p>
                    @if($appointment->complaint)
                        <p class="mb-2"><strong>Complaint:</strong> {{ $appointment->complaint }}</p>
                    @endif
                    @if($appointment->notes)
                        <p class="mb-0"><strong>Notes:</strong> {{ $appointment->notes }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-light"><i class="fas fa-cogs me-1"></i> Actions</div>
        <div class="card-body d-flex gap-2 flex-wrap">
            @if($appointment->status === 'scheduled')
                <form method="POST" action="{{ route('hospital.appointments.check-in', $appointment->id) }}">
                    @csrf
                    <button class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Check In</button>
                </form>
                <form method="POST" action="{{ route('hospital.appointments.start', $appointment->id) }}">
                    @csrf
                    <button class="btn btn-warning"><i class="fas fa-play"></i> Start Consultation</button>
                </form>
            @endif
            <a href="{{ route('hospital.consultations.create', ['appointment_id' => $appointment->id]) }}" class="btn btn-success">
                <i class="fas fa-stethoscope"></i> Open Consultation
            </a>
        </div>
    </div>

    @if($appointment->medicalRecords && $appointment->medicalRecords->count())
        <div class="card mt-3">
            <div class="card-header bg-secondary text-white"><i class="fas fa-notes-medical me-1"></i> Medical Records</div>
            <div class="card-body">
                <ul class="list-group">
                    @foreach($appointment->medicalRecords as $r)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ optional($r->consultation_date)->format('d M Y H:i') ?? '—' }} — {{ ucfirst($r->visit_type ?? 'visit') }}</span>
                            <a href="{{ route('hospital.consultations.show', $r->id) }}" class="btn btn-sm btn-link">View →</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection