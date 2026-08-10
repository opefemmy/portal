@extends('layouts.app')

@section('title', 'Bursar Reports')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-chart-bar me-2"></i>Bursar Reports</h4>
</div>

{{-- Filters — selecting a session / department should reconcile with
     /bursar/payments and /bursar/paid-students. --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('bursar.reports') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Session</label>
                <select name="session_id" class="form-select">
                    <option value="">All Sessions</option>
                    @foreach($sessions ?? [] as $s)
                        <option value="{{ $s->id }}" {{ (string)($sessionId ?? '') === (string)$s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Department</label>
                <input type="number" name="department_id" class="form-control" placeholder="Department ID" value="{{ $deptId ?? '' }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Summary tiles. These numbers come straight from the payments table
     using the same filter (status IN 'completed','verified') as the
     /bursar/payments and /bursar/paid-students screens, so the totals
     reconcile across all bursary staff. --}}
@php $summary = $summary ?? ['total_paid' => 0, 'payment_count' => 0, 'paid_students' => 0, 'total_expected' => 0, 'outstanding' => 0]; @endphp
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card success h-100">
            <div class="card-body">
                <h6 class="text-muted">Total Paid</h6>
                <h2>₦{{ number_format($summary['total_paid'], 2) }}</h2>
                <small class="text-muted">{{ $summary['payment_count'] }} payment(s)</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info h-100">
            <div class="card-body">
                <h6 class="text-muted">Paid Students</h6>
                <h2>{{ $summary['paid_students'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning h-100">
            <div class="card-body">
                <h6 class="text-muted">Total Expected</h6>
                <h2>₦{{ number_format($summary['total_expected'], 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger h-100">
            <div class="card-body">
                <h6 class="text-muted">Outstanding</h6>
                <h2>₦{{ number_format($summary['outstanding'], 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-file-invoice fa-3x text-primary mb-3"></i>
                <h5>Payment Reports</h5>
                <p class="text-muted">View all payment transactions</p>
                <a href="{{ route('bursar.payments') }}" class="btn btn-primary">
                    <i class="fas fa-eye me-2"></i>View Payments
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-success mb-3"></i>
                <h5>Paid Students</h5>
                <p class="text-muted">Students with verified payments</p>
                <a href="{{ route('bursar.paid-students') }}" class="btn btn-success">
                    <i class="fas fa-list me-2"></i>View Paid Students
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-danger mb-3"></i>
                <h5>Debtors List</h5>
                <p class="text-muted">Students with unpaid fees</p>
                <a href="#debtors" class="btn btn-danger">
                    <i class="fas fa-list me-2"></i>View Debtors
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Debtors List --}}
<div class="card mt-4" id="debtors">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Debtors List - Students with Unpaid Fees</h5>
    </div>
    <div class="card-body">
        @if(count($debtors) > 0)
        <div class="alert alert-info">
            <strong>Total Outstanding:</strong> ₦{{ number_format($totalDebt, 2) }} |
            <strong>Total Debtors:</strong> {{ count($debtors) }} students
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Matric Number</th>
                        <th>Student Name</th>
                        <th>Department</th>
                        <th>Unpaid Fees</th>
                        <th>Amount Owed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($debtors as $debtor)
                    <tr>
                        <td>{{ $debtor['student']->matric_number }}</td>
                        <td>{{ $debtor['student']->user->name }}</td>
                        <td>{{ $debtor['student']->department->name ?? 'N/A' }}</td>
                        <td>
                            @foreach($debtor['unpaid_fees'] as $fee)
                            <span class="badge bg-warning text-dark">{{ $fee->name }}</span>
                            @endforeach
                        </td>
                        <td><strong class="text-danger">�{{ number_format($debtor['total_unpaid'], 2) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-center text-success">
            <i class="fas fa-check-circle me-2"></i>No debtors found! All students have paid their fees.
        </p>
        @endif
    </div>
</div>
@endsection
