@extends('layouts.app')

@section('title', 'Daily Report')

@section('content')
<div class="page-header"><h4><i class="fas fa-calendar-day me-2"></i>Daily Report — {{ $date ?? date('M d, Y') }}</h4></div>

<div class="row mb-3">
    <div class="col-md-4"><div class="card text-center p-3 bg-success text-white"><small>Income (today)</small><h3>₦{{ number_format($totals['income'] ?? 0, 2) }}</h3></div></div>
    <div class="col-md-4"><div class="card text-center p-3 bg-danger text-white"><small>Expenses (today)</small><h3>₦{{ number_format($totals['expenses'] ?? 0, 2) }}</h3></div></div>
    <div class="col-md-4"><div class="card text-center p-3 bg-info text-white"><small>Net</small><h3>₦{{ number_format(($totals['income'] ?? 0) - ($totals['expenses'] ?? 0), 2) }}</h3></div></div>
</div>

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-1">Date</label>
        <input type="date" name="date" class="form-control form-control-sm" value="{{ $date ?? date('Y-m-d') }}">
    </div>
    <div class="col-md-2 d-grid"><button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button></div>
</form>

@if(($transactions ?? collect())->count())
<div class="card">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Transactions on this day</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Type</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @foreach($transactions as $t)
                    <tr>
                        <td><span class="badge bg-{{ $t->type === 'credit' ? 'success' : 'danger' }}">{{ ucfirst($t->type) }}</span></td>
                        <td>{{ $t->description ?? '—' }}</td>
                        <td class="text-end">₦{{ number_format($t->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No transactions on this day.</div>
@endif
@endsection
