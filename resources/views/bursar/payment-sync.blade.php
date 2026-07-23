@extends('layouts.app')

@section('title', 'Payment Synchronization')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title">Payment Synchronization</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('bursar.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Payment Synchronization</li>
            </ul>
        </div>
        <div class="col-auto text-end float-end ms-auto">
            <a href="{{ route('bursar.payments.sync.upload') }}" class="btn btn-primary">
                <i class="fas fa-upload me-2"></i> Upload Payments
            </a>
            <a href="{{ route('bursar.payments.sync.template') }}" class="btn btn-outline-secondary">
                <i class="fas fa-download me-2"></i> Template
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Records</p>
                        <h3 class="mb-0">{{ number_format($stats['total']) }}</h3>
                    </div>
                    <div class="stat-icon bg-primary-light">
                        <i class="fas fa-database text-primary"></i>
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
                        <p class="text-muted mb-1">Used</p>
                        <h3 class="mb-0 text-success">{{ number_format($stats['used']) }}</h3>
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
                        <p class="text-muted mb-1">Unused</p>
                        <h3 class="mb-0 text-warning">{{ number_format($stats['unused']) }}</h3>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="fas fa-clock text-warning"></i>
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
                        <p class="text-muted mb-1">Completed</p>
                        <h3 class="mb-0 text-info">{{ number_format($stats['completed']) }}</h3>
                    </div>
                    <div class="stat-icon bg-info-light">
                        <i class="fas fa-check text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Recent Imports</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Applicant Name</th>
                                <th>Email</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                                <th>Channel</th>
                                <th>Used</th>
                                <th>Imported</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentImports as $payment)
                            <tr>
                                <td>
                                    <code>{{ $payment->transaction_id }}</code>
                                </td>
                                <td>{{ $payment->applicant_name }}</td>
                                <td>{{ $payment->email }}</td>
                                <td>₦{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->payment_date->format('d M Y, h:i A') }}</td>
                                <td>
                                    @if($payment->payment_status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($payment->payment_status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @else
                                        <span class="badge bg-danger">Failed</span>
                                    @endif
                                </td>
                                <td>{{ $payment->payment_channel }}</td>
                                <td>
                                    @if($payment->is_used)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>{{ $payment->created_at->format('d M Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No payment records found. Upload an Excel/CSV file to import payments.</td>
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
}
.bg-primary-light { background-color: rgba(74, 108, 247, 0.1); }
.bg-success-light { background-color: rgba(34, 197, 94, 0.1); }
.bg-warning-light { background-color: rgba(245, 158, 11, 0.1); }
.bg-info-light { background-color: rgba(6, 182, 212, 0.1); }
</style>
@endsection
