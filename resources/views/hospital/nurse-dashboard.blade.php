@extends('layouts.app')

@section('title', 'Nurse Dashboard')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title">Nurse Dashboard</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.dashboard') }}">Hospital</a></li>
                <li class="breadcrumb-item active">Nurse</li>
            </ul>
        </div>
        <div class="col-auto text-end float-end ms-auto">
            <span class="badge bg-primary">{{ now()->format('l, F d, Y') }}</span>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Today's Appointments</p>
                        <h3 class="mb-0">{{ $stats['today_appointments'] }}</h3>
                    </div>
                    <div class="stat-icon bg-primary-light">
                        <i class="fas fa-calendar-day text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Admitted Patients</p>
                        <h3 class="mb-0">{{ $stats['admitted_patients'] }}</h3>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="fas fa-procedures text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Vitals Today</p>
                        <h3 class="mb-0">{{ $stats['vitals_recorded_today'] }}</h3>
                    </div>
                    <div class="stat-icon bg-success-light">
                        <i class="fas fa-heartbeat text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Pending Vitals</p>
                        <h3 class="mb-0">{{ $stats['today_appointments'] }}</h3>
                    </div>
                    <div class="stat-icon bg-info-light">
                        <i class="fas fa-thermometer-half text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="{{ route('hospital.appointments.queue') }}" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-list d-block mb-2"></i> View Queue
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('hospital.patients.index') }}" class="btn btn-outline-success w-100 mb-2">
                            <i class="fas fa-users d-block mb-2"></i> All Patients
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-heartbeat d-block mb-2"></i> Record Vitals
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-warning w-100 mb-2">
                            <i class="fas fa-procedures d-block mb-2"></i> Admitted Patients
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Today's Appointments -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title"><i class="fas fa-users me-2"></i>Patients Waiting for Vitals</h5>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-primary">{{ $todayAppointments->count() }} patients</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient Number</th>
                                <th>Patient Name</th>
                                <th>Type</th>
                                <th>Doctor</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayAppointments as $appointment)
                            <tr>
                                <td>{{ $appointment->appointment_time }}</td>
                                <td><code>{{ $appointment->patient->patient_number ?? 'N/A' }}</code></td>
                                <td>
                                    <strong>{{ $appointment->patient->full_name ?? 'Unknown' }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $appointment->patient->patient_type === 'student' ? 'success' : 'info' }}">
                                        {{ ucfirst($appointment->patient->patient_type ?? 'N/A') }}
                                    </span>
                                </td>
                                <td>Dr. {{ $appointment->doctor->last_name ?? 'TBA' }}</td>
                                <td>
                                    @switch($appointment->status)
                                        @case('checked_in')
                                            <span class="badge bg-warning">Waiting for Vitals</span>
                                            @break
                                        @case('in_progress')
                                            <span class="badge bg-purple">In Consultation</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success">Completed</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($appointment->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <a href="{{ route('hospital.patients.show', $appointment->patient_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                    No patients waiting for vitals
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admitted Patients -->
@if($admittedPatients->count() > 0)
<div class="row mt-3">
    <div class="col-md-12">
        <div class="card border-warning">
            <div class="card-header bg-warning-subtle">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title"><i class="fas fa-procedures me-2"></i>Admitted Patients</h5>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-warning">{{ $admittedPatients->count() }} patients</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Ward</th>
                                <th>Bed</th>
                                <th>Doctor</th>
                                <th>Admission Date</th>
                                <th>Days</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($admittedPatients as $admission)
                            <tr>
                                <td>
                                    <strong>{{ $admission->patient->full_name ?? 'Unknown' }}</strong>
                                    <br><small class="text-muted">{{ $admission->patient->patient_number ?? '' }}</small>
                                </td>
                                <td>{{ $admission->bed->ward->name ?? 'N/A' }}</td>
                                <td>{{ $admission->bed->bed_number ?? 'N/A' }}</td>
                                <td>Dr. {{ $admission->doctor->last_name ?? 'TBA' }}</td>
                                <td>{{ $admission->admission_date->format('d M, Y') }}</td>
                                <td>{{ $admission->admission_date->diffInDays(now()) }} days</td>
                                <td>
                                    <a href="{{ route('hospital.patients.show', $admission->patient_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('styles')
<style>
.stat-card .card-body { padding: 1.25rem; }
.stat-icon {
    width: 48px; height: 48px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
}
.bg-primary-light { background-color: rgba(74, 108, 247, 0.1); }
.bg-success-light { background-color: rgba(34, 197, 94, 0.1); }
.bg-warning-light { background-color: rgba(245, 158, 11, 0.1); }
.bg-info-light { background-color: rgba(6, 182, 212, 0.1); }
.bg-purple { background-color: #6f42c1; }
</style>
@endsection
