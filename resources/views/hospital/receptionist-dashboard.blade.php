@extends('layouts.app')

@section('title', 'Reception Dashboard')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title">Reception Dashboard</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.dashboard') }}">Hospital</a></li>
                <li class="breadcrumb-item active">Reception</li>
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
                        <p class="text-muted mb-1">Queue Count</p>
                        <h3 class="mb-0">{{ $stats['queue_count'] }}</h3>
                    </div>
                    <div class="stat-icon bg-primary-light">
                        <i class="fas fa-users text-primary"></i>
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
                        <p class="text-muted mb-1">Checked In Today</p>
                        <h3 class="mb-0">{{ $stats['checked_in_today'] }}</h3>
                    </div>
                    <div class="stat-icon bg-success-light">
                        <i class="fas fa-check-circle text-success"></i>
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
                        <p class="text-muted mb-1">Total Patients</p>
                        <h3 class="mb-0">{{ $stats['total_patients'] }}</h3>
                    </div>
                    <div class="stat-icon bg-info-light">
                        <i class="fas fa-database text-info"></i>
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
                        <p class="text-muted mb-1">New Patients Today</p>
                        <h3 class="mb-0">{{ $stats['new_patients_today'] }}</h3>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="fas fa-user-plus text-warning"></i>
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
                        <a href="{{ route('hospital.patients.create') }}" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-user-plus d-block mb-2"></i> Register Patient
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('hospital.appointments.create') }}" class="btn btn-outline-success w-100 mb-2">
                            <i class="fas fa-calendar-plus d-block mb-2"></i> New Appointment
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('hospital.appointments.queue') }}" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-list d-block mb-2"></i> View Queue
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('hospital.patients.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                            <i class="fas fa-search d-block mb-2"></i> Search Patient
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Today's Queue -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title"><i class="fas fa-list me-2"></i>Today's Queue</h5>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-primary">{{ $todayQueue->count() }} patients</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable" id="queueTable">
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
                            @forelse($todayQueue as $appointment)
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
                                        @case('scheduled')
                                            <span class="badge bg-info">Scheduled</span>
                                            @break
                                        @case('confirmed')
                                            <span class="badge bg-primary">Confirmed</span>
                                            @break
                                        @case('checked_in')
                                            <span class="badge bg-warning">Checked In</span>
                                            @break
                                        @case('in_progress')
                                            <span class="badge bg-purple">In Progress</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success">Completed</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($appointment->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <div class="btn-group">
                                        @if($appointment->status === 'scheduled' || $appointment->status === 'confirmed')
                                        <form action="{{ route('hospital.appointments.check-in', $appointment->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Check In">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        @endif
                                        <a href="{{ route('hospital.patients.show', $appointment->patient_id) }}" class="btn btn-sm btn-outline-primary" title="View Patient">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                    No patients in queue for today
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
