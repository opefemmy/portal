@extends('layouts.app')

@section('title', 'Create Payroll')

@section('content')
<div class="page-header"><h4><i class="fas fa-plus me-2"></i>Create Payroll Run</h4></div>

<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('finance.payroll.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Period Start</label>
                <input type="date" name="period_start" class="form-control" value="{{ old('period_start', date('Y-m-01')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Period End</label>
                <input type="date" name="period_end" class="form-control" value="{{ old('period_end', date('Y-m-t')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Department (optional)</label>
                <select name="department_id" class="form-select">
                    <option value="">— All departments —</option>
                    @foreach($departments ?? [] as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Create Payroll</button>
            <a href="{{ route('finance.payroll.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div></div>
@endsection
