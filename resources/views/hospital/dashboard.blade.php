@extends('layouts.app')

@section('title', 'Hospital Dashboard')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title">Hospital Dashboard</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item active">Hospital</li>
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
                        <p class="text-muted mb-1">Active Patients</p>
                        <h3 class="mb-0">{{ $stats['active_patients'] }}</h3>
                    </div>
                    <div class="stat-icon bg-success-light">
                        <i class="fas fa-users text-success"></i>
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
                        <p class="text-muted mb-1">Pending Prescriptions</p>
                        <h3 class="mb-0">{{ $stats['pending_prescriptions'] }}</h3>
                    </div>
                    <div class="stat-icon bg-info-light">
                        <i class="fas fa-prescription text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Second Row Stats -->
<div class="row">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">New Patients Today</p>
                        <h3 class="mb-0">{{ $stats['today_patients'] }}</h3>
                    </div>
                    <div class="stat-icon bg-secondary-light">
                        <i class="fas fa-user-plus text-secondary"></i>
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
                        <p class="text-muted mb-1">Pending Lab Tests</p>
                        <h3 class="mb-0">{{ $stats['pending_lab_tests'] }}</h3>
                    </div>
                    <div class="stat-icon bg-danger-light">
                        <i class="fas fa-flask text-danger"></i>
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
                        <p class="text-muted mb-1">Low Stock Drugs</p>
                        <h3 class="mb-0">{{ $stats['low_stock_drugs'] }}</h3>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
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
                        <p class="text-muted mb-1">Pending Appointments</p>
                        <h3 class="mb-0">{{ $stats['pending_appointments'] }}</h3>
                    </div>
                    <div class="stat-icon bg-info-light">
                        <i class="fas fa-clock text-info"></i>
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
                    <div class="col-md-2">
                        <a href="{{ route('hospital.patients.create') }}" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-user-plus d-block mb-2"></i> Register Patient
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('hospital.appointments.create') }}" class="btn btn-outline-success w-100 mb-2">
                            <i class="fas fa-calendar-plus d-block mb-2"></i> New Appointment
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('hospital.appointments.queue') }}" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-list d-block mb-2"></i> View Queue
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('hospital.pharmacy.prescriptions') }}" class="btn btn-outline-warning w-100 mb-2">
                            <i class="fas fa-prescription d-block mb-2"></i> Prescriptions
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('hospital.lab.index') }}" class="btn btn-outline-danger w-100 mb-2">
                            <i class="fas fa-flask d-block mb-2"></i> Laboratory
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('hospital.patients.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                            <i class="fas fa-users d-block mb-2"></i> All Patients
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
                        <a href="{{ route('hospital.appointments.queue') }}" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayAppointments as $appointment)
                            <tr>
                                <td>{{ $appointment->appointment_time }}</td>
                                <td>
                                    <strong>{{ $appointment->patient->full_name ?? 'N/A' }}</strong>
                                    <br><small class="text-muted">{{ $appointment->patient->patient_number ?? '' }}</small>
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
                                        @case('cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($appointment->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <a href="{{ route('hospital.appointments.show', $appointment->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
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

    <!-- Recent Patients -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title"><i class="fas fa-users me-2"></i>Recent Patients</h5>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('hospital.patients.index') }}" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Patient No.</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Phone</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPatients as $patient)
                            <tr>
                                <td><code>{{ $patient->patient_number }}</code></td>
                                <td>
                                    <strong>{{ $patient->full_name }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $patient->patient_type === 'student' ? 'success' : 'info' }}">
                                        {{ ucfirst($patient->patient_type) }}
                                    </span>
                                </td>
                                <td>{{ $patient->phone }}</td>
                                <td>{{ $patient->created_at->format('d M, h:i A') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                                    No patients registered yet
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

<!-- Alerts Section -->
<div class="row mt-3">
    @if($stats['low_stock_drugs'] > 0)
    <div class="col-md-6">
        <div class="alert alert-warning">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <strong>Low Stock Alert!</strong>
                    <p class="mb-0">{{ $stats['low_stock_drugs'] }} drugs are running low on stock.</p>
                </div>
                <a href="{{ route('hospital.pharmacy.low-stock') }}" class="btn btn-sm btn-warning ms-auto">View</a>
            </div>
        </div>
    </div>
    @endif

    @if($stats['pending_lab_tests'] > 0)
    <div class="col-md-6">
        <div class="alert alert-info">
            <div class="d-flex align-items-center">
                <i class="fas fa-flask fa-2x me-3"></i>
                <div>
                    <strong>Pending Lab Tests</strong>
                    <p class="mb-0">{{ $stats['pending_lab_tests'] }} tests awaiting processing.</p>
                </div>
                <a href="{{ route('hospital.lab.index') }}" class="btn btn-sm btn-info ms-auto">View</a>
            </div>
        </div>
    </div>
    @endif
</div>

@endsection

@section('styles')
<style>
.stat-card .card-body {
    padding: 1.25rem;
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
.bg-primary-light { background-color: rgba(74, 108, 247, 0.1); }
.bg-success-light { background-color: rgba(34, 197, 94, 0.1); }
.bg-warning-light { background-color: rgba(245, 158, 11, 0.1); }
.bg-info-light { background-color: rgba(6, 182, 212, 0.1); }
.bg-secondary-light { background-color: rgba(108, 117, 125, 0.1); }
.bg-danger-light { background-color: rgba(239, 68, 68, 0.1); }
.bg-purple { background-color: #6f42c1; }
</style>
@endsection
