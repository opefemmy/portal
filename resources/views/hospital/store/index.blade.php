@extends('layouts.app')

@section('title', 'Hospital Store - Inventory Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">Hospital Store & Inventory</h2>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Drug Items</h5>
                    <h3>{{ \App\Models\Hospital\HospitalDrug::count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5>Low Stock Items</h5>
                    <h3>{{ \App\Models\Hospital\HospitalDrug::whereRaw('current_stock <= reorder_level')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5>Expiring Soon</h5>
                    <h3>{{ \App\Models\Hospital\HospitalDrugBatch::where('status', 'active')->whereDate('expiry_date', '<=', now()->addDays(30))->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Total Value</h5>
                    <h3>₦{{ number_format(\App\Models\Hospital\HospitalDrug::sum(\DB::raw('current_stock * cost_price')), 0) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Links -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('hospital.pharmacy.drugs') }}" class="btn btn-outline-primary">
                            <i class="fas fa-pills me-2"></i>Drug Inventory
                        </a>
                        <a href="{{ route('hospital.pharmacy.low-stock') }}" class="btn btn-outline-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Low Stock
                        </a>
                        <a href="{{ route('hospital.pharmacy.expiring') }}" class="btn btn-outline-danger">
                            <i class="fas fa-calendar-times me-2"></i>Expiring Drugs
                        </a>
                        <a href="#" class="btn btn-outline-info">
                            <i class="fas fa-shopping-cart me-2"></i>Purchase Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Movements -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5>Inventory Movements (Recent)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Drug</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Reference</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(\App\Models\Hospital\HospitalInventoryMovement::with('drug', 'user')->latest()->take(20)->get() as $movement)
                                <tr>
                                    <td>{{ $movement->created_at->format('d M Y') }}</td>
                                    <td>{{ $movement->drug->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $movement->movement_type === 'sale' ? 'danger' : ($movement->movement_type === 'purchase' ? 'success' : 'info') }}">
                                            {{ ucfirst($movement->movement_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $movement->quantity }}</td>
                                    <td>{{ $movement->reference ?? '-' }}</td>
                                    <td>{{ $movement->user->name ?? 'System' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No inventory movements recorded</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Section -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Generate Reports</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-alt fa-3x mb-3 text-primary"></i>
                                    <h6>Stock Summary Report</h6>
                                    <button class="btn btn-sm btn-primary">Generate</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-line fa-3x mb-3 text-success"></i>
                                    <h6>Sales Report</h6>
                                    <button class="btn btn-sm btn-success">Generate</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-alt fa-3x mb-3 text-warning"></i>
                                    <h6>Expiry Report</h6>
                                    <button class="btn btn-sm btn-warning">Generate</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-invoice-dollar fa-3x mb-3 text-info"></i>
                                    <h6>Financial Report</h6>
                                    <button class="btn btn-sm btn-info">Generate</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
