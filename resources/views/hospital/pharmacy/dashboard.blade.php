@extends('layouts.app')

@section('title', 'Pharmacy Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">Pharmacy Dashboard</h2>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Drugs</h5>
                    <h3>{{ \App\Models\Hospital\HospitalDrug::count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5>Low Stock</h5>
                    <h3>{{ \App\Models\Hospital\HospitalDrug::whereRaw('current_stock <= reorder_level')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Pending Prescriptions</h5>
                    <h3>{{ \App\Models\Hospital\HospitalPrescription::where('status', 'pending')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Dispensed Today</h5>
                    <h3>{{ \App\Models\Hospital\HospitalPrescription::whereDate('dispensed_at', today())->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('hospital.pharmacy.drugs') }}" class="btn btn-primary">
                            <i class="fas fa-pills me-2"></i>Manage Drugs
                        </a>
                        <a href="{{ route('hospital.pharmacy.prescriptions') }}" class="btn btn-success">
                            <i class="fas fa-prescription me-2"></i>View Prescriptions
                        </a>
                        <a href="{{ route('hospital.pharmacy.low-stock') }}" class="btn btn-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Items
                        </a>
                        <a href="{{ route('hospital.pharmacy.expiring') }}" class="btn btn-danger">
                            <i class="fas fa-calendar-times me-2"></i>Expiring Soon
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Prescriptions -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Prescriptions</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(\App\Models\Hospital\HospitalPrescription::with(['patient', 'doctor'])->latest()->take(5)->get() as $prescription)
                            <tr>
                                <td>{{ $prescription->patient->name ?? 'N/A' }}</td>
                                <td>{{ $prescription->doctor->name ?? 'N/A' }}</td>
                                <td>{{ $prescription->created_at->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $prescription->status === 'pending' ? 'warning' : 'success' }}">
                                        {{ ucfirst($prescription->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('hospital.pharmacy.prescription.show', $prescription) }}" class="btn btn-sm btn-info">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No prescriptions yet</td>
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
