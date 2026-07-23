@extends('layouts.app')

@section('title', 'Validate Payment')

@section('content')
<div class="page-header">
    <h4>Validate Payment</h4>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Validate Your Payment</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-money-bill-wave fa-4x text-success mb-3"></i>
                    <p class="text-muted">
                        Enter your Transaction ID / Payment Reference to validate your payment.
                        Your payment must have been made on the external payment platform first.
                    </p>
                </div>

                @if($requireFee && $feeAmount > 0)
                <div class="alert alert-info">
                    <strong>Required Application Fee:</strong> ₦{{ number_format($feeAmount, 2) }}
                </div>
                @endif

                <form method="POST" action="{{ route('applicant.payment.validate') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="transaction_id" class="form-label">
                            Transaction ID / Payment Reference <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('transaction_id') is-invalid @enderror"
                               id="transaction_id"
                               name="transaction_id"
                               value="{{ old('transaction_id') }}"
                               placeholder="Enter your transaction ID"
                               required>
                        @error('transaction_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            This is the reference number you received after making payment on the payment platform.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Applicant Email (Optional)
                        </label>
                        <input type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               id="email"
                               name="email"
                               value="{{ old('email', auth()->user()->email ?? '') }}"
                               placeholder="Enter your email address">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check me-2"></i>Validate Payment
                        </button>
                        <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Login
                        </a>
                    </div>
                </form>

                <hr>

                <div class="alert alert-warning">
                    <h6><i class="fas fa-info-circle me-2"></i>How it works:</h6>
                    <ol class="mb-0">
                        <li>Make your payment on the external payment platform</li>
                        <li>Copy the Transaction ID / Reference Number</li>
                        <li>Enter the Transaction ID above and click Validate</li>
                        <li>Once validated, you can complete your application form</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
