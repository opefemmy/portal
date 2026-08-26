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
            <small class="text-muted">Registered {{ optional($patient->created_at)->format('d M Y') ?? 'N/A' }} &middot; {{ ucfirst($patient->patient_type ?? 'patient') }}</small>
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
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#clinical-notes" type="button">
                <i class="fas fa-notes-medical me-1"></i>Clinical Notes
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#staff-notes" type="button">
                <i class="fas fa-comments me-1"></i>Staff Notes
                <span class="badge bg-secondary">{{ $patient->staffNotes->count() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#referrals" type="button">
                <i class="fas fa-share me-1"></i>Referrals
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
                                <small class="text-muted">{{ optional($prescription->created_at)->format('d M Y, h:i A') ?? 'N/A' }}</small>
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
                            <small class="text-muted">{{ optional($lab->created_at)->format('d M Y') ?? 'N/A' }}</small>
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
                                    <strong>Discharged:</strong> {{ optional($admission->discharge_date)->format('d M Y') ?? 'N/A' }}
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
                            <small class="text-muted">{{ optional($vital->created_at)->format('d M Y, h:i A') ?? 'N/A' }}</small>
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

        <!-- Clinical Notes (SOAP) -->
        <div class="tab-pane fade" id="clinical-notes">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Add SOAP / Clinical Note</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('hospital.patients.soap.store', $patient->id) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-2">
                                <label class="form-label">Note Type</label>
                                <select name="note_type" class="form-select" required>
                                    <option value="soap">SOAP</option>
                                    <option value="progress">Progress</option>
                                    <option value="nursing">Nursing</option>
                                    <option value="discharge">Discharge</option>
                                </select>
                            </div>
                            <div class="col-md-10">
                                <label class="form-label">Subjective</label>
                                <textarea name="subjective" class="form-control" rows="2" placeholder="Patient's reported symptoms, history, concerns"></textarea>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label">Objective</label>
                                <textarea name="objective" class="form-control" rows="2" placeholder="Vitals, examination findings, lab results"></textarea>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label">Assessment</label>
                                <textarea name="assessment" class="form-control" rows="2" placeholder="Diagnosis, differential"></textarea>
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label">Plan</label>
                                <textarea name="plan" class="form-control" rows="2" placeholder="Treatment, prescriptions, referrals, follow-up"></textarea>
                            </div>
                            <div class="col-md-12 mt-3 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sign" value="1" id="signNote">
                                    <label class="form-check-label" for="signNote">Electronically sign this note on save</label>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Note
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Previous Notes</h5>
                </div>
                <div class="card-body">
                    @php
                        $notes = \App\Models\Hospital\HospitalClinicalNote::with('staff')
                            ->where('patient_id', $patient->id)
                            ->orderByDesc('created_at')
                            ->limit(20)
                            ->get();
                    @endphp
                    @forelse($notes as $n)
                        <div class="border-start border-3 border-secondary ps-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>{{ strtoupper($n->note_type) }} note</strong>
                                <small class="text-muted">{{ optional($n->created_at)->format('d M Y H:i') }}</small>
                            </div>
                            <small class="text-muted">{{ $n->staff?->full_name ?? '—' }}</small>
                            @if($n->signed_at)
                                <span class="badge bg-success ms-2">
                                    <i class="fas fa-signature"></i> Signed by {{ $n->signed_by_name }}
                                </span>
                                <small class="text-muted ms-2">Hash: {{ substr($n->signature_hash, 0, 12) }}…</small>
                            @else
                                <span class="badge bg-warning text-dark ms-2">Draft</span>
                            @endif
                            <div class="mt-1 small">
                                @if($n->subjective)<div><strong>S:</strong> {{ $n->subjective }}</div>@endif
                                @if($n->objective)<div><strong>O:</strong> {{ $n->objective }}</div>@endif
                                @if($n->assessment)<div><strong>A:</strong> {{ $n->assessment }}</div>@endif
                                @if($n->plan)<div><strong>P:</strong> {{ $n->plan }}</div>@endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No clinical notes yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Staff Notes (handover / instruction / commentary / alert) -->
        <div class="tab-pane fade" id="staff-notes">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add a Staff Note</h5>
                </div>
                <div class="card-body">
                    @permission('notes.create')
                        <form method="POST" action="{{ route('hospital.patients.notes.store', $patient->id) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Audience</label>
                                    <select name="audience" class="form-select">
                                        @foreach(\App\Models\Hospital\HospitalStaffNote::AUDIENCES as $aud)
                                            <option value="{{ $aud }}">{{ ucfirst($aud) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Note Type</label>
                                    <select name="note_type" class="form-select">
                                        @foreach(\App\Models\Hospital\HospitalStaffNote::NOTE_TYPES as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Note</label>
                                    <textarea name="body" class="form-control" rows="2" required
                                        placeholder="e.g. 'Nurse Mary — please recheck BP in 30 minutes.'"></textarea>
                                </div>
                                <div class="col-md-12 mt-2 d-flex gap-2 align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_pinned" value="1" id="pinNote">
                                        <label class="form-check-label" for="pinNote">Pin to top</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Note
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-secondary mb-0">
                            You do not have permission to add staff notes.
                        </div>
                    @endpermission
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Notes on this patient</h5>
                </div>
                <div class="card-body">
                    @forelse($patient->staffNotes as $note)
                        <div class="border-start border-3 ps-3 mb-3 {{ $note->is_pinned ? 'border-warning bg-warning-subtle' : 'border-secondary' }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $note->author?->name ?? 'Unknown' }}</strong>
                                    <span class="text-muted small ms-2">{{ $note->created_at->format('d M Y, h:i A') }}</span>
                                    <span class="badge bg-info ms-1">@ {{ ucfirst($note->audience) }}</span>
                                    <span class="badge bg-secondary ms-1">{{ \App\Models\Hospital\HospitalStaffNote::NOTE_TYPES[$note->note_type] ?? $note->note_type }}</span>
                                    @if($note->is_pinned)
                                        <span class="badge bg-warning text-dark ms-1"><i class="fas fa-thumbtack"></i> Pinned</span>
                                    @endif
                                </div>
                                <div class="d-flex gap-1">
                                    @permission('notes.pin')
                                        <form method="POST" action="{{ route('hospital.patients.notes.pin', [$patient->id, $note->id]) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-link" title="{{ $note->is_pinned ? 'Unpin' : 'Pin' }}">
                                                <i class="fas fa-thumbtack"></i>
                                            </button>
                                        </form>
                                    @endpermission
                                    @permission('notes.delete')
                                        <form method="POST" action="{{ route('hospital.patients.notes.destroy', [$patient->id, $note->id]) }}">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-link text-danger" title="Delete"
                                                onclick="return confirm('Delete this note?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endpermission
                                </div>
                            </div>
                            <p class="mb-0 mt-1">{{ $note->body }}</p>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No staff notes on this patient yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Doctor referrals (lab / pharmacy / x-ray / nurse / follow-up) -->
        <div class="tab-pane fade" id="referrals">
            <div class="row">
                @permission('referrals.send.lab')
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white"><i class="fas fa-vial me-1"></i> Send to Lab</div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('hospital.patients.referrals.lab', $patient->id) }}">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label">Test Type *</label>
                                        <input name="test_type" class="form-control" placeholder="e.g. CBC, Urinalysis" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Clinical Notes</label>
                                        <textarea name="clinical_notes" class="form-control" rows="2"></textarea>
                                    </div>
                                    <button class="btn btn-info"><i class="fas fa-paper-plane"></i> Send to Lab</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endpermission

                @permission('referrals.send.pharmacy')
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white"><i class="fas fa-pills me-1"></i> Prescribe</div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('hospital.patients.referrals.pharmacy', $patient->id) }}">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label">Drug Name *</label>
                                        <input name="drug_name" class="form-control" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Dosage *</label>
                                            <input name="dosage" class="form-control" placeholder="500mg" required>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Frequency *</label>
                                            <input name="frequency" class="form-control" placeholder="3x daily" required>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Duration *</label>
                                            <input name="duration" class="form-control" placeholder="5 days" required>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2"></textarea>
                                    </div>
                                    <button class="btn btn-success"><i class="fas fa-paper-plane"></i> Send to Pharmacy</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endpermission

                @permission('referrals.send.radiology')
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-secondary text-white"><i class="fas fa-x-ray me-1"></i> Send to X-Ray / Radiology</div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('hospital.patients.referrals.radiology', $patient->id) }}">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label">Imaging Type *</label>
                                        <input name="imaging_type" class="form-control" placeholder="e.g. Chest X-Ray" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Clinical Notes</label>
                                        <textarea name="clinical_notes" class="form-control" rows="2"></textarea>
                                    </div>
                                    <button class="btn btn-secondary"><i class="fas fa-paper-plane"></i> Send to Radiology</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endpermission

                @permission('referrals.send.nurse')
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white"><i class="fas fa-user-nurse me-1"></i> Send Back to Nurse</div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('hospital.patients.referrals.nurse', $patient->id) }}">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label">Instruction *</label>
                                        <textarea name="instruction" class="form-control" rows="2" required
                                            placeholder="e.g. Recheck BP in 30 minutes, then re-route."></textarea>
                                    </div>
                                    <button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Return to Nurse</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endpermission

                @permission('appointments.create')
                    <div class="col-md-12 mb-3">
                        <div class="card">
                            <div class="card-header bg-warning text-dark"><i class="fas fa-calendar-plus me-1"></i> Schedule Follow-Up Appointment</div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('hospital.patients.referrals.follow-up', $patient->id) }}" class="row g-2">
                                    @csrf
                                    <div class="col-md-3">
                                        <label class="form-label">Date *</label>
                                        <input type="date" name="appointment_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Time *</label>
                                        <input type="time" name="appointment_time" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Notes</label>
                                        <input name="notes" class="form-control" placeholder="Reason for follow-up">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button class="btn btn-warning w-100"><i class="fas fa-calendar-check"></i> Schedule</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endpermission
            </div>
        </div>
    </div>
</div>
@endsection
