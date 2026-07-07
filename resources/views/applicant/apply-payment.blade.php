@extends('layouts.app')

@section('title', 'Application Fee Payment')

@section('content')
<div class="page-header">
    <h4>Application Fee Payment</h4>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Pay Application Fee</h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-4x text-warning mb-4"></i>

                <h5 class="mb-3">Application Form Fee</h5>

                <div class="alert alert-info">
                    <h2 class="mb-0">₦{{ number_format($feeAmount, 2) }}</h2>
                </div>

                <p class="text-muted">
                    Please pay the application fee to proceed with your admission application.
                </p>

                <hr>

                <form method="POST" action="{{ route('applicant.apply.fee') }}">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-lg w-100">
                        <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                    </button>
                </form>

                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Payment is processed securely. You will receive a confirmation once payment is successful.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection