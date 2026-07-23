@extends('layouts.app')

@section('title', 'Pharmacy Dashboard')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title">Pharmacy Dashboard</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.dashboard') }}">Hospital</a></li>
                <li class="breadcrumb-item active">Pharmacy</li>
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
                        <p class="text-muted mb-1">Pending Prescriptions</p>
                        <h3 class="mb-0">{{ $stats['pending_prescriptions'] }}</h3>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="fas fa-prescription text-warning"></i>
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
                        <p class="text-muted mb-1">Dispensed Today</p>
                        <h3 class="mb-0">{{ $stats['dispensed_today'] }}</h3>
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
                        <p class="text-muted mb-1">Low Stock Items</p>
                        <h3 class="mb-0 text-danger">{{ $stats['low_stock_items'] }}</h3>
                    </div>
                    <div class="stat-icon bg-danger-light">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
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
                        <p class="text-muted mb-1">Total Drugs</p>
                        <h3 class="mb-0">{{ $stats['total_drugs'] }}</h3>
                    </div>
                    <div class="stat-icon bg-info-light">
                        <i class="fas fa-pills text-info"></i>
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
                        <a href="{{ route('hospital.pharmacy.prescriptions') }}" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-list d-block mb-2"></i> Prescriptions
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('hospital.pharmacy.drugs') }}" class="btn btn-outline-success w-100 mb-2">
                            <i class="fas fa-pills d-block mb-2"></i> All Drugs
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('hospital.pharmacy.low-stock') }}" class="btn btn-outline-danger w-100 mb-2">
                            <i class="fas fa-exclamation-triangle d-block mb-2"></i> Low Stock
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('hospital.pharmacy.expiring') }}" class="btn btn-outline-warning w-100 mb-2">
                            <i class="fas fa-clock d-block mb-2"></i> Expiring
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('hospital.pharmacy.categories') }}" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-tags d-block mb-2"></i> Categories
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('hospital.pharmacy.drugs.create') }}" class="btn btn-outline-secondary w-100 mb-2">
                            <i class="fas fa-plus d-block mb-2"></i> Add Drug
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pending Prescriptions -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title"><i class="fas fa-prescription me-2"></i>Pending Prescriptions</h5>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-warning">{{ $pendingPrescriptions->count() }} pending</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rx No.</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingPrescriptions as $prescription)
                            <tr>
                                <td><code>{{ $prescription->prescription_number }}</code></td>
                                <td>
                                    <strong>{{ $prescription->patient->full_name ?? 'Unknown' }}</strong>
                                </td>
                                <td>Dr. {{ $prescription->doctor->last_name ?? 'TBA' }}</td>
                                <td>{{ $prescription->created_at->format('d M, h:i A') }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $prescription->items->count() }} items</span>
                                </td>
                                <td>
                                    <span class="badge bg-warning">Pending</span>
                                </td>
                                <td>
                                    <a href="{{ route('hospital.pharmacy.prescriptions.show', $prescription->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                                    No pending prescriptions
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-header bg-danger-subtle">
                <h5 class="card-title"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert</h5>
            </div>
            <div class="card-body">
                @forelse($lowStockDrugs as $drug)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <strong>{{ $drug->drug_name }}</strong>
                        <br><small class="text-muted">{{ $drug->category->name ?? 'Uncategorized' }}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-danger">{{ $drug->current_stock }} left</span>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center">No low stock items</p>
                @endforelse
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
.bg-danger-light { background-color: rgba(239, 68, 68, 0.1); }
</style>
@endsection
