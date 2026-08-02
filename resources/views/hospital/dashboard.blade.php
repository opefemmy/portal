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
    <div class="col-md-4">
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
    <div class="col-md-4">
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
    <div class="col-md-4">
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

<!-- Second Row Stats -->
<div class="row mb-4">
    <div class="col-md-4">
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
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Staff</p>
                        <h3 class="mb-0">{{ $stats['total_staff'] }}</h3>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="fas fa-user-md text-warning"></i>
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
                <h5 class="card-title"><i class="fas fa-calendar-day me-2"></i>Today's Appointments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayAppointments as $appointment)
                            <tr>
                                <td>{{ optional($appointment->appointment_date)->format('h:i A') ?? 'N/A' }}</td>
                                <td>
                                    <strong>{{ $appointment->patient->first_name ?? '' }} {{ $appointment->patient->last_name ?? '' }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $appointment->status == 'completed' ? 'success' : 'info' }}">
                                        {{ ucfirst($appointment->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
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
                <h5 class="card-title"><i class="fas fa-users me-2"></i>Recent Patients</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Gender</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPatients as $patient)
                            <tr>
                                <td>
                                    <strong>{{ $patient->first_name }} {{ $patient->last_name }}</strong>
                                </td>
                                <td>{{ $patient->phone }}</td>
                                <td>{{ ucfirst($patient->gender) }}</td>
                                <td>{{ $patient->created_at->format('d M, h:i A') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
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
</style>
@endsection
