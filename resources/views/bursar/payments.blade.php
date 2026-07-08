@extends('layouts.app')

@section('title', 'Payment Management')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Payment Management</h4>
    <div>
        <a href="{{ route('bursar.reports') }}" class="btn btn-outline-primary">
            <i class="fas fa-chart-bar me-2"></i>Reports
        </a>
    </div>
</div>

{{-- Filter Section --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Payments</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('bursar.payments') }}" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="all">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Verified</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fee Type</label>
                <select name="fee_id" class="form-select">
                    <option value="">All Fees</option>
                    @foreach($fees as $fee)
                    <option value="{{ $fee->id }}" {{ request('fee_id') == $fee->id ? 'selected' : '' }}>{{ $fee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Matric Number</label>
                <input type="text" name="matric_number" class="form-control" placeholder="Search..." value="{{ request('matric_number') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Payment Summary --}}
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card success">
            <div class="card-body">
                <h6 class="text-muted">Verified Payments</h6>
                <h3>{{ $payments->where('status', 'completed')->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted">Pending Payments</h6>
                <h3>{{ $payments->where('status', 'pending')->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card danger">
            <div class="card-body">
                <h6 class="text-muted">Total Amount</h6>
                <h3>₦{{ number_format($payments->sum('amount'), 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Matric Number</th>
                        <th>Student Name</th>
                        <th>Fee Type</th>
                        <th>Amount</th>
                        <th>Payment Reference</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->student->matric_number ?? 'N/A' }}</td>
                        <td>{{ $payment->student->user->name ?? 'N/A' }}</td>
                        <td>{{ $payment->fee->name ?? 'N/A' }}</td>
                        <td>{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->reference ?? 'N/A' }}</td>
                        <td>
                            @if($payment->status === 'completed')
                                <span class="badge bg-success">Verified</span>
                            @elseif($payment->status === 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @else
                                <span class="badge bg-danger">Failed</span>
                            @endif
                        </td>
                        <td>{{ $payment->created_at->format('d M Y') }}</td>
                        <td>
                            @if($payment->status !== 'completed')
                            <a href="{{ route('bursar.payments.verify', $payment) }}"
                               class="btn btn-sm btn-outline-success"
                               data-bs-toggle="tooltip"
                               title="Verify this payment"
                               onclick="return confirm('Verify this payment?')">
                                <i class="fas fa-check"></i>
                            </a>
                            @endif
                            <a href="{{ route('bursar.payments.receipt', $payment) }}"
                               class="btn btn-sm btn-outline-primary"
                               data-bs-toggle="tooltip"
                               title="View Receipt">
                                <i class="fas fa-receipt"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No payments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection