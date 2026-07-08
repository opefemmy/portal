@extends('layouts.app')

@section('title', 'Bursar Reports')

@section('content')
<div class="page-header">
    <h4>Bursar Reports</h4>
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
                <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                <h5>Revenue Reports</h5>
                <p class="text-muted">View revenue by session, semester</p>
                <a href="{{ route('bursar.reports') }}" class="btn btn-success">
                    <i class="fas fa-chart-line me-2"></i>View Revenue
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
                        <td><strong class="text-danger">₦{{ number_format($debtor['total_unpaid'], 2) }}</strong></td>
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