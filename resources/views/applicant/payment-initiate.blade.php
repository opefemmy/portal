@extends('layouts.app')

@section('title', 'Processing Payment')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Secure Payment</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-4">
                    <i class="fas fa-credit-card fa-4x text-primary"></i>
                </div>

                <h4>₦{{ number_format($amount, 2) }}</h4>
                <p class="text-muted">Application Fee Payment</p>
                <p><strong>Reference:</strong> {{ $reference }}</p>

                <hr>

                {{-- Paystack Inline Form --}}
                <form id="paymentForm">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <input type="hidden" name="amount" value="{{ $amount * 100 }}">
                    <input type="hidden" name="reference" value="{{ $reference }}">

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
                        <i class="fas fa-shield-alt me-1"></i>
                        Your payment is secured by Paystack. We do not store your card details.
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
                // Payment successful - redirect to callback
                window.location.href = '{{ $callbackUrl }}?reference=' + response.reference + '&trx=' + response.transaction;
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
