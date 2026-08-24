@extends('layouts.app')

@section('title', 'Transaction #'.$transaction->transaction_number ?? $transaction->id)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-exchange-alt me-2"></i>Transaction #{{ $transaction->transaction_number ?? $transaction->id }}</h4>
    <a href="{{ route('finance.transactions.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
</div>

<div class="card"><div class="card-body">
    <table class="table table-borderless mb-0">
        <tbody>
            <tr><th width="200">Date</th><td>{{ optional($transaction->transaction_date)->format('M d, Y') ?? $transaction->created_at?->format('M d, Y') }}</td></tr>
            <tr><th>Type</th><td><span class="badge bg-{{ $transaction->type === 'credit' ? 'success' : 'danger' }}">{{ ucfirst($transaction->type) }}</span></td></tr>
            <tr><th>Ledger</th><td>{{ $transaction->ledger->name ?? '—' }}</td></tr>
            <tr><th>Amount</th><td class="text-end">₦{{ number_format($transaction->amount, 2) }}</td></tr>
            <tr><th>Description</th><td>{{ $transaction->description ?? '—' }}</td></tr>
            <tr><th>Reference</th><td>{{ $transaction->reference ?? '—' }}</td></tr>
            <tr><th>Created By</th><td>{{ $transaction->user->name ?? '—' }} on {{ $transaction->created_at?->format('M d, Y H:i') }}</td></tr>
        </tbody>
    </table>
</div></div>
@endsection
