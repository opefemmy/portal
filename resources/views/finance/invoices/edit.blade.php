@extends('layouts.app')

@section('title', 'Edit Invoice')

@section('content')
<div class="page-header"><h4><i class="fas fa-edit me-2"></i>Edit Invoice #{{ $invoice->invoice_number ?? $invoice->id }}</h4></div>
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('finance.invoices.update', $invoice) }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Customer Name</label>
                <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $invoice->customer_name) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Invoice Date</label>
                <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', optional($invoice->invoice_date)->format('Y-m-d') ?? $invoice->created_at?->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Amount (₦)</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $invoice->amount) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    @foreach(['draft','pending','paid','overdue','cancelled'] as $s)
                        <option value="{{ $s }}" {{ old('status', $invoice->status) == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $invoice->description) }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Changes</button>
            <a href="{{ route('finance.invoices.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div></div>
@endsection
