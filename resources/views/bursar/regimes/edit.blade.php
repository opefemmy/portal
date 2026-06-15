@extends('layouts.app')

@section('title', 'Edit Regime')

@section('content')
<div class="page-header">
    <h4>Edit Payment Regime</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('bursar.regimes.update', $regime) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Regime Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name', $regime->name) }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="student_type" class="form-label">Student Type</label>
                        <select class="form-select @error('student_type') is-invalid @endre"
                                id="student_type" name="student_type" required>
                            <option value="Indigene" {{ $regime->student_type == 'Indigene' ? 'selected' : '' }}>Indigene</option>
                            <option value="Non-Indigene" {{ $regime->student_type == 'Non-Indigene' ? 'selected' : '' }}>Non-Indigene</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="installment" class="form-label">Installment</label>
                        <select class="form-select" id="installment" name="installment" required>
                            <option value="First" {{ $regime->installment == 'First' ? 'selected' : '' }}>First (60%)</option>
                            <option value="Second" {{ $regime->installment == 'Second' ? 'selected' : '' }}>Second (40%)</option>
                            <option value="Full" {{ $regime->installment == 'Full' ? 'selected' : '' }}>Full (100%)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="percentage" class="form-label">Percentage (%)</label>
                        <input type="number" class="form-control" id="percentage" name="percentage" value="{{ old('percentage', $regime->percentage) }}" required min="1" max="100">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Fixed Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" value="{{ old('amount', $regime->amount) }}" min="0" step="0.01">
                    </div>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ $regime->is_active ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Regime
                </button>
                <a href="{{ route('bursar.regimes.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection