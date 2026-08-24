@extends('layouts.app')

@section('title', 'Receipt #'.$receipt->receipt_number ?? $receipt->id)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-receipt me-2"></i>Receipt #{{ $receipt->receipt_number ?? $receipt->id }}</h4>
    <div>
        @if(($receipt->status ?? 'pending') !== 'verified')
            <form method="POST" action="{{ route('finance.receipts.verify', $receipt) }}" class="d-inline">
                @csrf
                <button class="btn btn-success"><i class="fas fa-check-circle me-2"></i>Verify</button>
            </form>
        @endif
        <a href="{{ route('finance.receipts.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>
</div>

<div class="card"><div class="card-body">
    <table class="table table-borderless mb-0">
        <tbody>
            <tr><th width="200">Student</th><td>{{ $receipt->student->user->name ?? '—' }} <small class="text-muted">({{ $receipt->student->matric_number ?? '' }})</small></td></tr>
            <tr><th>Date</th><td>{{ optional($receipt->payment_date)->format('M d, Y') ?? $receipt->created_at?->format('M d, Y') }}</td></tr>
            <tr><th>Amount</th><td class="text-end">₦{{ number_format($receipt->amount, 2) }}</td></tr>
            <tr><th>Purpose</th><td>{{ ucfirst(str_replace('_',' ', $receipt->purpose ?? '—')) }}</td></tr>
            <tr><th>Method</th><td>{{ ucfirst(str_replace('_',' ', $receipt->payment_method ?? '—')) }}</td></tr>
            <tr><th>Status</th><td><span class="badge bg-{{ ($receipt->status ?? '') === 'verified' ? 'success' : 'warning' }}">{{ ucfirst($receipt->status ?? 'pending') }}</span></td></tr>
            <tr><th>Description</th><td>{{ $receipt->description ?? '—' }}</td></tr>
            <tr><th>Verified By</th><td>{{ $receipt->verifiedBy->name ?? '—' }} on {{ optional($receipt->verified_at)->format('M d, Y H:i') ?? '—' }}</td></tr>
        </tbody>
    </table>
</div></div>
@endsection
