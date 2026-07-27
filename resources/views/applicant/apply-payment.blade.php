@extends('layouts.app')

@section('title', 'Application Fee Payment')

@section('content')
<div class="page-header">
    <h4>Application Fee Payment</h4>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Application Fee Payment</h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-4x text-primary mb-3"></i>

                @if(isset($paymentType) && $paymentType)
                <h4>Application Fee: ₦{{ number_format($paymentType->amount, 2) }}</h4>
                @else
                <h4>Application Fee: ₦{{ number_format($feeAmount ?? 0, 2) }}</h4>
                @endif

                <p class="text-muted">Enter your payment transaction ID to validate payment</p>

                <hr>

                <form method="POST" action="{{ route('applicant.payment.verify-external') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Payment Reference / Transaction ID *</label>
                        <input type="text" name="payment_ref" class="form-control" placeholder="Enter your payment reference" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount Paid (₦) *</label>
                        <input type="number" name="amount" class="form-control" placeholder="Enter amount paid" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date *</label>
                        <input type="date" name="payment_date" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mb-3">
                        <i class="fas fa-check me-2"></i>Validate Payment
                    </button>
                </form>

                <hr>

                <div class="alert alert-warning">
                    <h6><i class="fas fa-info-circle me-2"></i>How to Pay:</h6>
                    <ol class="mb-0 text-start">
                        <li>Click the Pay Now button to redirect to the payment page</li>
                        <li>Copy the payment reference and validate your payment</li>
                        <li><strong>Note:</strong> Don't pay to any individual</li>
                    </ol>
                </div>

                {{-- Pay Now Button --}}
                @php
                $paymentPortalUrl = \App\Models\SystemSetting::getPaymentPortalUrl();
                @endphp
                @if($paymentPortalUrl)
                <div class="d-grid mt-3">
                    <a href="{{ $paymentPortalUrl }}" target="_blank" class="btn btn-primary btn-lg">
                        <i class="fas fa-credit-card me-2"></i>Pay Now
                    </a>
                </div>
                @else
                <div class="d-grid mt-3">
                    <a href="{{ route('applicant.payment.gateway') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-credit-card me-2"></i>Pay Now
                    </a>
                </div>
                @endif
            </div>
        </div>

        @if(isset($applicant) && $applicant && $applicant->payment_status)
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Payment Status</h6>
            </div>
            <div class="card-body">
                <p><strong>Status:</strong>
                    @if($applicant->payment_status === 'completed')
                        <span class="badge bg-success">Verified</span>
                    @else
                        <span class="badge bg-warning">{{ ucfirst($applicant->payment_status) }}</span>
                    @endif
                </p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
