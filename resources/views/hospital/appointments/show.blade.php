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
        <div class="card-header bg-light"><i class="fas fa-cogs me-1"></i> Patient Flow Actions</div>
        <div class="card-body d-flex gap-2 flex-wrap">
            {{-- Records-officer desk: certify the chart is on file. --}}
            @permission('appointments.certify')
                @if(in_array($appointment->status, ['scheduled', 'confirmed', 'checked_in'], true))
                    <form method="POST" action="{{ route('hospital.appointments.certify', $appointment->id) }}">
                        @csrf
                        <button class="btn btn-secondary" title="Records officer: confirm the patient's chart is on file.">
                            <i class="fas fa-folder-open"></i> Certify Chart
                        </button>
                    </form>
                @endif
            @endpermission

            {{-- Records-officer desk: assign an available doctor (auto if none pinned). --}}
            @permission('appointments.assign-doctor')
                @if(is_null($appointment->doctor_id) || empty($appointment->assigned_doctor_at))
                    <form method="POST" action="{{ route('hospital.appointments.assign-doctor', $appointment->id) }}">
                        @csrf
                        <button class="btn btn-info" title="Records officer: pick an available doctor on duty.">
                            <i class="fas fa-user-md"></i> Assign Doctor
                        </button>
                    </form>
                @endif
            @endpermission

            {{-- Nurse desk: stamp vitals. --}}
            @permission('appointments.vitals')
                @if($appointment->certified_at && is_null($appointment->vitals_recorded_at))
                    <form method="POST" action="{{ route('hospital.appointments.vitals', $appointment->id) }}">
                        @csrf
                        <button class="btn btn-primary" title="Nurse: vitals taken; hand to doctor.">
                            <i class="fas fa-thermometer-half"></i> Record Vitals
                        </button>
                    </form>
                @endif
            @endpermission

            @if($appointment->status === 'scheduled' || $appointment->status === 'records_certified')
                @permission('appointments.check-in')
                    <form method="POST" action="{{ route('hospital.appointments.check-in', $appointment->id) }}">
                        @csrf
                        <button class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Check In</button>
                    </form>
                @endpermission
            @endif

            @permission('appointments.start')
                @if(!is_null($appointment->doctor_id) && !is_null($appointment->vitals_recorded_at) && $appointment->status !== 'in_progress')
                    <form method="POST" action="{{ route('hospital.appointments.start', $appointment->id) }}">
                        @csrf
                        <button class="btn btn-warning"><i class="fas fa-play"></i> Start Consultation</button>
                    </form>
                @endif
            @endpermission

            @permission('consultations.create')
                <a href="{{ route('hospital.consultations.create', ['appointment_id' => $appointment->id]) }}" class="btn btn-success">
                    <i class="fas fa-stethoscope"></i> Open Consultation
                </a>
            @endpermission

            {{-- Records officer: sign the patient out at end of day. --}}
            @permission('signout.complete')
                @if(is_null($appointment->sign_out_at) && ! in_array($appointment->status, ['completed', 'cancelled'], true))
                    <form method="POST" action="{{ route('hospital.appointments.sign-out', $appointment->id) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-dark" title="Records officer: sign the patient out at end of day.">
                            <i class="fas fa-sign-out-alt"></i> Sign Out (End of Day)
                        </button>
                    </form>
                @endif
            @endpermission
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-light"><i class="fas fa-route me-1"></i> Patient-Flow Timeline</div>
        <div class="card-body">
            <ol class="list-group list-group-numbered">
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">Booked</div>
                        Scheduled by {{ $appointment->scheduledByUser?->name ?? 'system' }}
                        on {{ optional($appointment->created_at)->format('d M Y, h:i A') ?? '—' }}.
                    </div>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">Records Certified</div>
                        @if($appointment->certified_at)
                            Certified on {{ $appointment->certified_at->format('d M Y, h:i A') }}.
                        @else
                            <span class="text-muted">Awaiting records officer.</span>
                        @endif
                    </div>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">Doctor Assigned</div>
                        @if($appointment->doctor_id)
                            Dr. {{ $appointment->doctor?->full_name ?? '—' }}
                            @if($appointment->assigned_doctor_at)
                                on {{ $appointment->assigned_doctor_at->format('d M Y, h:i A') }}.
                            @endif
                        @else
                            <span class="text-muted">Awaiting records-officer assignment.</span>
                        @endif
                    </div>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">Vitals Recorded</div>
                        @if($appointment->vitals_recorded_at)
                            Recorded on {{ $appointment->vitals_recorded_at->format('d M Y, h:i A') }}.
                        @else
                            <span class="text-muted">Awaiting nurse.</span>
                        @endif
                    </div>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">Doctor Consultation</div>
                        @if($appointment->status === 'in_progress')
                            <span class="text-primary">In progress</span>
                        @elseif($appointment->status === 'completed')
                            Completed on {{ optional($appointment->completed_at)->format('d M Y, h:i A') ?? '—' }}.
                        @else
                            <span class="text-muted">Awaiting doctor.</span>
                        @endif
                    </div>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">End-of-Day Sign-Out</div>
                        @if($appointment->sign_out_at)
                            <span class="text-success">
                                Signed out on {{ $appointment->sign_out_at->format('d M Y, h:i A') }}.
                            </span>
                            @if($appointment->sign_out_summary)
                                <div class="small text-muted mt-1">{{ $appointment->sign_out_summary }}</div>
                            @endif
                        @elseif(in_array($appointment->status, ['completed', 'cancelled'], true))
                            <span class="text-muted">No sign-out recorded.</span>
                        @else
                            <span class="text-muted">Awaiting records officer.</span>
                        @endif
                    </div>
                </li>
            </ol>
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