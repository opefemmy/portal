@extends('layouts.app')

@section('title', 'Create Receipt')

@section('content')
<div class="page-header"><h4><i class="fas fa-plus me-2"></i>Create Receipt</h4></div>

<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('finance.receipts.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Student</label>
                <select name="student_id" class="form-select" required>
                    <option value="">— Select student —</option>
                    @foreach($students ?? [] as $s)
                        <option value="{{ $s->id }}">{{ $s->user->name ?? '' }} ({{ $s->matric_number ?? '' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', date('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Amount (₦)</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Purpose</label>
                <select name="purpose" class="form-select" required>
                    @foreach(['school_fees','hostel','library','application','acceptance','registration','other'] as $p)
                        <option value="{{ $p }}">{{ ucfirst(str_replace('_',' ',$p)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-select">
                    @foreach(['cash','bank_transfer','card','mobile_money','cheque'] as $m)
                        <option value="{{ $m }}">{{ ucfirst(str_replace('_',' ',$m)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Receipt</button>
            <a href="{{ route('finance.receipts.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div></div>
@endsection
