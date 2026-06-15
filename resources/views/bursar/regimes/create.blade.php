@extends('layouts.app')

@section('title', 'Create Regime')

@section('content')
<div class="page-header">
    <h4>Create Payment Regime</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('bursar.regimes.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Regime Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}" required
                               placeholder="e.g., Indigene - First Semester">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="student_type" class="form-label">Student Type</label>
                        <select class="form-select @error('student_type') is-invalid @enderror"
                                id="student_type" name="student_type" required>
                            <option value="">Select Type</option>
                            <option value="Indigene">Indigene</option>
                            <option value="Non-Indigene">Non-Indigene</option>
                        </select>
                        @error('student_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="installment" class="form-label">Installment</label>
                        <select class="form-select @error('installment') is-invalid @enderror"
                                id="installment" name="installment" required>
                            <option value="">Select Installment</option>
                            <option value="First">First (60%)</option>
                            <option value="Second">Second (40%)</option>
                            <option value="Full">Full (100%)</option>
                        </select>
                        @error('installment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="percentage" class="form-label">Percentage (%)</label>
                        <input type="number" class="form-control @error('percentage') is-invalid @enderror"
                               id="percentage" name="percentage" value="{{ old('percentage') }}" required min="1" max="100">
                        @error('percentage')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Fixed Amount (Optional)</label>
                        <input type="number" class="form-control @error('amount') is-invalid @enderror"
                               id="amount" name="amount" value="{{ old('amount') }}" min="0" step="0.01"
                               placeholder="Leave empty to calculate from fee">
                        <small class="text-muted">If set, this exact amount will be charged</small>
                    </div>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                <label class="form-check-label" for="is_active">Active</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Create Regime
                </button>
                <a href="{{ route('bursar.regimes.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection