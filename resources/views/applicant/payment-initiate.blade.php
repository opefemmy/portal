@extends('layouts.app')

@php
    // Same labelling rules as the gateway and test pages. Was hardcoded to
    // "Application Fee Payment" regardless of purpose — fixed so the user
    // sees the fee they actually intend to pay on the Paystack iframe step.
    $purpose = $purpose ?? \App\Models\PaymentType::PURPOSE_APPLICATION;
    $paymentType = \App\Models\PaymentType::findByPurpose($purpose);
    $purposeLabel = $paymentType?->name ?: ($paymentType?->display_label ?? 'Application Fee');
@endphp

@section('title', 'Processing Payment')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Secure Payment — {{ $purposeLabel }}</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-4">
                    <i class="fas fa-credit-card fa-4x text-primary"></i>
                </div>

                <h4>₦{{ number_format($amount, 2) }}</h4>
                <p class="text-muted">{{ $purposeLabel }} Payment</p>
                <p><strong>Reference:</strong> {{ $reference }}</p>

                <hr>

                {{-- Paystack Inline Form --}}
                <form id="paymentForm">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <input type="hidden" name="amount" value="{{ $amount * 100 }}">
                    <input type="hidden" name="reference" value="{{ $reference }}">
                    <input type="hidden" name="purpose" value="{{ $purpose }}">

                    <button type="submit" class="btn btn-success btn-lg w-100" id="paystackBtn">
                        <i class="fas fa-lock me-2"></i>Pay with Paystack
                    </button>
                </form>

                <div class="mt-3">
                    <a href="{{ route('applicant.payment.cancel') }}" class="text-danger">
                        Cancel Payment
                    </a>
                </div>

                <hr>

                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-shield-alt me-1"></i> Your payment is secured by Paystack. We do not store your card details.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Paystack Script --}}
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
    const paystackBtn = document.getElementById('paystackBtn');

    paystackBtn.addEventListener('click', function(e) {
        e.preventDefault();

        paystackBtn.disabled = true;
        paystackBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

        const handler = PaystackPop.setup({
            key: '{{ $paystackPublicKey }}',
            email: '{{ $email }}',
            amount: {{ $amount * 100 }},
            reference: '{{ $reference }}',
            callback: function(response) {
                // Payment successful - redirect to callback. Forward the
                // purpose so the callback handler / downstream redirects
                // can use the right route.
                const url = new URL('{{ $callbackUrl }}', window.location.origin);
                url.searchParams.set('reference', response.reference);
                url.searchParams.set('trx', response.transaction);
                url.searchParams.set('purpose', '{{ $purpose }}');
                window.location.href = url.toString();
            },
            onClose: function() {
                paystackBtn.disabled = false;
                paystackBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Pay with Paystack';
                alert('Payment window closed. Please complete your payment.');
            }
        });

        handler.openIframe();
    });
</script>
@endsection
