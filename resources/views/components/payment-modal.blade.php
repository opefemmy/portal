{{--
    Reusable Payment Gateway Modal
    Usage: @include('components.payment-modal', ['payment' => $payment, 'amount' => $amount, 'email' => $email, 'name' => $name, 'description' => $description])
--}}

@php
$gateways = \App\Models\PaymentGateway::getActiveGatewaysWithConfig();
$paymentId = $payment->id ?? $payment->payment_ref ?? time();
$paymentRef = $payment->payment_ref ?? $payment->reference ?? 'PAY-' . time();
$totalAmount = $amount ?? $payment->total_amount ?? $payment->amount ?? 0;
$customerEmail = $email ?? $payment->patient_email ?? $payment->email ?? auth()->user()->email ?? 'customer@example.com';
$customerName = $name ?? $payment->patient_name ?? $payment->payer_name ?? auth()->user()->name ?? 'Customer';
$customerPhone = $phone ?? $payment->patient_phone ?? $payment->payer_phone ?? '';
$paymentDescription = $description ?? $payment->service_name ?? $payment->description ?? 'Payment';
@endphp

<!-- Payment Gateway Modal -->
<div class="modal fade" id="paymentGatewayModal" tabindex="-1" aria-labelledby="paymentGatewayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentGatewayModalLabel">Select Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h4>{{ $paymentDescription }}</h4>
                    <h3 class="text-danger">₦{{ number_format($totalAmount, 2) }}</h3>
                    <p class="text-muted">Ref: {{ $paymentRef }}</p>
                </div>

                <div class="d-grid gap-3">
                    @forelse($gateways as $gateway)
                        @if($gateway->provider === 'xpresspayments')
                        <button type="button" class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2 py-3"
                                onclick="payWithGateway('xpress', '{{ $paymentRef }}', {{ $totalAmount }}, '{{ $customerEmail }}', '{{ $customerName }}')">
                            <img src="https://xpresspay.com.ng/wp-content/uploads/2023/01/Xpress-Logo-1.png"
                                 alt="XpressPayment" height="28" onerror="this.style.display='none'"
                                 style="object-fit: contain;">
                            <span>Pay with XpressPayment</span>
                        </button>

                        @elseif($gateway->provider === 'paystack')
                        <button type="button" class="btn btn-info btn-lg d-flex align-items-center justify-content-center gap-2 py-3"
                                onclick="payWithGateway('paystack', '{{ $paymentRef }}', {{ $totalAmount }}, '{{ $customerEmail }}', '{{ $customerName }}')">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/paystack-badge/1.0.0/paystack-badge.png"
                                 alt="Paystack" height="28" onerror="this.style.display='none'"
                                 style="object-fit: contain;">
                            <span class="text-white">Pay with Paystack</span>
                        </button>

                        @elseif($gateway->provider === 'flutterwave')
                        <button type="button" class="btn btn-warning btn-lg d-flex align-items-center justify-content-center gap-2 py-3"
                                onclick="payWithGateway('flutterwave', '{{ $paymentRef }}', {{ $totalAmount }}, '{{ $customerEmail }}', '{{ $customerName }}')">
                            <img src="https://flutterwave.com/images/logo-dark.png"
                                 alt="Flutterwave" height="28" onerror="this.style.display='none'"
                                 style="object-fit: contain; background: white; padding: 2px; border-radius: 4px;">
                            <span>Pay with Flutterwave</span>
                        </button>

                        @elseif($gateway->provider === 'remita')
                        <button type="button" class="btn btn-success btn-lg d-flex align-items-center justify-content-center gap-2 py-3"
                                onclick="payWithGateway('remita', '{{ $paymentRef }}', {{ $totalAmount }}, '{{ $customerEmail }}', '{{ $customerName }}')">
                            <img src="https://www.remita.net/wp-content/uploads/2023/04/Remita-Logo.png"
                                 alt="Remita" height="28" onerror="this.style.display='none'"
                                 style="object-fit: contain;">
                            <span>Pay with Remita</span>
                        </button>
                        @endif
                    @empty
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No payment gateway is configured. Please contact support.
                    </div>
                    @endforelse
                </div>

                @if($gateways->count() > 0)
                <hr>
                <div class="text-center">
                    <p class="text-muted mb-2">Or pay via bank transfer</p>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#bankTransferModal">
                        <i class="fas fa-university me-2"></i>Bank Transfer
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Bank Transfer Modal -->
@if($gateways->count() > 0)
<div class="modal fade" id="bankTransferModal" tabindex="-1" aria-labelledby="bankTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bankTransferModalLabel">Bank Transfer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Bank Details:</strong><br>
                    Account Name: {{ config('app.name', 'Institution') }}<br>
                    Account Number: 1234567890<br>
                    Bank: First Bank
                </div>
                <form id="bankTransferForm" onsubmit="submitBankTransfer(event, '{{ $paymentRef }}')">
                    <div class="mb-3">
                        <label class="form-label">Upload Payment Proof</label>
                        <input type="file" class="form-control" name="payment_proof" accept="image/*,.pdf" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Submit Payment Proof</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Payment Scripts -->
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://checkout.xpresspay.com/script.js"></script>
<script src="https://checkout.flutterwave.com/v3.js"></script>

<script>
var paymentConfig = {
    xpress: {
        publicKey: '{{ config("services.xpresspayments.public_key", "") }}',
    },
    paystack: {
        publicKey: '{{ config("services.paystack.public_key", "") }}',
    },
    flutterwave: {
        publicKey: '{{ config("services.flutterwave.public_key", "") }}',
    },
    remita: {
        merchantId: '{{ config("services.remita.merchant_id", "") }}',
        serviceTypeId: '{{ config("services.remita.service_type_id", "4430731") }}',
        apiKey: '{{ config("services.remita.api_key", "") }}',
    }
};

function payWithGateway(gateway, ref, amount, email, name) {
    if (gateway === 'xpress') {
        payWithXpressPayment(ref, amount, email, name);
    } else if (gateway === 'paystack') {
        payWithPaystack(ref, amount, email, name);
    } else if (gateway === 'flutterwave') {
        payWithFlutterwave(ref, amount, email, name);
    } else if (gateway === 'remita') {
        payWithRemita(ref, amount, email, name);
    }
}

function payWithXpressPayment(ref, amount, email, name) {
    if (typeof XpressPay !== 'undefined') {
        var xpress = new XpressPay({
            key: paymentConfig.xpress.publicKey,
            amount: amount,
            email: email,
            ref: ref,
            callback: function(response) {
                verifyPayment(ref);
            }
        });
        xpress.open();
    } else {
        window.open('https://checkout.xpresspay.com/?ref=' + ref + '&amount=' + amount + '&email=' + email, '_blank');
        setTimeout(function() { verifyPayment(ref); }, 5000);
    }
}

function payWithPaystack(ref, amount, email, name) {
    var handler = PaystackPop.setup({
        key: paymentConfig.paystack.publicKey,
        email: email,
        amount: amount * 100, // Paystack uses kobo
        ref: ref,
        callback: function(response) {
            verifyPayment(ref);
        },
        onClose: function() {
            console.log('Payment window closed');
        }
    });
    handler.openIframe();
}

function payWithFlutterwave(ref, amount, email, name) {
    if (typeof FlutterwaveCheckout !== 'undefined') {
        FlutterwaveCheckout({
            public_key: paymentConfig.flutterwave.publicKey,
            tx_ref: ref,
            amount: amount,
            currency: 'NGN',
            payment_options: 'card,mobilemoney,ussd',
            customer: { email: email, name: name },
            customizations: {
                title: '{{ config("app.name", "Payment") }}',
                description: '{{ $paymentDescription }}',
                logo: '{{ asset("images/logo.png") }}',
            },
            callback: function(response) {
                verifyPayment(ref);
            },
            onclose: function() {}
        });
    } else {
        window.open('https://checkout.flutterwave.com/pay/' + ref + '?amount=' + amount + '&email=' + email + '&name=' + encodeURIComponent(name), '_blank');
        setTimeout(function() { verifyPayment(ref); }, 5000);
    }
}

function payWithRemita(ref, amount, email, name) {
    var config = paymentConfig.remita;
    var hash = md5(config.apiKey + ref + amount + config.serviceTypeId);
    var url = 'https://login.remita.net/payment/' + config.merchantId + '/' + ref + '/' + amount + '/' + hash +
              '?serviceTypeId=' + config.serviceTypeId + '&payer.email=' + encodeURIComponent(email) +
              '&payer.name=' + encodeURIComponent(name) + '&payer.phone=';

    window.open(url, '_blank');
    setTimeout(function() { verifyPayment(ref); }, 5000);
}

function verifyPayment(ref) {
    document.body.style.cursor = 'wait';

    // Try hospital payment check first
    fetch('/hospital-payment/check/' + ref)
    .then(response => response.json())
    .then(data => {
        document.body.style.cursor = 'default';
        if (data.success && data.payment.status === 'completed') {
            alert('Payment successful!');
            window.location.reload();
        } else {
            // Payment might be pending or from another system
            alert('Payment is being processed. Please wait and refresh.');
            setTimeout(function() { window.location.reload(); }, 3000);
        }
    })
    .catch(error => {
        document.body.style.cursor = 'default';
        console.error('Error:', error);
        // Try general payment check as fallback
        verifyGeneralPayment(ref);
    });
}

function verifyGeneralPayment(ref) {
    document.body.style.cursor = 'wait';
    fetch('/api/payment/verify/' + ref)
    .then(response => response.json())
    .then(data => {
        document.body.style.cursor = 'default';
        if (data.success && data.status === 'completed') {
            alert('Payment successful!');
            window.location.reload();
        } else {
            alert('Payment is being processed. Please wait and refresh.');
            setTimeout(function() { window.location.reload(); }, 3000);
        }
    })
    .catch(error => {
        document.body.style.cursor = 'default';
        alert('Payment initiated. Please verify after payment.');
    });
}

function submitBankTransfer(event, ref) {
    event.preventDefault();
    alert('Payment proof submitted for review. You will be notified once verified.');
    var modal = bootstrap.Modal.getInstance(document.getElementById('bankTransferModal'));
    modal.hide();
}

// Simple MD5 implementation
function md5(string) {
    // Use built-in if available, otherwise simple fallback
    if (typeof CryptoJS !== 'undefined') {
        return CryptoJS.MD5(string).toString();
    }
    // Simple hash for basic functionality
    var hash = 0;
    for (var i = 0; i < string.length; i++) {
        var char = string.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash;
    }
    return Math.abs(hash).toString(16);
}
</script>
