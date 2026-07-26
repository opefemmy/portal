@extends('layouts.app')

@section('title', 'My Payments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-credit-card me-2"></i>My Payments</h4>
    <a href="{{ route('patient.dashboard') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>

@forelse($payments as $payment)
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <div>
                <h5>{{ $payment->service_name }}</h5>
                <p class="mb-1">Ref: <strong>{{ $payment->payment_ref }}</p>
                <small class="text-muted">{{ $payment->created_at->format('d M Y, h:i A') }}</small>
            </div>
            <div class="text-end">
                <h4 class="text-{{ $payment->status == 'completed' ? 'success' : 'warning' }}">₦{{ number_format($payment->total_amount) }}</h4>
                <span class="badge bg-{{ $payment->status == 'completed' ? 'success' : 'warning' }}">{{ ucfirst($payment->status) }}</span>
            </div>
        </div>
    </div>
</div>
@empty
<div class="alert alert-info">No payments found.</div>
@endforelse

{{ $payments->links() }}
@endsection
