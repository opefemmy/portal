@extends('layouts.app')

@section('title', 'Audit Reports')

@section('content')
<div class="page-header"><h4><i class="fas fa-search-dollar me-2"></i>Audit Reports</h4></div>

<form method="GET" action="{{ route('auditor.reports') }}" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-1">Start Date</label>
        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate instanceof \Carbon\Carbon ? $startDate->format('Y-m-d') : $startDate }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-1">End Date</label>
        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate instanceof \Carbon\Carbon ? $endDate->format('Y-m-d') : $endDate }}">
    </div>
    <div class="col-md-2 d-grid"><button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button></div>
</form>

<div class="row mb-3">
    <div class="col-md-6"><div class="card text-center p-3 bg-success text-white"><small>Total Income ({{ is_object($startDate) ? $startDate->format('M d') : $startDate }} → {{ is_object($endDate) ? $endDate->format('M d') : $endDate }})</small><h3>₦{{ number_format($totalIncome ?? 0, 2) }}</h3></div></div>
    <div class="col-md-6"><div class="card text-center p-3 bg-danger text-white"><small>Total Expenses</small><h3>₦{{ number_format($totalExpenses ?? 0, 2) }}</h3></div></div>
</div>

<div class="card mb-3">
    <div class="card-header bg-success text-white"><h5 class="mb-0">Receipts</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Date</th><th>Receipt #</th><th>Student</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @forelse($receipts as $r)
                    <tr>
                        <td>{{ optional($r->payment_date)->format('M d, Y') }}</td>
                        <td>{{ $r->receipt_number ?? '—' }}</td>
                        <td>{{ $r->student->user->name ?? '—' }}</td>
                        <td class="text-end">₦{{ number_format($r->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-3 text-muted">No receipts in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-2">{{ $receipts->appends(request()->query())->links() }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-danger text-white"><h5 class="mb-0">Transactions</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @forelse($transactions as $t)
                    <tr>
                        <td>{{ optional($t->transaction_date)->format('M d, Y') }}</td>
                        <td><span class="badge bg-{{ $t->type === 'credit' ? 'success' : 'danger' }}">{{ ucfirst($t->type) }}</span></td>
                        <td>{{ $t->description ?? '—' }}</td>
                        <td class="text-end">₦{{ number_format($t->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-3 text-muted">No transactions in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-2">{{ $transactions->appends(request()->query())->links() }}</div>
    </div>
</div>
@endsection
