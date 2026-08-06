@extends('layouts.app')

@php
    // Same labelling rules as the live gateway view so the test page
    // is consistent with what the user sees in production. Prefer the
    // catalogue's `name` field so admin-defined purposes display exactly
    // what the admin typed (e.g. "Acceptance Fee", "Late Registration
    // Fee"). Fall back to display_label for backwards compat with rows
    // that have no name set.
    $paymentType = \App\Models\PaymentType::findByPurpose($purpose ?? \App\Models\PaymentType::PURPOSE_APPLICATION);
    $purposeLabel = $paymentType?->name ?: ($paymentType?->display_label ?? 'Application Fee');
    $title = "Test Payment — {$purposeLabel}";
@endphp

@section('title', $title)

@section('content')
<div class="page-header">
    <h4>Test Payment Gateway</h4>
    <p class="text-muted">Use this page to test the payment system without real card payment</p>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-flask me-2"></i>Test Mode — {{ $purposeLabel }}</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>How Test Payment Works:</h6>
                    <ol>
                        <li>Confirm the payment type and amount below</li>
                        <li>Click "Process Test Payment"</li>
                        <li>The system will simulate a successful payment (no real money)</li>
                        <li>You will be redirected according to the fee you just paid</li>
                    </ol>
                    <p class="mb-0"><strong>Note:</strong> This is for testing purposes only.</p>
                </div>

                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('applicant.payment.test.process') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Payment Type</label>
                        <input type="text" class="form-control" value="{{ $purposeLabel }}" readonly>
                        <input type="hidden" name="purpose" value="{{ $purpose }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount (₦)</label>
                        <input type="number" name="amount" class="form-control"
                               value="{{ old('amount', $feeAmount ?: 5000) }}"
                               required min="100" step="0.01">
                        <small class="text-muted">Default for {{ $purposeLabel }}: ₦{{ number_format($feeAmount ?: 0, 2) }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Test Reference (auto-generated)</label>
                        <input type="text" class="form-control" value="TEST-{{ strtoupper(\Illuminate\Support\Str::random(8)) }}" readonly>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-check-circle me-2"></i>Process Test Payment
                    </button>
                </form>

                <hr>

                {{-- Quick-switch to a different fee. Pulled from the catalogue
                     so any admin-defined PaymentType shows up automatically —
                     no template edit needed when they add a new fee. --}}
                <div class="d-flex gap-2 flex-wrap">
                    @foreach(\App\Services\ApplicantPaymentService::getApplicantPaymentTypesStatic() as $idx => $type)
                        @php $variants = ['btn-outline-primary', 'btn-outline-success', 'btn-outline-info', 'btn-outline-warning', 'btn-outline-secondary']; @endphp
                        <a href="{{ route('applicant.payment.test', ['purpose' => $type->purpose]) }}"
                           class="btn btn-sm flex-fill {{ $variants[$idx % count($variants)] }}">
                            Test {{ $type->display_label }}
                        </a>
                    @endforeach
                </div>

                <hr>

                <div class="d-grid gap-2">
                    <a href="{{ route('applicant.payment.gateway', ['purpose' => $purpose]) }}" class="btn btn-primary">
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
                    <li><strong>XpressPayment</strong> - Card payments, USSD, Bank transfers</li>
                    <li><strong>Paystack</strong> - Card payments, USSD, Bank transfers</li>
                    <li><strong>Flutterwave</strong> - Card payments, Mobile money</li>
                    <li><strong>Remita</strong> - Direct debit, Bank transfers</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
