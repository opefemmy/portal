@extends('layouts.app')

@section('title', 'Payment Receipt')

@section('content')
<style>
    .portal-page {
        background: url("{{ asset('uploads/backgrounds/login-bg.png') }}") no-repeat center center fixed !important;
        background-size: cover !important;
        min-height: 100vh;
        padding: 20px 0;
    }
    .portal-card-custom {
        background: white !important;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
</style>
<div class="portal-page">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow portal-card-custom">
                @if(($showPaymentModal ?? false) && $payment->status == 'pending')
                <div class="card-header bg-warning text-dark py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>Complete Payment
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Please complete your payment to confirm your service request.
                    </div>
                @elseif($payment->status == 'completed')
                <div class="card-header bg-success text-white py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>Payment Receipt
                    </h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle fa-4x text-success"></i>
                        <h4 class="mt-3">Payment Verified</h4>
                    </div>
                @else
                <div class="card-header bg-secondary text-white py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>Payment Details
                    </h4>
                </div>
                <div class="card-body">
                @endif

                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted">Payment Reference</td>
                            <td class="text-end fw-bold">{{ $payment->payment_ref }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Patient Name</td>
                            <td class="text-end">{{ $payment->patient_name }}</td>
                        </tr>
                        @if($payment->patient_email)
                        <tr>
                            <td class="text-muted">Email</td>
                            <td class="text-end">{{ $payment->patient_email }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Phone</td>
                            <td class="text-end">{{ $payment->patient_phone }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Service</td>
                            <td class="text-end">{{ $payment->service_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Payment Date</td>
                            <td class="text-end">{{ $payment->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Payment Method</td>
                            <td class="text-end">{{ ucfirst($payment->payment_method) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td class="text-end">
                                @if($payment->status == 'completed')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($payment->status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @else
                                    <span class="badge bg-secondary">{{ $payment->status }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>

                    <hr>

                    <table class="table table-borderless">
                        <tr>
                            <td>Service Amount</td>
                            <td class="text-end">₦{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Portal Charge (2%)</td>
                            <td class="text-end">₦{{ number_format($payment->portal_charge, 2) }}</td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-bold">Total Amount</td>
                            <td class="text-end fw-bold h4 text-success">₦{{ number_format($payment->total_amount, 2) }}</td>
                        </tr>
                    </table>

                    <div class="d-grid gap-2 mt-4">
                        @if($payment->status == 'pending')
                        <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#paymentModal">
                            <i class="fas fa-credit-card me-2"></i>Pay Now
                        </button>
                        @endif
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print me-2"></i>Print Receipt
                        </button>
                        <a href="{{ route('patient-portal.dashboard') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Payment Modal -->
@if($payment->status == 'pending')
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="paymentModalLabel">
                    <i class="fas fa-credit-card me-2"></i>Complete Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <label class="form-label fw-bold">Select Payment Gateway <span class="text-danger">*</span></label>
                    <select id="paymentGateway" class="form-select form-select-lg">
                        <option value="">-- Select Payment Gateway --</option>
                        @php
                        $enabledProviders = \App\Models\PaymentGateway::getEnabledProviders();
                        @endphp
                        @foreach($enabledProviders as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="alert alert-info">
                    <h5 class="alert-heading">Payment Details</h5>
                    <p class="mb-1"><strong>Reference:</strong> {{ $payment->payment_ref }}</p>
                    <p class="mb-1"><strong>Service:</strong> {{ $payment->service_name }}</p>
                    <p class="mb-0"><strong>Amount:</strong> ₦{{ number_format($payment->total_amount, 2) }}</p>
                </div>

                <div class="d-grid">
                    <button type="button" class="btn btn-success btn-lg" id="processPaymentBtn">
                        <i class="fas fa-credit-card me-2"></i>Proceed to Pay
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var processBtn = document.getElementById('processPaymentBtn');
    var gatewaySelect = document.getElementById('paymentGateway');

    if (processBtn && gatewaySelect) {
        processBtn.addEventListener('click', function() {
            var gateway = gatewaySelect.value;

            if (!gateway) {
                alert('Please select a payment gateway');
                return;
            }

            processBtn.disabled = true;
            processBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

            var paymentRef = '{{ $payment->payment_ref }}';

            // Simulate payment processing
            fetch('{{ route("patient-portal.validate-payment-portal") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    payment_reference: paymentRef
                })
            })
            .then(response => response.json())
            .then(data => {
                location.href = '{{ route("patient-portal.dashboard") }}?payment=completed';
            })
            .catch(error => {
                alert('Payment processing error. Please try again.');
                processBtn.disabled = false;
                processBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Proceed to Pay';
            });
        });
    }
});
</script>
@endpush
@endsection
