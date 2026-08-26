@extends('layouts.app')

@section('title', 'Hospital Appointments')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Appointments</h3>
        @permission('appointments.create')
            <a href="{{ route('hospital.appointments.create') }}" class="btn btn-primary" title="Schedule a new appointment">
                <i class="fas fa-plus"></i> New Appointment
            </a>
        @endpermission
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(['scheduled', 'confirmed', 'checked_in', 'records_certified', 'awaiting_doctor', 'in_progress', 'completed', 'cancelled'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Doctor</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">All Doctors</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-secondary flex-grow-1" title="Apply filters">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('hospital.appointments.index') }}" class="btn btn-outline-secondary" title="Clear filters">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $appointment)
                            <tr>
                                <td>
                                    <strong>{{ $appointment->appointment_date ? $appointment->appointment_date->format('d M Y') : 'N/A' }}</strong><br>
                                    <small class="text-muted">{{ $appointment->appointment_time ?? '' }}</small>
                                </td>
                                <td>
                                    @if($appointment->patient)
                                        <strong>{{ $appointment->patient->first_name }} {{ $appointment->patient->last_name }}</strong><br>
                                        <small class="text-muted">{{ $appointment->patient->patient_number }}</small>
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>
                                <td>
                                    @if($appointment->doctor)
                                        Dr. {{ $appointment->doctor->first_name }} {{ $appointment->doctor->last_name }}
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusColor = match($appointment->status) {
                                            'completed' => 'success',
                                            'in_progress' => 'primary',
                                            'checked_in' => 'info',
                                            'confirmed' => 'secondary',
                                            'cancelled' => 'danger',
                                            default => 'warning',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }}">{{ ucfirst(str_replace('_', ' ', $appointment->status ?? 'scheduled')) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('hospital.appointments.show', $appointment->id) }}" class="btn btn-sm btn-info" title="View appointment details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(in_array($appointment->status, ['scheduled', 'confirmed']))
                                        @permission('appointments.check-in')
                                            <form method="POST" action="{{ route('hospital.appointments.check-in', $appointment->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Check patient in">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endpermission
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No appointments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $appointments->links() }}
        </div>
    </div>
</div>
@endsection
