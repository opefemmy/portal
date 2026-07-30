@extends('layouts.app')

@section('title', 'Application Fee Payment')

@section('content')
<div class="page-header">
    <h4>Application Fee Payment</h4>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Application Fee Payment</h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-4x text-primary mb-3"></i>

                @if(isset($paymentType) && $paymentType)
                <h4>Application Fee: ₦{{ number_format($paymentType->amount, 2) }}</h4>
                @php $amount = $paymentType->amount; @endphp
                @else
                <h4>Application Fee: ₦{{ number_format($feeAmount ?? 5000, 2) }}</h4>
                @php $amount = $feeAmount ?? 5000; @endphp
                @endif

                <p class="text-muted">Complete your payment to proceed with application</p>

                <hr>

                <button type="button" class="btn btn-primary btn-lg w-100" data-bs-toggle="modal" data-bs-target="#paymentGatewayModal">
                    <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                </button>

                <hr>

                {{-- Validate Payment Button --}}
                <p class="text-muted mb-2">Already paid? Validate your payment below:</p>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#validatePaymentModal">
                    <i class="fas fa-check-circle me-2"></i>Validate Payment
                </button>
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

@include('components.payment-modal', [
    'payment' => (object)['id' => 1, 'payment_ref' => 'APP-' . time(), 'amount' => $amount ?? 5000],
    'amount' => $amount ?? 5000,
    'email' => auth()->user()->email ?? null,
    'name' => auth()->user()->name ?? 'Applicant',
    'description' => 'Application Fee'
])

{{-- Validate Payment Modal --}}
<div class="modal fade" id="validatePaymentModal" tabindex="-1" aria-labelledby="validatePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="validatePaymentModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Validate Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('applicant.payment.verify-external') }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        After making payment, enter your payment reference/transaction ID to validate.
                    </div>
                    <div class="mb-3">
                        <label class="form-label"> Payment Reference / Transaction ID <span class="text-danger">*</span></label>
                        <input type="text" name="payment_ref" class="form-control" placeholder="Enter your payment reference" required>
                        <small class="text-muted">The transaction ID you received after payment</small>
                    </div>
                    <input type="hidden" name="amount" value="{{ $amount ?? 5000 }}">
                    <input type="hidden" name="payment_date" value="{{ date('Y-m-d') }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Validate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
