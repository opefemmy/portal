@extends('layouts.app')

@section('title', 'New Transaction')

@section('content')
<div class="page-header"><h4><i class="fas fa-plus me-2"></i>New Transaction</h4></div>

<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('finance.transactions.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', date('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" required>
                    <option value="credit" {{ old('type') == 'credit' ? 'selected' : '' }}>Credit (income)</option>
                    <option value="debit" {{ old('type') == 'debit' ? 'selected' : '' }}>Debit (expense)</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Ledger</label>
                <select name="ledger_id" class="form-select" required>
                    <option value="">— Select ledger —</option>
                    @foreach($ledgers ?? [] as $l)
                        <option value="{{ $l->id }}">{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Amount (₦)</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
            </div>
            <div class="col-md-12">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" value="{{ old('description') }}" required>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
            <a href="{{ route('finance.transactions.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div></div>
@endsection
