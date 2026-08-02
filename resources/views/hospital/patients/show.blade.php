@extends('layouts.app')

@section('title', 'Patient: ' . $patient->full_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">
                <i class="fas fa-user-injured me-2"></i>{{ $patient->full_name }}
                <span class="badge bg-primary ms-2">{{ $patient->patient_number }}</span>
            </h3>
            <small class="text-muted">Registered {{ $patient->created_at->format('d M Y') }} &middot; {{ ucfirst($patient->patient_type ?? 'patient') }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hospital.patients.timeline', $patient->id) }}" class="btn btn-outline-secondary" title="View full medical history timeline">
                <i class="fas fa-history"></i> Timeline
            </a>
            <a href="{{ route('hospital.patients.edit', $patient->id) }}" class="btn btn-warning" title="Edit patient details">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('hospital.appointments.create', ['patient_id' => $patient->id]) }}" class="btn btn-success" title="Schedule a new appointment for this patient">
                <i class="fas fa-calendar-plus"></i> New Appointment
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Patient Information Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Personal Info</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted">Full Name</td><td class="text-end fw-bold">{{ $patient->full_name }}</td></tr>
                        <tr><td class="text-muted">Patient No.</td><td class="text-end">{{ $patient->patient_number }}</td></tr>
                        <tr><td class="text-muted">Gender</td><td class="text-end">{{ ucfirst($patient->gender ?? 'N/A') }}</td></tr>
                        <tr><td class="text-muted">Date of Birth</td><td class="text-end">{{ $patient->date_of_birth ? $patient->date_of_birth->format('d M Y') : 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Age</td><td class="text-end">{{ $patient->age ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Phone</td><td class="text-end">{{ $patient->phone }}</td></tr>
                        <tr><td class="text-muted">Email</td><td class="text-end">{{ $patient->email ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Address</td><td class="text-end">{{ $patient->address ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Medical Info</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted">Blood Group</td><td class="text-end">{{ $patient->blood_group ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Genotype</td><td class="text-end">{{ $patient->genotype ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Allergies</td><td class="text-end">{{ $patient->allergies ?? 'None recorded' }}</td></tr>
                        <tr><td class="text-muted">Chronic Conditions</td><td class="text-end">{{ $patient->chronic_conditions ?? 'None recorded' }}</td></tr>
                        <tr><td class="text-muted">Medical History</td><td class="text-end">{{ $patient->medical_history ?? 'None recorded' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Next of Kin</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted">Name</td><td class="text-end">{{ $patient->next_of_kin_name ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Phone</td><td class="text-end">{{ $patient->next_of_kin_phone ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Relationship</td><td class="text-end">{{ $patient->next_of_kin_relationship ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Address</td><td class="text-end">{{ $patient->next_of_kin_address ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs: Appointments, Prescriptions, Lab, Admissions -->
    <ul class="nav nav-tabs mb-3" id="patientTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#appointments" type="button">
                <i class="fas fa-calendar me-1"></i>Appointments
                <span class="badge bg-secondary">{{ $patient->appointments->count() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#prescriptions" type="button">
                <i class="fas fa-pills me-1"></i>Prescriptions
                <span class="badge bg-secondary">{{ $patient->prescriptions->count() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#labs" type="button">
                <i class="fas fa-flask me-1"></i>Lab Requests
                <span class="badge bg-secondary">{{ $patient->labRequests->count() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#admissions" type="button">
                <i class="fas fa-procedures me-1"></i>Admissions
                <span class="badge bg-secondary">{{ $patient->admissions->count() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#vitals" type="button">
                <i class="fas fa-stethoscope me-1"></i>Vital Signs
                <span class="badge bg-secondary">{{ $patient->vitalSigns->count() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Appointments -->
        <div class="tab-pane fade show active" id="appointments">
            <div class="card">
                <div class="card-body">
                    @forelse($patient->appointments->sortByDesc('appointment_date') as $appointment)
                        <div class="border-bottom pb-3 mb-3 d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <span class="badge bg-primary">{{ $appointment->appointment_date ? $appointment->appointment_date->format('d M Y, h:i A') : 'N/A' }}</span>
                                    <span class="badge bg-{{ $appointment->status === 'completed' ? 'success' : ($appointment->status === 'cancelled' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($appointment->status ?? 'scheduled') }}
                                    </span>
                                </h6>
                                @if($appointment->doctor)
                                    <p class="mb-0 small text-muted">
                                        <i class="fas fa-user-md me-1"></i>Dr. {{ $appointment->doctor->first_name }} {{ $appointment->doctor->last_name }}
                                    </p>
                                @endif
                                @if($appointment->complaint)
                                    <p class="mb-0 small"><strong>Complaint:</strong> {{ $appointment->complaint }}</p>
                                @endif
                            </div>
                            <a href="{{ route('hospital.appointments.show', $appointment->id) }}" class="btn btn-sm btn-outline-primary" title="View appointment details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">No appointments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Prescriptions -->
        <div class="tab-pane fade" id="prescriptions">
            <div class="card">
                <div class="card-body">
                    @forelse($patient->prescriptions->sortByDesc('created_at') as $prescription)
                        <div class="border-bottom pb-3 mb-3">
                            <h6 class="mb-1">
                                <span class="badge bg-{{ $prescription->status === 'dispensed' ? 'success' : 'warning' }}">{{ ucfirst($prescription->status ?? 'pending') }}</span>
                                <small class="text-muted">{{ $prescription->created_at->format('d M Y, h:i A') }}</small>
                            </h6>
                            <p class="mb-0 small">{{ $prescription->notes ?? 'No notes' }}</p>
                            @if($prescription->doctor)
                                <p class="mb-0 small text-muted"><i class="fas fa-user-md me-1"></i>Dr. {{ $prescription->doctor->first_name }} {{ $prescription->doctor->last_name }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">No prescriptions on record.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Lab Requests -->
        <div class="tab-pane fade" id="labs">
            <div class="card">
                <div class="card-body">
                    @forelse($patient->labRequests->sortByDesc('created_at') as $lab)
                        <div class="border-bottom pb-3 mb-3">
                            <h6 class="mb-1">
                                <span class="badge bg-info">{{ $lab->test_type }}</span>
                                <span class="badge bg-{{ $lab->status === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($lab->status ?? 'pending') }}</span>
                            </h6>
                            @if($lab->clinical_notes)
                                <p class="mb-0 small">{{ $lab->clinical_notes }}</p>
                            @endif
                            <small class="text-muted">{{ $lab->created_at->format('d M Y') }}</small>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">No lab requests on record.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Admissions -->
        <div class="tab-pane fade" id="admissions">
            <div class="card">
                <div class="card-body">
                    @forelse($patient->admissions->sortByDesc('admission_date') as $admission)
                        <div class="border-bottom pb-3 mb-3">
                            <h6 class="mb-1">
                                <span class="badge bg-primary">{{ $admission->admission_number ?? 'ADM-' . $admission->id }}</span>
                                <span class="badge bg-{{ $admission->status === 'discharged' ? 'success' : 'warning' }}">{{ ucfirst($admission->status ?? 'admitted') }}</span>
                            </h6>
                            <p class="mb-0 small"><strong>Admitted:</strong> {{ optional($admission->admission_date)->format('d M Y') ?? 'N/A' }}
                                @if($admission->discharge_date)
                                    <strong>Discharged:</strong> {{ $admission->discharge_date->format('d M Y') }}
                                @endif
                            </p>
                            @if($admission->reason)
                                <p class="mb-0 small">{{ $admission->reason }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">No admissions on record.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Vitals -->
        <div class="tab-pane fade" id="vitals">
            <div class="card">
                <div class="card-body">
                    @forelse($patient->vitalSigns->sortByDesc('created_at') as $vital)
                        <div class="border-bottom pb-3 mb-3">
                            <small class="text-muted">{{ $vital->created_at->format('d M Y, h:i A') }}</small>
                            <p class="mb-0 small">
                                @if($vital->temperature) <strong>Temp:</strong> {{ $vital->temperature }}°C &nbsp; @endif
                                @if($vital->blood_pressure_systolic) <strong>BP:</strong> {{ $vital->blood_pressure_systolic }}/{{ $vital->blood_pressure_diastolic ?? '?' }} &nbsp; @endif
                                @if($vital->pulse) <strong>Pulse:</strong> {{ $vital->pulse }} bpm &nbsp; @endif
                                @if($vital->oxygen_level) <strong>SpO2:</strong> {{ $vital->oxygen_level }}% @endif
                            </p>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">No vital signs recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
