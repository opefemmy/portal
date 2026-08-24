@extends('layouts.app')

@section('title', 'Income vs Expenditure')

@section('content')
<div class="page-header"><h4><i class="fas fa-balance-scale me-2"></i>Income vs Expenditure</h4></div>

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-1">From</label>
        <input type="date" name="from" class="form-control form-control-sm" value="{{ $from ?? '' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-1">To</label>
        <input type="date" name="to" class="form-control form-control-sm" value="{{ $to ?? '' }}">
    </div>
    <div class="col-md-2 d-grid"><button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button></div>
</form>

<div class="row mb-3">
    <div class="col-md-6"><div class="card text-center p-3 bg-success text-white"><small>Total Income</small><h3>₦{{ number_format($income ?? 0, 2) }}</h3></div></div>
    <div class="col-md-6"><div class="card text-center p-3 bg-danger text-white"><small>Total Expenses</small><h3>₦{{ number_format($expenses ?? 0, 2) }}</h3></div></div>
</div>

@php $net = ($income ?? 0) - ($expenses ?? 0); @endphp
<div class="card">
    <div class="card-header bg-{{ $net >= 0 ? 'success' : 'danger' }} text-white"><h5 class="mb-0">Net {{ $net >= 0 ? 'Surplus' : 'Deficit' }}: ₦{{ number_format(abs($net), 2) }}</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @forelse($entries ?? [] as $e)
                    <tr>
                        <td>{{ optional($e->transaction_date)->format('M d, Y') ?? $e->created_at?->format('M d, Y') }}</td>
                        <td><span class="badge bg-{{ $e->type === 'credit' ? 'success' : 'danger' }}">{{ ucfirst($e->type) }}</span></td>
                        <td>{{ $e->description ?? '—' }}</td>
                        <td class="text-end">₦{{ number_format($e->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-4 text-muted">No entries in range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
