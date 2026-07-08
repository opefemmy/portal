@extends('layouts.app')

@section('title', 'My Payments')

@section('content')
<div class="page-header">
    <h4>My Payments</h4>
    @if(isset($error))
    <p class="text-danger">{{ $error }}</p>
    @endif
</div>

{{-- Payment Gateway Info --}}
@if(isset($gateway) && $gateway)
<div class="alert alert-info mb-4">
    <i class="fas fa-credit-card me-2"></i>
    Payments are processed via <strong>{{ ucfirst($gateway->provider) }}</strong>
    @if($gateway->is_test_mode)
    <span class="badge bg-warning">Test Mode</span>
    @endif
</div>
@endif

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Required Fees</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fees as $fee)
                            @php
                            $paid = $payments->where('fee_id', $fee->id)->where('status', 'verified')->first();
                            @endphp
                            <tr>
                                <td><strong>{{ $fee->name }}</strong></td>
                                <td>₦{{ number_format($fee->amount, 2) }}</td>
                                <td>{{ $fee->due_date?->format('d M Y') ?? 'N/A' }}</td>
                                <td>
                                    @if($paid)
                                    <span class="badge bg-success">Paid</span>
                                    @else
                                    <span class="badge bg-warning">Unpaid</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$paid)
                                    <a href="{{ route('student.payments.pay', $fee) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-credit-card me-1"></i>Pay Now
                                    </a>
                                    @else
                                    <a href="{{ route('student.payments.receipt', $paid) }}" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-receipt me-1"></i>Receipt
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No fees configured for your session.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Payment History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Fee Type</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Gateway</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td><small>{{ $payment->reference }}</small></td>
                        <td>{{ $payment->fee->name ?? 'N/A' }}</td>
                        <td>₦{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->created_at->format('d M Y, h:i A') }}</td>
                        <td>{{ ucfirst($payment->gateway) }}</td>
                        <td>
                            @if($payment->status === 'verified')
                            <span class="badge bg-success">Verified</span>
                            @elseif($payment->status === 'pending')
                            <span class="badge bg-warning">Pending</span>
                            @elseif($payment->status === 'failed')
                            <span class="badge bg-danger">Failed</span>
                            @else
                            <span class="badge bg-secondary">{{ $payment->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->status === 'verified')
                            <a href="{{ route('student.payments.receipt', $payment) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-print"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-inbox me-2"></i>No payment history.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection