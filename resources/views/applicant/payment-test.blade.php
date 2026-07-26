@extends('layouts.app')

@section('title', 'Test Payment')

@section('content')
<div class="page-header">
    <h4>Test Payment Gateway</h4>
    <p class="text-muted">Use this page to test the payment system without real card payment</p>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-flask me-2"></i>Test Mode</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>How Test Payment Works:</h6>
                    <ol>
                        <li>Enter the test amount</li>
                        <li>Click "Process Test Payment"</li>
                        <li>The system will simulate a successful payment</li>
                        <li>You will be redirected to complete your application</li>
                    </ol>
                    <p class="mb-0"><strong>Note:</strong> This is for testing purposes only. No real money is involved.</p>
                </div>

                <form method="POST" action="{{ route('applicant.payment.test.process') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Payment Type</label>
                        <input type="text" class="form-control" value="Application Fee" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount (₦)</label>
                        @php
                        $paymentType = \App\Models\PaymentType::where('code', 'APP_FORM')->first();
                        $feeAmount = $paymentType ? $paymentType->amount : 5000;
                        @endphp
                        <input type="number" name="amount" class="form-control" value="{{ $feeAmount }}" required min="100">
                        <small class="text-muted">Default: ₦{{ number_format($feeAmount, 2) }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Test Reference</label>
                        <input type="text" class="form-control" value="TEST-{{ strtoupper(Str::random(8)) }}" readonly>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-check-circle me-2"></i>Process Test Payment
                    </button>
                </form>

                <hr>

                <div class="d-grid gap-2">
                    <a href="{{ route('applicant.payment.gateway') }}" class="btn btn-primary">
                        <i class="fas fa-credit-card me-2"></i>Go to Real Payment Page
                    </a>
                    <a href="{{ route('applicant.payment') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Payment Options
                    </a>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">Payment Gateways Available</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li><strong>Paystack</strong> - Card payments, USSD, Bank transfers</li>
                    <li><strong>Flutterwave</strong> - Card payments, Mobile money</li>
                    <li><strong>Stripe</strong> - International cards</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
