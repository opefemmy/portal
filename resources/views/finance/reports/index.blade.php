@extends('layouts.app')

@section('title', 'Finance Reports')

@section('content')
<div class="page-header"><h4><i class="fas fa-chart-bar me-2"></i>Finance Reports</h4></div>

<div class="row">
    <div class="col-md-3 mb-3">
        <a href="{{ route('finance.reports.daily') }}" class="card text-center p-3 text-decoration-none shadow-sm h-100">
            <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
            <strong>Daily Report</strong><br>
            <small class="text-muted">Today's income / expenses</small>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('finance.reports.monthly') }}" class="card text-center p-3 text-decoration-none shadow-sm h-100">
            <i class="fas fa-calendar fa-2x text-success mb-2"></i>
            <strong>Monthly Report</strong><br>
            <small class="text-muted">This month summary</small>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('finance.reports.ie') }}" class="card text-center p-3 text-decoration-none shadow-sm h-100">
            <i class="fas fa-balance-scale fa-2x text-info mb-2"></i>
            <strong>Income vs Expenditure</strong><br>
            <small class="text-muted">Side-by-side comparison</small>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('finance.transactions.index') }}" class="card text-center p-3 text-decoration-none shadow-sm h-100">
            <i class="fas fa-exchange-alt fa-2x text-warning mb-2"></i>
            <strong>All Transactions</strong><br>
            <small class="text-muted">Detailed ledger</small>
        </a>
    </div>
</div>
@endsection
