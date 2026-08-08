@extends('layouts.app')

@section('title', 'Payment Receipt')

@section('content')
<div class="container">
    @include('payments._receipt')

    <div class="d-flex justify-content-between mt-4 no-print">
        <a href="{{ route('bursar.payments') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Payments
        </a>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print Receipt
            </button>
            <a href="{{ route('payments.receipt.pdf', $payment) }}" class="btn btn-primary" target="_blank">
                <i class="fas fa-file-pdf me-2"></i>Download PDF
            </a>
        </div>
    </div>
</div>
@endsection