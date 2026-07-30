@extends('layouts.app')

@section('title', 'Hospital Payment Receipt')

@php
$showPaymentModal = request('pay') && $payment->status === 'pending';
@endphp

@section('content')
<style>
    .receipt {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .receipt-header {
        text-align: center;
        border-bottom: 2px solid #dc3545;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
    .receipt-header h2 {
        color: #dc3545;
        margin-bottom: 5px;
    }
    .receipt-details {
        margin: 20px 0;
    }
    .receipt-details table {
        width: 100%;
    }
    .receipt-details th {
        text-align: left;
        padding: 10px;
        background: #f8f9fa;
        width: 40%;
    }
    .receipt-details td {
        padding: 10px;
    }
    .total-amount {
        font-size: 1.5rem;
        font-weight: bold;
        color: #dc3545;
        background: #fce4e4;
        padding: 15px;
        border-radius: 5px;
        text-align: center;
    }
    .status-badge {
        padding: 5px 15px;
        border-radius: 20px;
    }
    .status-completed {
        background: #28a745;
        color: white;
    }
    .status-pending {
        background: #ffc107;
        color: #333;
    }
    @media print {
        body { background: white; }
        .receipt { box-shadow: none; }
        .no-print { display: none; }
    }
</style>

<div class="container py-4">
    <div class="receipt">
        <div class="receipt-header">
            <h2>{{ config('app.name', 'Institution Management Portal') }}</h2>
            <p>Hospital Services Payment Receipt</p>
        </div>

        <div class="receipt-details">
            <table class="table table-bordered">
                <tr>
                    <th>Payment Reference</th>
                    <td><strong>{{ $payment->payment_ref }}</strong></td>
                </tr>
                <tr>
                    <th>Payment Date</th>
                    <td>{{ $payment->created_at->format('d M Y, h:i A') }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="status-badge status-{{ $payment->status }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="receipt-details">
            <h5>Patient Information</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Name</th>
                    <td>{{ $payment->patient_name }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $payment->patient_email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td>{{ $payment->patient_phone }}</td>
                </tr>
                <tr>
                    <th>Gender</th>
                    <td>{{ $payment->patient_gender ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Age</th>
                    <td>{{ $payment->patient_age ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="receipt-details">
            <h5>Service Details</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Service</th>
                    <td>{{ $payment->service_name }}</td>
                </tr>
                @if($payment->appointment_date)
                <tr>
                    <th>Appointment Date</th>
                    <td>{{ \Carbon\Carbon::parse($payment->appointment_date)->format('d M Y, h:i A') }}</td>
                </tr>
                @endif
                @if($payment->doctor_name)
                <tr>
                    <th>Doctor</th>
                    <td>{{ $payment->doctor_name }}</td>
                </tr>
                @endif
                <tr>
                    <th>Payment Method</th>
                    <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                </tr>
                <tr>
                    <th>Service Amount</th>
                    <td>₦{{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Portal Charge</th>
                    <td>₦{{ number_format($payment->portal_charge ?? 0, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="total-amount mt-4">
            Total Paid: ₦{{ number_format($payment->total_amount, 2) }}
        </div>

        <div class="text-center mt-4 no-print">
            @if($payment->status === 'pending')
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                <i class="fas fa-credit-card me-2"></i>Pay Now
            </button>
            @endif
            <button onclick="window.print()" class="btn btn-danger">
                <i class="fas fa-print me-2"></i>Print Receipt
            </button>
            <a href="{{ url('/login') }}" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </div>
</div>

<!-- Payment Modal -->
@if($payment->status === 'pending')
@php
$gateways = \App\Models\PaymentGateway::where('is_active', true)->get();
@endphp
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Complete Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h4>{{ $payment->service_name }}</h4>
                    <h3 class="text-danger">₦{{ number_format($payment->total_amount, 2) }}</h3>
                    <p class="text-muted">Ref: {{ $payment->payment_ref }}</p>
                </div>

                <div class="d-grid gap-3">
                    @forelse($gateways as $gateway)
                        @if($gateway->provider === 'xpresspayments')
                        <button type="button" class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2" onclick="payWithXpress()">
                            <img src="https://xpresspay.com.ng/wp-content/uploads/2023/01/Xpress-Logo-1.png" alt="XpressPayment" height="25" onerror="this.style.display='none'">
                            <span>Pay with XpressPayment</span>
                        </button>
                        @elseif($gateway->provider === 'paystack')
                        <button type="button" class="btn btn-info btn-lg d-flex align-items-center justify-content-center gap-2" onclick="payWithPaystack()">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/paystack-badge/1.0.0/paystack-badge.png" alt="Paystack" height="25" onerror="this.style.display='none'">
                            <span>Pay with Paystack</span>
                        </button>
                        @elseif($gateway->provider === 'flutterwave')
                        <button type="button" class="btn btn-warning btn-lg d-flex align-items-center justify-content-center gap-2" onclick="payWithFlutterwave()">
                            <img src="https://flutterwave.com/images/logo-dark.png" alt="Flutterwave" height="25" onerror="this.style.display='none'" style="background: white; padding: 2px;">
                            <span>Pay with Flutterwave</span>
                        </button>
                        @elseif($gateway->provider === 'remita')
                        <button type="button" class="btn btn-success btn-lg d-flex align-items-center justify-content-center gap-2" onclick="payWithRemita()">
                            <img src="https://www.remita.net/wp-content/uploads/2023/04/Remita-Logo.png" alt="Remita" height="25" onerror="this.style.display='none'">
                            <span>Pay with Remita</span>
                        </button>
                        @endif
                    @empty
                    <button type="button" class="btn btn-primary btn-lg" onclick="payWithXpress()">
                        <i class="fas fa-credit-card me-2"></i>Pay with XpressPayment
                    </button>
                    <button type="button" class="btn btn-info btn-lg" onclick="payWithPaystack()">
                        <i class="fas fa-university me-2"></i>Pay with Paystack
                    </button>
                    @endforelse
                </div>

                <hr>
                <div class="text-center">
                    <p class="text-muted">Or pay at bank and upload evidence</p>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#bankTransferModal">
                        <i class="fas fa-bank me-2"></i>Bank Transfer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bank Transfer Modal -->
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
                    Account Name: {{ config('app.name', 'Institution') }} Hospital<br>
                    Account Number: 1234567890<br>
                    Bank: First Bank
                </div>
                <form id="bankTransferForm">
                    @csrf
                    <input type="hidden" name="payment_reference" value="{{ $payment->payment_ref }}">
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

<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://checkout.xpresspay.com/script.js"></script>
<script src="https://checkout.flutterwave.com/v3.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($showPaymentModal)
    // Auto-show payment modal
    setTimeout(function() {
        var paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
        paymentModal.show();
    }, 500);
    @endif

    // Handle bank transfer form submission
    document.getElementById('bankTransferForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Payment proof submitted for review. You will be notified once verified.');
        var modal = bootstrap.Modal.getInstance(document.getElementById('bankTransferModal'));
        modal.hide();
    });
});

function payWithXpress() {
    var paymentRef = '{{ $payment->payment_ref }}';
    var amount = {{ $payment->total_amount }};
    var email = '{{ $payment->patient_email ?? 'patient@example.com' }}';

    // XpressPayment integration
    if (typeof XpressPay !== 'undefined') {
        var xpress = new XpressPay({
            key: '{{ config("services.xpresspayments.public_key", "") }}',
            amount: amount,
            email: email,
            ref: paymentRef,
            callback: function(response) {
                if (response.status === 'success') {
                    verifyPayment(paymentRef);
                }
            }
        });
        xpress.open();
    } else {
        // Fallback: Open XpressPayment in new window
        var url = 'https://checkout.xpresspay.com/?ref=' + paymentRef + '&amount=' + amount + '&email=' + email;
        window.open(url, '_blank');

        // Poll for payment verification
        setTimeout(function() {
            verifyPayment(paymentRef);
        }, 5000);
    }
}

function payWithPaystack() {
    var paymentRef = '{{ $payment->payment_ref }}';
    var amount = {{ $payment->total_amount * 100 }}; // Paystack uses kobo
    var email = '{{ $payment->patient_email ?? 'patient@example.com' }}';

    var handler = PaystackPop.setup({
        key: '{{ config("services.paystack.public_key", "") }}',
        email: email,
        amount: amount,
        ref: paymentRef,
        callback: function(response) {
            verifyPayment(paymentRef);
        },
        onClose: function() {
            alert('Payment window closed');
        }
    });
    handler.openIframe();
}

function payWithFlutterwave() {
    var paymentRef = '{{ $payment->payment_ref }}';
    var amount = {{ $payment->total_amount }};
    var email = '{{ $payment->patient_email ?? 'patient@example.com' }}';
    var name = '{{ $payment->patient_name ?? '' }}';

    // Flutterwave inline payment
    if (typeof FlutterwaveCheckout !== 'undefined') {
        var flutterwave = FlutterwaveCheckout({
            public_key: '{{ config("services.flutterwave.public_key", "") }}',
            tx_ref: paymentRef,
            amount: amount,
            currency: 'NGN',
            payment_options: 'card,mobilemoney,ussd',
            customer: {
                email: email,
                name: name,
            },
            customizations: {
                title: '{{ config("app.name", "Hospital Payment") }}',
                description: '{{ $payment->service_name }}',
                logo: '{{ asset("images/logo.png") }}',
            },
            callback: function(response) {
                if (response.status === 'successful' || response.tx_ref) {
                    verifyPayment(paymentRef);
                }
            },
            onclose: function() {
                console.log('Payment window closed');
            }
        });
    } else {
        // Fallback: Open Flutterwave payment page
        var url = 'https://checkout.flutterwave.com/pay/' + paymentRef + '?amount=' + amount + '&email=' + email + '&name=' + encodeURIComponent(name);
        window.open(url, '_blank');

        setTimeout(function() {
            verifyPayment(paymentRef);
        }, 5000);
    }
}

function payWithRemita() {
    var paymentRef = '{{ $payment->payment_ref }}';
    var amount = {{ $payment->total_amount }};
    var email = '{{ $payment->patient_email ?? 'patient@example.com' }}';
    var name = '{{ $payment->patient_name ?? '' }}';
    var phone = '{{ $payment->patient_phone ?? '' }}';

    // Remita uses a redirect approach
    // Build the payment URL with query parameters
    var merchantId = '{{ config("services.remita.merchant_id", "") }}';
    var serviceTypeId = '{{ config("services.remita.service_type_id", "4430731") }}';

    // Generate hash for Remita
    var hash = '{{ md5(config("services.remita.api_key", "") . $payment->payment_ref . $payment->total_amount . $serviceTypeId) }}';

    // Remita payment URL
    var url = 'https://login.remita.net/payment/' + merchantId + '/' + paymentRef + '/' + amount + '/' + hash + '?serviceTypeId=' + serviceTypeId + '&payer.email=' + encodeURIComponent(email) + '&payer.name=' + encodeURIComponent(name) + '&payer.phone=' + encodeURIComponent(phone);

    // Open Remita payment page
    window.open(url, '_blank');

    // Poll for payment verification
    setTimeout(function() {
        verifyPayment(paymentRef);
    }, 5000);
}

function verifyPayment(paymentRef) {
    // Show loading
    document.body.style.cursor = 'wait';

    // Try POST validate first
    fetch('/hospital-payment/validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ payment_reference: paymentRef })
    })
    .then(response => response.json())
    .then(data => {
        document.body.style.cursor = 'default';
        if (data.success && data.payment.status === 'completed') {
            alert('Payment successful!');
            window.location.reload();
        } else if (data.success && data.payment.status === 'pending') {
            // Try GET check endpoint as fallback
            checkPaymentStatus(paymentRef);
        } else {
            alert('Payment verification failed. Please try again or contact support.');
        }
    })
    .catch(error => {
        // Fallback to GET check
        checkPaymentStatus(paymentRef);
    });
}

function checkPaymentStatus(paymentRef) {
    document.body.style.cursor = 'wait';

    fetch('/hospital-payment/check/' + paymentRef)
    .then(response => response.json())
    .then(data => {
        document.body.style.cursor = 'default';
        if (data.success && data.payment.status === 'completed') {
            alert('Payment successful!');
            window.location.reload();
        } else if (data.success && data.payment.status === 'pending') {
            alert('Payment is being processed. Please wait a moment and refresh.');
            setTimeout(function() {
                window.location.reload();
            }, 3000);
        } else {
            alert('Payment verification failed. Please try again or contact support.');
        }
    })
    .catch(error => {
        document.body.style.cursor = 'default';
        console.error('Error:', error);
        alert('Error verifying payment. Please refresh the page.');
    });
}
</script>
@endsection
