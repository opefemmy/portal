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
                                    <span class="badge bg-{{ $row['status'] === 'completed' ? 'success' : 'warning' }}">
                                        {{ ucfirst($row['status']) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $row['payer_name'] ?? '—' }}</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-semibold">Total paid</td>
                            <td class="text-end fw-semibold">
                                ₦{{ number_format((float) $history->sum('amount'), 2) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
