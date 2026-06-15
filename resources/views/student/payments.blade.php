@extends('layouts.app')

@section('title', 'My Payments')

@section('content')
<div class="page-header">
    <h4>My Payments</h4>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Required Fees</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
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
                            $paid = $payments->where('fee_id', $fee->id)->where('status', 'completed')->first();
                            @endphp
                            <tr>
                                <td>{{ $fee->name }}</td>
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
                                    <a href="{{ route('student.payments.pay', $fee) }}" class="btn btn-sm btn-primary">Pay Now</a>
                                    @else
                                    <a href="{{ route('student.payments.receipt', $paid) }}" class="btn btn-sm btn-outline-primary">Receipt</a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No fees configured.</td>
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
    <div class="card-header">
        <h5>Payment History</h5>
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
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->reference }}</td>
                        <td>{{ $payment->fee->name }}</td>
                        <td>₦{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->created_at->format('d M Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $payment->status === 'completed' ? 'success' : 'warning' }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">No payment history.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection