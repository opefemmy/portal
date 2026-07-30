@extends('layouts.app')

@section('title', 'Payment')

@section('content')
@php
// Get fee amount from PaymentType
$paymentType = \App\Models\PaymentType::where('code', 'APP_FORM')->first();
$feeAmount = $paymentType ? $paymentType->amount : 5000;
$requireFee = true;
@endphp

<div class="page-header">
    <h4>Application Fee Payment</h4>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Pay Application Fee</h5>
            </div>
            <div class="card-body">
                @if($requireFee && $feeAmount > 0)
                <div class="alert alert-info">
                    <strong>Required Application Fee:</strong> ₦{{ number_format($feeAmount, 2) }}
                </div>
                @endif

                <div class="row text-center mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="p-4 border rounded">
                            <i class="fas fa-credit-card fa-3x text-primary mb-3"></i>
                            <h5>Pay Now Online</h5>
                            <p class="text-muted small">Pay securely using multiple payment gateways</p>
                            @auth
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentGatewayModal">
                                <i class="fas fa-external-link-alt me-1"></i> Pay Now
                            </button>
                            @else
                            <a href="{{ route('login') }}" class="btn btn-primary">
                                <i class="fas fa-lock me-1"></i> Login to Pay
                            </a>
                            @endauth
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="p-4 border rounded">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5>Validate Existing Payment</h5>
                            <p class="text-muted small">Already paid? Enter your transaction ID to validate</p>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#validateModal">
                                <i class="fas fa-check me-1"></i> Validate Payment
                            </button>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="alert alert-warning">
                    <h6><i class="fas fa-info-circle me-2"></i>How to Pay:</h6>
                    <ol class="mb-0">
                        <li>Click <strong>"Pay Now Online"</strong> to select a payment gateway</li>
                        <li>Complete payment using your preferred gateway</li>
                        <li>Or <strong>"Validate Existing Payment"</strong> if you already paid externally</li>
                        <li><strong>Note:</strong> Don't pay to any individual - use only the official payment channels</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

@auth
@include('components.payment-modal', [
    'payment' => (object)['id' => 1, 'payment_ref' => 'APP-' . time(), 'amount' => $feeAmount],
    'amount' => $feeAmount,
    'email' => auth()->user()->email,
    'name' => auth()->user()->name,
    'description' => 'Application Fee'
])
@endauth

<!-- Validate Payment Modal -->
<div class="modal fade" id="validateModal" tabindex="-1" aria-labelledby="validateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="validateModalLabel">Validate Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ auth()->check() ? route('applicant.payment.validate') : route('public.payment.validate') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="transaction_id" class="form-label">
                            Payment Reference / Transaction ID <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control"
                               id="transaction_id"
                               name="transaction_id"
                               placeholder="Enter your payment reference"
                               required>
                        <div class="form-text">
                            Enter the transaction ID you received after making payment.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i> Validate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
