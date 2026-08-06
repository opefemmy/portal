@extends('layouts.app')

@section('title', 'Edit Payment Type')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Edit Payment Type</h4>
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
        <form action="{{ route('admin.payment-types.update', $paymentType) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $paymentType->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code"
                           class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $paymentType->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" rows="2"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $paymentType->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                    <input type="number" name="amount"
                           class="form-control @error('amount') is-invalid @enderror"
                           value="{{ old('amount', $paymentType->amount) }}"
                           min="0" step="0.01" required>
                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Payment Channel</label>
                    <select name="payment_channel" class="form-select @error('payment_channel') is-invalid @enderror">
                        <option value="external" {{ old('payment_channel', $paymentType->payment_channel) === 'external' ? 'selected' : '' }}>External (Online Payment)</option>
                        <option value="internal" {{ old('payment_channel', $paymentType->payment_channel) === 'internal' ? 'selected' : '' }}>Internal (Manual)</option>
                        <option value="both"     {{ old('payment_channel', $paymentType->payment_channel ?? 'both') === 'both' ? 'selected' : '' }}>Both</option>
                    </select>
                    @error('payment_channel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Priority</label>
                    <input type="number" name="priority"
                           class="form-control @error('priority') is-invalid @enderror"
                           value="{{ old('priority', $paymentType->priority ?: 1) }}" min="1">
                    <small class="text-muted">Lower number = shown first.</small>
                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Purpose</label>
                    <input type="text" name="purpose"
                           class="form-control @error('purpose') is-invalid @enderror"
                           value="{{ old('purpose', $paymentType->purpose) }}"
                           list="purpose-suggestions-edit"
                           placeholder="e.g., school_fee, compulsory_fee, hostel">
                    <datalist id="purpose-suggestions-edit">
                        @foreach(\App\Models\PaymentType::getPurposes() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </datalist>
                    <small class="text-muted">Free-form label — pick a known purpose or type any custom value.</small>
                    @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Audience <span class="text-danger">*</span></label>
                    <select name="audience" class="form-select @error('audience') is-invalid @enderror" required>
                        @foreach(\App\Models\PaymentType::getAudiences() as $value => $label)
                            <option value="{{ $value }}" {{ old('audience', $paymentType->audience ?? 'both') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('audience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1"
                           class="form-check-input" id="edit_active"
                           {{ old('is_active', $paymentType->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="edit_active">Active</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="requires_payment" value="1"
                           class="form-check-input" id="edit_payment"
                           {{ old('requires_payment', $paymentType->requires_payment) ? 'checked' : '' }}>
                    <label class="form-check-label" for="edit_payment">Requires Payment</label>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.payment-types.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
