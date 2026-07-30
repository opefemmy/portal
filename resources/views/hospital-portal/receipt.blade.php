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
                        <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#paymentGatewayModal">
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

@if($payment->status == 'pending')
@include('components.payment-modal', [
    'payment' => $payment,
    'amount' => $payment->total_amount,
    'email' => $payment->patient_email,
    'name' => $payment->patient_name,
    'phone' => $payment->patient_phone,
    'description' => $payment->service_name
])
@endif
@endsection
