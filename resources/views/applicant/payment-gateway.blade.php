@extends('layouts.app')

@section('title', 'Pay Application Fee')

@section('content')
<div class="page-header">
    <h4>Pay Application Fee</h4>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Application Fee Payment</h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-4x text-success mb-3"></i>

                <h3 class="mb-3">Application Fee: ₦{{ number_format($feeAmount, 2) }}</h3>

                <p class="text-muted">
                    Make payment securely using our online payment gateways
                </p>

                <hr>

                {{-- Pay Now Button - Opens Payment Gateway Selection --}}
                <button type="button" class="btn btn-primary btn-lg w-100 mb-3" data-bs-toggle="modal" data-bs-target="#paymentGatewayModal">
                    <i class="fas fa-credit-card me-2"></i>Pay Now
                </button>

                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ route('applicant.payment.test') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-flask me-1"></i>Test Payment
                    </a>
                    <a href="{{ route('applicant.payment') }}" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-check-circle me-1"></i>Validate Existing Payment
                    </a>
                </div>

                <hr>

                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>How to Pay:</h6>
                    <ol class="text-start mb-0">
                        <li>Click the <strong>Pay Now</strong> button above</li>
                        <li>Select your preferred payment gateway</li>
                        <li>Complete payment using card, bank transfer, or USSD</li>
                        <li>After successful payment, you will be redirected back</li>
                    </ol>
                </div>

                <div class="alert alert-warning mt-3">
                    <small>
                        <i class="fas fa-lock me-1"></i>
                        Payments are secured by multiple gateways. Don't share your card details with anyone.
                    </small>
                </div>
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
                @if($applicant->payment_ref)
                <p><strong>Reference:</strong> {{ $applicant->payment_ref }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

@include('components.payment-modal', [
    'payment' => (object)['id' => 1, 'payment_ref' => 'APP-' . time(), 'amount' => $feeAmount],
    'amount' => $feeAmount,
    'email' => auth()->user()->email ?? null,
    'name' => auth()->user()->name ?? 'Applicant',
    'description' => 'Application Fee'
])
@endsection
