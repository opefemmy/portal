@extends('layouts.app')

@section('title', 'Payment Management')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Payment Management</h4>
    <div>
        <a href="{{ route('bursar.reports') }}" class="btn btn-outline-primary">
            <i class="fas fa-chart-bar me-2"></i>Reports
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Matric Number</th>
                        <th>Student Name</th>
                        <th>Fee Type</th>
                        <th>Amount</th>
                        <th>Payment Reference</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->student->matric_number ?? 'N/A' }}</td>
                        <td>{{ $payment->student->user->name ?? 'N/A' }}</td>
                        <td>{{ $payment->fee->name ?? 'N/A' }}</td>
                        <td>{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->reference ?? 'N/A' }}</td>
                        <td>
                            @if($payment->status === 'completed')
                                <span class="badge bg-success">Verified</span>
                            @elseif($payment->status === 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @else
                                <span class="badge bg-danger">Failed</span>
                            @endif
                        </td>
                        <td>{{ $payment->created_at->format('d M Y') }}</td>
                        <td>
                            @if($payment->status !== 'completed')
                            <a href="{{ route('bursar.payments.verify', $payment) }}"
                               class="btn btn-sm btn-outline-success"
                               data-bs-toggle="tooltip"
                               title="Verify this payment"
                               onclick="return confirm('Verify this payment?')">
                                <i class="fas fa-check"></i>
                            </a>
                            @endif
                            <a href="{{ route('student.payments.receipt', $payment) }}"
                               class="btn btn-sm btn-outline-primary"
                               data-bs-toggle="tooltip"
                               title="View Receipt">
                                <i class="fas fa-receipt"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No payments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection