@extends('layouts.app')

@php
    // Friendly label + amount from the resolved PaymentType. The view used to
    // hardcode "Pay Application Fee" regardless of purpose, so the user saw
    // a confusing page when they came in via ?purpose=acceptance or
    // ?purpose=school_fee. Now we render the right label everywhere.
    // Prefer the catalogue `name` so admin-renamed rows display verbatim.
    $purposeLabel = $paymentType?->name
        ?: ($paymentType?->display_label ?? 'Application Fee');
    $title = "Pay {$purposeLabel}";
@endphp

@section('title', $title)

@section('content')
<div class="page-header">
    <h4>{{ $title }}</h4>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>{{ $purposeLabel }} Payment</h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-4x text-success mb-3"></i>

                <h3 class="mb-3">{{ $purposeLabel }}: ₦{{ number_format($feeAmount, 2) }}</h3>

                <p class="text-muted">
                    Make payment securely using our online payment gateway
                </p>

                <hr>

                {{-- Pay Now Button - Opens Payment Gateway --}}
                {{-- The previous form omitted the purpose field, so POSTing
                     /applicant/payment/initiate always defaulted to
                     'application', the canPay gate returned
                     "You have already paid the application fee." and the
                     user was bounced back to the dashboard. We now pass
                     purpose through to the initiate route AND to the test
                     endpoint so the user actually reaches the right
                     Paystack iframe / test page. --}}
                <form method="POST" action="{{ route('applicant.payment.initiate') }}">
                    @csrf
                    <input type="hidden" name="amount" value="{{ $feeAmount }}">
                    <input type="hidden" name="purpose" value="{{ $purpose }}">

                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="fas fa-credit-card me-2"></i>Pay Now
                    </button>
                </form>

                <div class="d-flex gap-2 justify-content-center">
                    {{-- Test payment also needs the purpose so the user can
                         simulate acceptance / school_fee flows without
                         Paystack. --}}
                    <a href="{{ route('applicant.payment.test', ['purpose' => $purpose]) }}" class="btn btn-outline-secondary btn-sm">
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
                        <li>You will be redirected to the secure payment page</li>
                        <li>Enter your card details to complete payment</li>
                        <li>After successful payment, you will be redirected back to complete your application</li>
                    </ol>
                </div>

                <div class="alert alert-warning mt-3">
                    <small>
                        <i class="fas fa-lock me-1"></i>
                        Payments are secured by Paystack. Don't share your card details with anyone.
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
@endsection
