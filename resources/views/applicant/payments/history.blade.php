@extends('layouts.app')

@section('title', 'Transaction History')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-receipt me-2"></i>Transaction History</h4>
    <a href="{{ route('applicant.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

<div class="card">
    <div class="card-header bg-white">
        <div class="row align-items-center">
            <div class="col-md-6">
                <strong>{{ $applicant->full_name }}</strong><br>
                <small class="text-muted">{{ $applicant->application_number }}</small>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="text-muted">Application #</span>
                <strong>{{ $applicant->application_number }}</strong>
            </div>
        </div>
    </div>

    <div class="card-body">
        @if($history->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                <h5>No transactions yet</h5>
                <p class="text-muted">Your payments will appear here once you pay your fees.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Purpose</th>
                            <th>Channel</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Payer</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $row)
                            <tr>
                                <td>
                                    @if($row['paid_at'])
                                        {{ $row['paid_at'] instanceof \Illuminate\Support\Carbon ? $row['paid_at']->format('d M Y, H:i') : \Illuminate\Support\Carbon::parse($row['paid_at'])->format('d M Y, H:i') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td><code>{{ $row['reference'] }}</code></td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        {{ ucfirst(str_replace('_', ' ', $row['purpose'])) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-uppercase small">
                                        {{ ucfirst(str_replace('_', ' ', $row['channel'])) }}
                                    </span>
                                    @if($row['source'] === 'manual')
                                        <span class="badge bg-info ms-1" title="Validated bank transfer">Manual</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">₦{{ number_format((float) $row['amount'], 2) }}</td>
                                <td>
                                    {{--
                                        Status badges now distinguish
                                        pending / failed / cancelled in
                                        addition to completed — the
                                        Applicant::transactionHistory
                                        filter was relaxed so all rows
                                        reach the view, including the
                                        ones the user needs to requery.
                                    --}}
                                    @php
                                        $badgeClass = match ($row['status']) {
                                            'completed', 'verified' => 'success',
                                            'pending'   => 'warning',
                                            'failed'    => 'danger',
                                            'cancelled' => 'secondary',
                                            default     => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeClass }}">
                                        {{ ucfirst($row['status']) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $row['payer_name'] ?? '—' }}</small>
                                </td>
                                <td class="text-end">
                                    {{--
                                        Action affordances:
                                          - Completed / verified → Receipt
                                            button (existing affordance,
                                            goes to the applicant-side
                                            receipt route).
                                          - Pending / failed AND an
                                            applicant-purpose is present →
                                            Retry button (the new affordance,
                                            hits applicant.payment.retry to
                                            reuse the open row and re-init
                                            the gateway with a fresh
                                            reference). Available alongside
                                            Requery — Retry re-runs the whole
                                            payment, Requery only re-checks
                                            status of the same attempt.
                                          - Pending / failed AND a payment_id
                                            is present but no applicant-purpose
                                            (legacy rows) → Requery button.
                                          - Manual bank-transfer rows have
                                            no payment_id and can't be
                                            requeried — they only get a
                                            Receipt button when completed,
                                            or a dash otherwise.
                                    --}}
                                    @if(in_array($row['status'], ['completed', 'verified'], true) && !empty($row['receipt_url']))
                                        <a href="{{ $row['receipt_url'] }}" target="_blank"
                                           class="btn btn-sm btn-outline-success"
                                           title="View / print receipt">
                                            <i class="fas fa-receipt me-1"></i>Receipt
                                        </a>
                                    @elseif(in_array($row['status'], ['pending', 'failed'], true)
                                            && !empty($row['purpose'])
                                            && in_array($row['purpose'], ['application', 'acceptance', 'school_fee', 'compulsory'], true))
                                        <form method="POST"
                                              action="{{ route('applicant.payment.retry', ['purpose' => $row['purpose']]) }}"
                                              class="d-inline">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Retry this payment with a fresh gateway reference">
                                                <i class="fas fa-redo me-1"></i>Retry
                                            </button>
                                        </form>
                                        @if(!empty($row['payment_id']) && $row['source'] === 'online')
                                            <form method="POST"
                                                  action="{{ route('payments.requery', ['payment' => $row['payment_id']]) }}"
                                                  class="d-inline ms-1"
                                                  data-requery-form>
                                                @csrf
                                                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-warning"
                                                        title="Requery this payment with the gateway"
                                                        onclick="return confirm('Requery this payment? We will recheck the status with the gateway.')">
                                                    <i class="fas fa-sync me-1"></i>Requery
                                                </button>
                                            </form>
                                        @endif
                                    @elseif(!empty($row['payment_id']) && !empty($row['source']) && $row['source'] === 'online')
                                        <form method="POST"
                                              action="{{ route('payments.requery', ['payment' => $row['payment_id']]) }}"
                                              class="d-inline"
                                              data-requery-form>
                                            @csrf
                                            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-warning"
                                                    title="Requery this payment with the gateway"
                                                    onclick="return confirm('Requery this payment? We will recheck the status with the gateway.')">
                                                <i class="fas fa-sync me-1"></i>Requery
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-semibold">Total paid</td>
                            <td class="text-end fw-semibold">
                                {{-- Only settled rows count toward "Total paid" now that the
                                     view also shows pending / failed rows. --}}
                                ₦{{ number_format((float) $history->whereIn('status', ['completed', 'verified'])->sum('amount'), 2) }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
