@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Invoices</h4>
    @can('finance.create')
    <a href="{{ route('finance.invoices.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Create Invoice
    </a>
    @endcan
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Invoice No.</th>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr>
                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                    <td>{{ $invoice->student->name ?? 'N/A' }}</td>
                    <td>{{ $invoice->payment_type }}</td>
                    <td>₦{{ number_format($invoice->amount, 2) }}</td>
                    <td>₦{{ number_format($invoice->amount_paid, 2) }}</td>
                    <td>₦{{ number_format($invoice->balance, 2) }}</td>
                    <td>
                        @switch($invoice->status)
                            @case('pending')
                                <span class="badge bg-warning">Pending</span>
                                @break
                            @case('partial')
                                <span class="badge bg-info">Partial</span>
                                @break
                            @case('paid')
                                <span class="badge bg-success">Paid</span>
                                @break
                            @case('overdue')
                                <span class="badge bg-danger">Overdue</span>
                                @break
                            @default
                                <span class="badge bg-secondary">{{ $invoice->status }}</span>
                        @endswitch
                    </td>
                    <td>{{ $invoice->due_date?->format('d M Y') ?? 'N/A' }}</td>
                    <td>
                        <a href="{{ route('finance.invoices.show', $invoice->id) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">No invoices found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $invoices->links() }}
    </div>
</div>
@endsection