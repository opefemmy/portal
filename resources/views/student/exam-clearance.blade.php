@extends('layouts.app')

@section('title', 'Exam Clearance')

@section('content')
<div class="page-header">
    <h4>Examination Clearance</h4>
    <p class="text-muted">
        {{ $student->user->name ?? 'Student' }}
        @if($session) — {{ $session->name }} session @endif
    </p>
</div>

<div class="card mb-4">
    <div class="card-body">
        @if($fullyPaid)
            <div class="alert alert-success mb-0">
                <i class="fas fa-check-circle me-2"></i>
                <strong>You are cleared for the examinations.</strong>
                All required school fees have been paid in full.
            </div>
        @else
            <div class="alert alert-warning mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                You are not yet cleared. Pay each required fee to 100% to unlock
                the printable clearance letter.
            </div>
        @endif
    </div>
</div>

@forelse($perFeeStatus as $row)
    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h5 class="mb-0">{{ $row['fee']->name }}</h5>
            @php
                $paid = $row['paid'];
                $badge = match(true) {
                    $paid >= 100 => 'success',
                    $paid >= 60  => 'warning',
                    default      => 'danger',
                };
            @endphp
            <span class="badge bg-{{ $badge }}">{{ $paid }}% paid</span>
        </div>
        <div class="card-body">
            <table class="table table-sm mb-2">
                <tr>
                    <th width="200">Fee Category</th>
                    <td>{{ ucfirst(str_replace('_', '-', $row['category'])) }}</td>
                </tr>
                <tr>
                    <th>Full Fee</th>
                    <td>₦{{ number_format($row['price'], 2) }}</td>
                </tr>
                <tr>
                    <th>Portal Charge</th>
                    <td>₦{{ number_format($row['portal'], 2) }}</td>
                </tr>
            </table>

            @if($row['payments']->count())
                <h6 class="mt-3">Payment History</h6>
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Installment</th>
                            <th>Percent</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($row['payments'] as $payment)
                            <tr>
                                <td><code>{{ $payment->reference }}</code></td>
                                <td>{{ ucfirst($payment->installment_label ?? $payment->installment ?? 'full') }}</td>
                                <td>{{ $payment->percent_paid }}%</td>
                                <td>₦{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ optional($payment->created_at)->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted mb-0">No payments recorded yet.</p>
            @endif
        </div>
    </div>
@empty
    <div class="alert alert-info">
        No required fees have been configured for your department/programme yet.
    </div>
@endforelse

<div class="d-flex justify-content-between mt-4">
    <a href="{{ route('student.payments') }}" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left me-1"></i>Back to Payments
    </a>

    @if($fullyPaid)
        <a href="{{ route('student.exam-clearance.print') }}" class="btn btn-success" target="_blank">
            <i class="fas fa-print me-1"></i>Print Exam Clearance
        </a>
    @else
        <button type="button" class="btn btn-success" disabled>
            <i class="fas fa-print me-1"></i>Print Exam Clearance (Locked)
        </button>
    @endif
</div>
@endsection