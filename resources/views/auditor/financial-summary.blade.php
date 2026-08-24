@extends('layouts.app')

@section('title', 'Financial Summary')

@section('content')
<div class="page-header"><h4><i class="fas fa-chart-line me-2"></i>Financial Summary</h4></div>

<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center p-3 bg-success text-white"><small>Total Income (all-time)</small><h3>₦{{ number_format($summary['total_income'] ?? 0, 2) }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-danger text-white"><small>Total Expenses (all-time)</small><h3>₦{{ number_format($summary['total_expenses'] ?? 0, 2) }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-info text-white"><small>This Month Income</small><h3>₦{{ number_format($summary['this_month_income'] ?? 0, 2) }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-warning text-dark"><small>This Month Expenses</small><h3>₦{{ number_format($summary['this_month_expenses'] ?? 0, 2) }}</h3></div></div>
</div>

<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>This summary is read-only. Detailed receipt and transaction drill-downs are on the <a href="{{ route('auditor.reports') }}">Audit Reports</a> page.</div>
@endsection
