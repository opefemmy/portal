@extends('layouts.app')

@section('title', 'Hospital Payment History')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm" style="max-width: 900px; margin: 0 auto;">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0">
                <i class="fas fa-hospital me-2"></i>Hospital Payment History
            </h4>
        </div>
        <div class="card-body">
            {{--
                Public "look up your receipts" page. The hospital public
                payment flow has no login — patients pay with just their
                phone number. To recover a lost receipt URL we let them
                re-enter their phone and list the last 10 completed
                payments on the system. Each row links to the existing
                public receipt route (hospital-payment.receipt).
            --}}
            <form method="get" action="{{ url()->current() }}" class="mb-4">
                <div class="row g-2 align-items-end">
                    <div class="col-md-9">
                        <label for="phone" class="form-label small text-muted mb-1">
                            Enter the phone number you used at payment time
                        </label>
                        <input type="text"
                               name="phone"
                               id="phone"
                               value="{{ $phone }}"
                               placeholder="e.g. 08012345678"
                               class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-search me-1"></i>Look Up
                        </button>
                    </div>
                </div>
            </form>

            @if($phone === '')
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-phone fa-3x mb-3"></i>
                    <p class="mb-0">Enter your phone number above to view your recent payments.</p>
                </div>
            @elseif($payments->isEmpty())
                <div class="text-center py-4">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">
                        No completed payments found for
                        <strong>{{ $phone }}</strong>.
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Service</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                <tr>
                                    <td>
                                        {{ optional($payment->payment_date)->format('d M Y') ?: '—' }}
                                    </td>
                                    <td><code>{{ $payment->payment_ref }}</code></td>
                                    <td>{{ $payment->service_name ?: '—' }}</td>
                                    <td class="text-end fw-semibold">
                                        ₦{{ number_format((float) $payment->total_amount, 2) }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('hospital-payment.receipt', $payment) }}"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-danger"
                                           title="View / print receipt">
                                            <i class="fas fa-receipt me-1"></i>Receipt
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mt-3 mb-0">
                    Showing the {{ $payments->count() }} most recent completed payments
                    for <strong>{{ $phone }}</strong>.
                </p>
            @endif
        </div>
    </div>
</div>
@endsection