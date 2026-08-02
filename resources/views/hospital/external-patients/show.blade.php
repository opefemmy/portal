@extends('layouts.app')

@section('title', 'Patient: ' . $patient->full_name)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-user me-2"></i>{{ $patient->full_name }}</h4>
        <p class="text-muted mb-0">Patient Number: <strong>{{ $patient->patient_number }}</strong></p>
    </div>
    <div class="gap-2 d-flex">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newVisitModal">
            <i class="fas fa-plus me-2"></i>New Visit
        </button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#appointmentModal">
            <i class="fas fa-calendar me-2"></i>Schedule Appointment
        </button>
    </div>
</div>

<!-- Patient Info Card -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Personal Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr><td><strong>Phone:</strong></td><td>{{ $patient->phone }}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>{{ $patient->email ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Gender:</strong></td><td>{{ ucfirst($patient->gender ?? 'N/A') }}</td></tr>
                    <tr><td><strong>Age:</strong></td><td>{{ $patient->age ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Address:</strong></td><td>{{ $patient->address ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Registered:</strong></td><td>{{ $patient->created_at->format('d M Y') }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Medical Info</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr><td><strong>Blood Group:</strong></td><td>{{ $patient->blood_group ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Genotype:</strong></td><td>{{ $patient->genotype ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Allergies:</strong></td><td>{{ $patient->allergies ?? 'None recorded' }}</td></tr>
                    <tr><td><strong>Chronic Conditions:</strong></td><td>{{ $patient->chronic_conditions ?? 'None recorded' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Emergency Contact</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr><td><strong>Name:</strong></td><td>{{ $patient->emergency_contact_name ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Phone:</strong></td><td>{{ $patient->emergency_contact_phone ?? 'N/A' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Tabs for Visits, Appointments, Communications -->
<ul class="nav nav-tabs mb-4" id="patientTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#visits" type="button">
            <i class="fas fa-history me-2"></i>Visit History
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#appointments" type="button">
            <i class="fas fa-calendar me-2"></i>Appointments
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#communications" type="button">
            <i class="fas fa-comments me-2"></i>Communications
            @if($patient->communications->where('is_read', false)->count() > 0)
            <span class="badge bg-danger">{{ $patient->communications->where('is_read', false)->count() }}</span>
            @endif
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#payments" type="button">
            <i class="fas fa-credit-card me-2"></i>Payments
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Visits Tab -->
    <div class="tab-pane fade show active" id="visits" role="tabpanel">
        <div class="card">
            <div class="card-body">
                @forelse($patient->visits->sortByDesc('visit_date') as $visit)
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5><span class="badge bg-primary">{{ $visit->visit_number }}</span>
                            <span class="badge bg-{{ $visit->status == 'completed' ? 'success' : 'warning' }}">{{ ucfirst($visit->status) }}</span></h5>
                            <p class="mb-1"><strong>Date:</strong> {{ optional($visit->visit_date)->format('d M Y, h:i A') ?? 'N/A' }}</p>
                            <p class="mb-1"><strong>Type:</strong> {{ $visit->visit_type ?? 'General' }}</p>
                            @if($visit->chief_complaint)
                            <p class="mb-1"><strong>Complaint:</strong> {{ $visit->chief_complaint }}</p>
                            @endif
                            @if($visit->diagnosis)
                            <p class="mb-1"><strong>Diagnosis:</strong> {{ $visit->diagnosis }}</p>
                            @endif
                            @if($visit->treatment)
                            <p class="mb-1"><strong>Treatment:</strong> {{ $visit->treatment }}</p>
                            @endif
                        </div>
                        @if($visit->next_visit_date)
                        <div class="text-end">
                            <span class="badge bg-info">Next Visit: {{ \Carbon\Carbon::parse($visit->next_visit_date)->format('d M Y') }}</span>
                            @if($visit->next_visit_notes)
                            <p class="text-muted small mb-0">{{ $visit->next_visit_notes }}</p>
                            @endif
                        </div>
                        @endif
                        <div class="mt-2">
                            <a href="{{ route('hospital.visits.edit', $visit->id) }}" class="btn btn-sm btn-outline-primary" title="Edit visit details, add vitals, prescriptions, or lab orders">
                                <i class="fas fa-edit"></i> Manage Visit
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center">No visits recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Appointments Tab -->
    <div class="tab-pane fade" id="appointments" role="tabpanel">
        <div class="card">
            <div class="card-body">
                @forelse($patient->appointments->sortByDesc('appointment_date') as $appointment)
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>{{ $appointment->appointment_number }}</strong> - {{ $appointment->purpose }}
                        </div>
                        <div>
                            <span class="badge bg-{{ $appointment->status == 'completed' ? 'success' : ($appointment->status == 'cancelled' ? 'danger' : 'warning') }}">
                                {{ ucfirst($appointment->status) }}
                            </span>
                            <span class="text-muted">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y, h:i A') }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center">No appointments scheduled.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Communications Tab -->
    <div class="tab-pane fade" id="communications" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0">Communication History</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                    <i class="fas fa-paper-plane me-2"></i>Send Message
                </button>
            </div>
            <div class="card-body">
                @forelse($patient->communications->sortByDesc('created_at') as $comm)
                <div class="border-bottom pb-2 mb-2 {{ $comm->is_read ? '' : 'bg-light' }}">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="badge bg-{{ $comm->type == 'sms' ? 'success' : ($comm->type == 'email' ? 'info' : 'secondary') }}">{{ strtoupper($comm->type) }}</span>
                            <strong>{{ $comm->subject }}</strong>
                        </div>
                        <small class="text-muted">{{ $comm->created_at->format('d M Y, h:i A') }}</small>
                    </div>
                    <p class="mb-0 mt-2">{{ $comm->message }}</p>
                </div>
                @empty
                <p class="text-muted text-center">No communications yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Payments Tab -->
    <div class="tab-pane fade" id="payments" role="tabpanel">
        <div class="card">
            <div class="card-body">
                @forelse($payments as $payment)
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>{{ $payment->payment_ref }}</strong> - {{ $payment->service_name }}
                        </div>
                        <div>
                            <span class="badge bg-{{ $payment->status == 'completed' ? 'success' : 'warning' }}">{{ ucfirst($payment->status) }}</span>
                            <span class="text-muted">₦{{ number_format($payment->total_amount) }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center">No payments found.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- New Visit Modal -->
<div class="modal fade" id="newVisitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start New Visit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('hospital.external-patients.visit', $patient->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Visit Type</label>
                        <select name="visit_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="General">General Consultation</option>
                            <option value="Follow-up">Follow-up</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Laboratory">Laboratory</option>
                            <option value="Pharmacy">Pharmacy</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chief Complaint</label>
                        <textarea name="chief_complaint" class="form-control" rows="3" placeholder="What is the patient complaining of?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Visit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Appointment Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('hospital.external-patients.appointment', $patient->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Appointment Date & Time</label>
                        <input type="datetime-local" name="appointment_date" class="form-control" min="{{ now()->format('Y-m-d\TH:i') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose</label>
                        <input type="text" name="purpose" class="form-control" placeholder="Reason for appointment" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Send Message Modal -->
<div class="modal fade" id="sendMessageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Communication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('hospital.external-patients.communication', $patient->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="note">Internal Note</option>
                            <option value="sms">SMS</option>
                            <option value="call">Call Log</option>
                            <option value="email">Email</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
