@extends('layouts.app')

@section('title', 'Make Payment')

@section('content')
<div class="page-header">
    <h4>Payment Details</h4>
</div>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">{{ $fee->name }}</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td><strong>Fee Type:</strong></td>
                        <td>{{ $fee->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Amount:</strong></td>
                        <td>₦{{ number_format($fee->amount, 2) }}</td>
                    </tr>
                    @if($fee->description)
                    <tr>
                        <td><strong>Description:</strong></td>
                        <td>{{ $fee->description }}</td>
                    </tr>
                    @endif
                </table>

                <hr>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    You will be redirected to <strong>{{ ucfirst($gateway->provider ?? 'payment gateway') }}</strong> to complete your payment.
                </div>

                <form method="POST" action="{{ route('student.payments.initiate', $fee) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                    </button>
                </form>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-lock me-1"></i>Secure payment powered by {{ ucfirst($gateway->provider ?? 'Payment Gateway') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection