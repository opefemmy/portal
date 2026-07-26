@extends('layouts.app')

@section('title', 'Visit: ' . $visit->visit_number)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-user-injured me-2"></i>Visit: {{ $visit->visit_number }}</h4>
        <p class="text-muted mb-0">Patient: <strong>{{ $visit->patient->full_name }}</strong> ({{ $visit->patient->patient_number }})</p>
    </div>
    <div class="gap-2 d-flex">
        @if($visit->status != 'completed')
        <form method="POST" action="{{ route('hospital.visits.complete', $visit->id) }}">
            @csrf
            <button type="submit" class="btn btn-success" onclick="return confirm('Complete this visit?')">
                <i class="fas fa-check me-2"></i>Complete Visit
            </button>
        </form>
        @else
        <span class="badge bg-success fs-5">COMPLETED</span>
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<!-- Vital Signs Card -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Vital Signs</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('hospital.visits.vitals', $visit->id) }}" class="row g-3">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label">Temperature</label>
                        <input type="text" name="vital_signs_temperature" class="form-control" value="{{ $visit->vital_signs_temperature }}" placeholder="36.5°C">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Blood Pressure</label>
                        <input type="text" name="vital_signs_bp" class="form-control" value="{{ $visit->vital_signs_bp }}" placeholder="120/80 mmHg">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Pulse</label>
                        <input type="text" name="vital_signs_pulse" class="form-control" value="{{ $visit->vital_signs_pulse }}" placeholder="72 bpm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Respiration</label>
                        <input type="text" name="vital_signs_respiration" class="form-control" value="{{ $visit->vital_signs_respiration }}" placeholder="16 /min">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Oxygen (SpO2)</label>
                        <input type="text" name="vital_signs_oxygen" class="form-control" value="{{ $visit->vital_signs_oxygen }}" placeholder="98%">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Save Vitals</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Diagnosis & Treatment -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Diagnosis & Treatment</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('hospital.visits.update', $visit->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Chief Complaint</label>
                        <textarea class="form-control" rows="2" readonly>{{ $visit->chief_complaint }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Diagnosis</label>
                        <textarea name="diagnosis" class="form-control" rows="3" placeholder="Enter diagnosis...">{{ $visit->diagnosis }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Treatment Plan</label>
                        <textarea name="treatment" class="form-control" rows="3" placeholder="Enter treatment...">{{ $visit->treatment }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Next Visit Date</label>
                            <input type="date" name="next_visit_date" class="form-control" value="{{ $visit->next_visit_date }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Next Visit Notes</label>
                            <input type="text" name="next_visit_notes" class="form-control" value="{{ $visit->next_visit_notes }}" placeholder="e.g., Bring previous records">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending" {{ $visit->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="in_progress" {{ $visit->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ $visit->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $visit->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Visit Info</h5>
            </div>
            <div class="card-body">
                <p><strong>Visit Date:</strong> {{ $visit->visit_date->format('d M Y, h:i A') }}</p>
                <p><strong>Type:</strong> {{ $visit->visit_type ?? 'General' }}</p>
                <p><strong>Status:</strong>
                    @if($visit->status == 'completed')
                    <span class="badge bg-success">{{ ucfirst($visit->status) }}</span>
                    @elseif($visit->status == 'in_progress')
                    <span class="badge bg-warning">{{ ucfirst($visit->status) }}</span>
                    @else
                    <span class="badge bg-secondary">{{ ucfirst($visit->status) }}</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Prescriptions -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0"><i class="fas fa-pills me-2"></i>Prescriptions</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">+ Add</button>
            </div>
            <div class="card-body">
                @forelse($visit->prescriptions as $prescription)
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $prescription->medication_name }}</strong>
                        <span class="badge bg-{{ $prescription->is_dispensed ? 'success' : 'warning' }}">
                            {{ $prescription->is_dispensed ? 'Dispensed' : 'Pending' }}
                        </span>
                    </div>
                    <small class="text-muted">
                        Dosage: {{ $prescription->dosage ?? 'N/A' }} |
                        Frequency: {{ $prescription->frequency ?? 'N/A' }} |
                        Duration: {{ $prescription->duration ?? 'N/A' }}
                    </small>
                </div>
                @empty
                <p class="text-muted">No prescriptions yet.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0"><i class="fas fa-flask me-2"></i>Lab Orders</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLabModal">+ Add</button>
            </div>
            <div class="card-body">
                @forelse($visit->labOrders as $lab)
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $lab->test_name }}</strong>
                        <span class="badge bg-{{ $lab->status == 'completed' ? 'success' : 'warning' }}">
                            {{ ucfirst($lab->status) }}
                        </span>
                    </div>
                    <small class="text-muted">{{ $lab->urgency }} | {{ $lab->test_type ?? 'General' }}</small>
                </div>
                @empty
                <p class="text-muted">No lab orders yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Add Prescription Modal -->
<div class="modal fade" id="addPrescriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Prescription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('hospital.visits.prescription', $visit->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Medication Name *</label>
                        <input type="text" name="medication_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Dosage</label>
                            <input type="text" name="dosage" class="form-control" placeholder="e.g., 500mg">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Frequency</label>
                            <input type="text" name="frequency" class="form-control" placeholder="e.g., 3x daily">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Duration</label>
                        <input type="text" name="duration" class="form-control" placeholder="e.g., 7 days">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Instructions</label>
                        <textarea name="instructions" class="form-control" rows="2" placeholder="Special instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Prescription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Lab Order Modal -->
<div class="modal fade" id="addLabModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Lab Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('hospital.visits.lab', $visit->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Test Name *</label>
                        <input type="text" name="test_name" class="form-control" required placeholder="e.g., Malaria Test">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Test Type</label>
                            <select name="test_type" class="form-select">
                                <option value="">Select</option>
                                <option value="Blood">Blood</option>
                                <option value="Urine">Urine</option>
                                <option value="Stool">Stool</option>
                                <option value="Radiology">Radiology</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Urgency</label>
                            <select name="urgency" class="form-select">
                                <option value="routine">Routine</option>
                                <option value="urgent">Urgent</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Lab Order</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
