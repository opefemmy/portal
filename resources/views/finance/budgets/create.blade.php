@extends('layouts.app')

@section('title', 'Create Budget')

@section('content')
<div class="page-header"><h4><i class="fas fa-plus me-2"></i>Create Budget</h4></div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('finance.budgets.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Budget Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fiscal Year</label>
                    <input type="text" name="fiscal_year" class="form-control" value="{{ old('fiscal_year', date('Y')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department (optional)</label>
                    <select name="department_id" class="form-select">
                        <option value="">— Institution-wide —</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total Budget (₦)</label>
                    <input type="number" step="0.01" name="total_budget" class="form-control" value="{{ old('total_budget') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date', date('Y-01-01')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ old('end_date', date('Y-12-31')) }}" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Create Budget</button>
                <a href="{{ route('finance.budgets.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
