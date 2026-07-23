@extends('layouts.app')

@section('title', 'Doctor Dashboard')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title">Doctor Dashboard</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.dashboard') }}">Hospital</a></li>
                <li class="breadcrumb-item active">Doctor</li>
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
                        <p class="text-muted mb-1">Pending Consultations</p>
                        <h3 class="mb-0">{{ $stats['pending_consultations'] }}</h3>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="fas fa-user-clock text-warning"></i>
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
                        <p class="text-muted mb-1">Completed Today</p>
                        <h3 class="mb-0">{{ $stats['completed_today'] }}</h3>
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
                        <i class="fas fa-users text-info"></i>
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
                        <a href="{{ route('hospital.consultations.create') }}" class="btn btn-outline-success w-100 mb-2">
                            <i class="fas fa-stethoscope d-block mb-2"></i> Start Consultation
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('hospital.patients.index') }}" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-search d-block mb-2"></i> Search Patient
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('hospital.lab.index') }}" class="btn btn-outline-warning w-100 mb-2">
                            <i class="fas fa-flask d-block mb-2"></i> Lab Requests
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Today's Appointments -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title"><i class="fas fa-calendar-day me-2"></i>Today's Appointments</h5>
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
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayAppointments as $appointment)
                            <tr>
                                <td>{{ $appointment->appointment_time }}</td>
                                <td>
                                    <strong>{{ $appointment->patient->full_name ?? 'Unknown' }}</strong>
                                    <br><small class="text-muted">{{ $appointment->patient->patient_number ?? '' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $appointment->patient->patient_type === 'student' ? 'success' : 'info' }}">
                                        {{ ucfirst($appointment->patient->patient_type ?? 'N/A') }}
                                    </span>
                                </td>
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
                                    @if($appointment->status === 'checked_in')
                                    <form action="{{ route('hospital.appointments.start', $appointment->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Start Consultation">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    </form>
                                    @elseif($appointment->status === 'in_progress')
                                    <a href="{{ route('hospital.patients.show', $appointment->patient_id) }}" class="btn btn-sm btn-primary" title="Continue">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                    @else
                                    <a href="{{ route('hospital.patients.show', $appointment->patient_id) }}" class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                    No appointments scheduled for today
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Consultations -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title"><i class="fas fa-user-clock me-2"></i>Active Consultations</h5>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-warning">{{ $pendingConsultations->count() }} pending</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Time Waiting</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingConsultations as $consultation)
                            <tr>
                                <td>
                                    <strong>{{ $consultation->patient->full_name ?? 'Unknown' }}</strong>
                                    <br><small class="text-muted">{{ $consultation->patient->patient_number ?? '' }}</small>
                                </td>
                                <td>
                                    @if($consultation->appointment)
                                    {{ $consultation->appointment->created_at->diffForHumans() }}
                                    @else
                                    --
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('hospital.patients.show', $consultation->patient_id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-arrow-right me-1"></i> Continue
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                                    No pending consultations
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
