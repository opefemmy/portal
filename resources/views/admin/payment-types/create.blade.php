@extends('layouts.app')

@section('title', 'Create Payment Type')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Create Payment Type</h4>
    <a href="{{ route('admin.payment-types.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Payment Types
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger">
    <strong>Please fix the errors below and try again:</strong>
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.payment-types.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}"
                           placeholder="e.g., Compulsory Fee, Convocation Fee, ID Card" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code"
                           class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code') }}"
                           placeholder="e.g., COMP_FEE, CONVOCATION" required>
                    <small class="text-muted">Must be unique. Uppercase letters, digits and underscores are recommended.</small>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" rows="2"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Optional. Describe what this payment is for.">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                    <input type="number" name="amount"
                           class="form-control @error('amount') is-invalid @enderror"
                           value="{{ old('amount', '0.00') }}"
                           min="0" step="0.01" required>
                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Payment Channel</label>
                    <select name="payment_channel" class="form-select @error('payment_channel') is-invalid @enderror">
                        <option value="external" {{ old('payment_channel') === 'external' ? 'selected' : '' }}>External (Online Payment)</option>
                        <option value="internal" {{ old('payment_channel') === 'internal' ? 'selected' : '' }}>Internal (Manual)</option>
                        <option value="both" {{ old('payment_channel', 'both') === 'both' ? 'selected' : '' }}>Both</option>
                    </select>
                    <small class="text-muted">External = online gateway. Internal = paid at the bursary.</small>
                    @error('payment_channel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Priority</label>
                    <input type="number" name="priority"
                           class="form-control @error('priority') is-invalid @enderror"
                           value="{{ old('priority', 1) }}" min="1">
                    <small class="text-muted">Lower number = shown first.</small>
                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Purpose</label>
                    <input type="text" name="purpose"
                           class="form-control @error('purpose') is-invalid @enderror"
                           value="{{ old('purpose') }}"
                           list="purpose-suggestions"
                           placeholder="e.g., school_fee, hostel, compulsory, convocation">
                    <datalist id="purpose-suggestions">
                        @foreach(\App\Models\PaymentType::getPurposes() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </datalist>
                    <small class="text-muted">
                        Pick a known purpose or type any label you like (e.g. <code>compulsory_fee</code>).
                        Use lowercase with underscores; spaces will be saved as written.
                    </small>
                    @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Audience <span class="text-danger">*</span></label>
                    <select name="audience" class="form-select @error('audience') is-invalid @enderror" required>
                        @foreach(\App\Models\PaymentType::getAudiences() as $value => $label)
                            <option value="{{ $value }}" {{ old('audience', 'both') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">
                        <strong>Applicant only</strong> hides this type from student pages.<br>
                        <strong>Student only</strong> hides it from applicant pages.<br>
                        <strong>Both</strong> shows it everywhere.
                    </small>
                    @error('audience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1"
                           class="form-check-input" id="create_active"
                           {{ old('is_active', 1) ? 'checked' : '' }}>
                    <label class="form-check-label" for="create_active">Active (visible to users)</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="requires_payment" value="1"
                           class="form-check-input" id="create_payment"
                           {{ old('requires_payment', 1) ? 'checked' : '' }}>
                    <label class="form-check-label" for="create_payment">Requires Payment</label>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.payment-types.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Create Payment Type
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
