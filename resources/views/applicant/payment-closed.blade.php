@extends('layouts.app')

@section('title', 'Payment Closed')

@section('content')
<div class="page-header">
    <h4>Payment Closed</h4>
</div>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
        <h3>Payment Window Closed</h3>
        @if(isset($applicant))
            <p class="lead">Dear <strong>{{ $applicant->first_name ?? 'Applicant' }} {{ $applicant->last_name ?? '' }}</strong>,</p>
        @else
            <p class="lead">Dear Applicant,</p>
        @endif
        <p>The payment window for the current application cycle has been closed.</p>
        @if(isset($payment))
            <div class="alert alert-warning mt-3">
                <p class="mb-1">Payment Reference: <strong>{{ $payment->reference ?? $payment->rrr ?? 'N/A' }}</strong></p>
                <p class="mb-1">Amount: <strong>NGN {{ number_format($payment->amount ?? 0, 2) }}</strong></p>
            </div>
        @endif
        <p>If you have already initiated a payment, kindly allow up to 24 hours for confirmation.</p>
        <div class="mt-4">
            <a href="{{ url('/') }}" class="btn btn-primary">Back to Home</a>
            <a href="{{ route('applicant.status') }}" class="btn btn-outline-secondary">Check Status</a>
        </div>
    </div>
</div>
@endsection
