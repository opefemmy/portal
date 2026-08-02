@extends('layouts.app')

@section('title', 'Payment Receipt')

@section('content')
<div class="container">
    <div class="card shadow-sm print-shadow-none">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h2 class="mb-1">Payment Receipt</h2>
                <p class="text-muted">Official Receipt</p>
            </div>

            <table class="table table-borderless mb-4">
                <tr>
                    <th width="35%">Receipt No:</th>
                    <td><code>{{ $payment->reference ?? $payment->payment_ref ?? 'N/A' }}</code></td>
                </tr>
                <tr>
                    <th>Date:</th>
                    <td>{{ optional($payment->created_at)->format('d M, Y h:i A') ?? ($payment->payment_date ?? 'N/A') }}</td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        @if($payment->status === 'completed' || $payment->status === 'verified')
                            <span class="badge bg-success">Verified</span>
                        @elseif($payment->status === 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @else
                            <span class="badge bg-danger">{{ ucfirst($payment->status ?? 'Unknown') }}</span>
                        @endif
                    </td>
                </tr>
            </table>

            <h5 class="mb-3">Payer Details</h5>
            <table class="table table-borderless mb-4">
                @if($payment->student)
                <tr>
                    <th width="35%">Matric Number:</th>
                    <td>{{ $payment->student->matric_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Name:</th>
                    <td>{{ $payment->student->user->name ?? $payment->payer_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td>{{ $payment->student->user->email ?? $payment->payer_email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Department:</th>
                    <td>{{ $payment->student->department->name ?? 'N/A' }}</td>
                </tr>
                @else
                <tr>
                    <th width="35%">Payer Name:</th>
                    <td>{{ $payment->payer_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td>{{ $payment->payer_email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Phone:</th>
                    <td>{{ $payment->payer_phone ?? 'N/A' }}</td>
                </tr>
                @endif
            </table>

            <h5 class="mb-3">Payment Details</h5>
            <table class="table table-borderless mb-4">
                <tr>
                    <th width="35%">Fee Type:</th>
                    <td>{{ $payment->fee->name ?? ($payment->payment_purpose ?? 'N/A') }}</td>
                </tr>
                <tr>
                    <th>Amount:</th>
                    <td><h4 class="text-success mb-0">₦{{ number_format($payment->amount, 2) }}</h4></td>
                </tr>
                <tr>
                    <th>Gateway:</th>
                    <td>{{ ucfirst($payment->gateway ?? $payment->payment_method ?? 'N/A') }}</td>
                </tr>
                @if($payment->portal_charge)
                <tr>
                    <th>Portal Charge:</th>
                    <td>₦{{ number_format($payment->portal_charge, 2) }}</td>
                </tr>
                <tr>
                    <th>Total Paid:</th>
                    <td>₦{{ number_format($payment->total_amount ?? ($payment->amount + $payment->portal_charge), 2) }}</td>
                </tr>
                @endif
                <tr>
                    <th>Transaction ID:</th>
                    <td><code>{{ $payment->transaction_id ?? 'N/A' }}</code></td>
                </tr>
            </table>

            <div class="alert alert-info no-print">
                <i class="fas fa-info-circle me-2"></i>
                This is a computer-generated receipt. Please retain for your records.
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between no-print">
            <a href="{{ route('bursar.payments') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Payments
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print Receipt
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .no-print, .main-header, .sidebar, .main-footer { display: none !important; }
    .print-shadow-none { box-shadow: none !important; border: none !important; }
}
</style>
@endpush
@endsection