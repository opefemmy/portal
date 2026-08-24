@extends('layouts.app')

@section('title', 'Monthly Report')

@section('content')
<div class="page-header"><h4><i class="fas fa-calendar me-2"></i>Monthly Report — {{ $month ?? date('F Y') }}</h4></div>

<div class="row mb-3">
    <div class="col-md-4"><div class="card text-center p-3 bg-success text-white"><small>Income (this month)</small><h3>₦{{ number_format($totals['income'] ?? 0, 2) }}</h3></div></div>
    <div class="col-md-4"><div class="card text-center p-3 bg-danger text-white"><small>Expenses (this month)</small><h3>₦{{ number_format($totals['expenses'] ?? 0, 2) }}</h3></div></div>
    <div class="col-md-4"><div class="card text-center p-3 bg-info text-white"><small>Net</small><h3>₦{{ number_format(($totals['income'] ?? 0) - ($totals['expenses'] ?? 0), 2) }}</h3></div></div>
</div>

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-1">Month</label>
        <input type="month" name="month" class="form-control form-control-sm" value="{{ $month ?? date('Y-m') }}">
    </div>
    <div class="col-md-2 d-grid"><button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button></div>
</form>

@if(($transactions ?? collect())->count())
<div class="card">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Transactions this month</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @foreach($transactions as $t)
                    <tr>
                        <td>{{ optional($t->transaction_date)->format('M d') ?? $t->created_at?->format('M d') }}</td>
                        <td><span class="badge bg-{{ $t->type === 'credit' ? 'success' : 'danger' }}">{{ ucfirst($t->type) }}</span></td>
                        <td>{{ $t->description ?? '—' }}</td>
                        <td class="text-end">₦{{ number_format($t->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
