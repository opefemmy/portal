@extends('layouts.app')

@section('title', 'Invoice #'.$invoice->invoice_number ?? $invoice->id)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-file-invoice me-2"></i>Invoice #{{ $invoice->invoice_number ?? $invoice->id }}</h4>
    <a href="{{ route('finance.invoices.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
</div>
<div class="card"><div class="card-body">
    <table class="table table-borderless mb-0">
        <tbody>
            <tr><th width="200">Customer</th><td>{{ $invoice->customer_name ?? '—' }}</td></tr>
            <tr><th>Invoice Date</th><td>{{ optional($invoice->invoice_date)->format('M d, Y') ?? $invoice->created_at?->format('M d, Y') }}</td></tr>
            <tr><th>Due Date</th><td>{{ optional($invoice->due_date)->format('M d, Y') ?? '—' }}</td></tr>
            <tr><th>Amount</th><td>₦{{ number_format($invoice->amount ?? $invoice->total_amount ?? 0, 2) }}</td></tr>
            <tr><th>Status</th><td><span class="badge bg-{{ ['paid'=>'success','pending'=>'warning','overdue'=>'danger','draft'=>'secondary'][$invoice->status] ?? 'secondary' }}">{{ ucfirst($invoice->status ?? 'draft') }}</span></td></tr>
            <tr><th>Description</th><td>{{ $invoice->description ?? '—' }}</td></tr>
        </tbody>
    </table>
</div></div>
@endsection
