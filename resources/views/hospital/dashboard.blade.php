@extends('layouts.app')

@section('title', 'Hospital Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Today's Appointments</h5>
                    <h2 class="mb-0">{{ $stats['today_appointments'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Patients</h5>
                    <h2 class="mb-0">{{ $stats['active_patients'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Admitted Patients</h5>
                    <h2 class="mb-0">{{ $stats['admitted_patients'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending Prescriptions</h5>
                    <h2 class="mb-0">{{ $stats['pending_prescriptions'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Today's Appointments</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayAppointments as $appointment)
                            <tr>
                                <td>{{ $appointment->appointment_time }}</td>
                                <td>{{ $appointment->patient->full_name }}</td>
                                <td>Dr. {{ $appointment->doctor->last_name }}</td>
                                <td>
                                    <span class="badge bg-{{ $appointment->status === 'completed' ? 'success' : 'primary' }}">
                                        {{ ucfirst($appointment->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">No appointments today</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Patients</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Patient No.</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPatients as $patient)
                            <tr>
                                <td>{{ $patient->patient_number }}</td>
                                <td>{{ $patient->full_name }}</td>
                                <td>{{ ucfirst($patient->patient_type) }}</td>
                                <td>{{ $patient->created_at->format('d M') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">No patients registered</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if($stats['low_stock_drugs'] > 0)
    <div class="alert alert-warning mt-4">
        <strong>Low Stock Alert:</strong> {{ $stats['low_stock_drugs'] }} drugs are running low on stock.
        <a href="{{ route('hospital.pharmacy.low-stock') }}">View</a>
    </div>
    @endif

    @if($stats['pending_lab_tests'] > 0)
    <div class="alert alert-info mt-2">
        <strong>Pending Lab Tests:</strong> {{ $stats['pending_lab_tests'] }} tests awaiting processing.
    </div>
    @endif
</div>
@endsection