@extends('layouts.app')

@section('title', 'Test Payment Simulator')

@section('content')
@php
    /**
     * Shared test-payment picker. Used by the applicant, student,
     * bursar, registrar portals via /payment/test/{audience}. Lists
     * every PaymentType for the viewer's audience (or "both" for staff
     * roles) and lets them fire a simulated payment that hits the same
     * markCompleted() code path the live Paystack callback does.
     */
    $audienceLabel = match($audience ?? 'applicant') {
        'student' => 'Student',
        'both'    => 'All (Applicant + Student)',
        default   => 'Applicant',
    };
@endphp

<div class="page-header">
    <h4><i class="fas fa-vial me-2"></i>Test Payment Simulator</h4>
    <p class="text-muted mb-0">
        Audience: <strong>{{ $audienceLabel }}</strong>.
        This page is <strong>disabled in production</strong> — every
        call is audit-logged. Use it to verify payment flows without
        a real card.
    </p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-flask me-2"></i>Pick a fee to simulate
                </h5>
            </div>
            <div class="card-body">
                @if($types->isEmpty())
                    <div class="alert alert-info mb-0">
                        No active <code>PaymentType</code> rows are visible to this
                        audience yet. Create one at
                        <a href="{{ route('admin.payment-types.index') }}">/admin/payment-types</a>
                        or re-run <code>php artisan db:seed --class=PaymentTypeSeeder</code>.
                    </div>
                @else
                    <form method="POST"
                          action="{{ match($audience ?? 'applicant') {
                              'student'   => route('student.payment.test.process.student'),
                              'both'      => str_starts_with(request()->path(), 'bursar')
                                                ? route('bursar.payment.test.process')
                                                : route('registrar.payment.test.process'),
                              default     => route('applicant.test.process.applicant'),
                          } }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Payment type</label>
                            <select name="payment_type_id" class="form-select" required>
                                <option value="">Select…</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}">
                                        {{ $type->name }}
                                        ({{ $type->code }})
                                        — ₦{{ number_format($type->amount) }}
                                        — {{ $type->display_label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_type_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        @if(($audience ?? 'applicant') !== 'applicant' && isset($fees) && $fees->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-receipt me-1"></i>Link to a Fee (optional)
                                </label>
                                <select name="fee_id" id="fee_id" class="form-select">
                                    <option value="">— None (PaymentType only) —</option>
                                    @foreach($fees as $fee)
                                        @php
                                            // Surface every per-category amount so the
                                            // user can see what the live Paystack path
                                            // would charge. Indigene / non-indigene
                                            // columns can each be null, in which case
                                            // we fall back to the legacy `amount` column.
                                            $indigene = $fee->indigene_amount !== null
                                                ? (float) $fee->indigene_amount
                                                : (float) $fee->amount;
                                            $nonIndigene = $fee->non_indigene_amount !== null
                                                ? (float) $fee->non_indigene_amount
                                                : (float) $fee->amount;
                                            $totalWithPortal = $nonIndigene + (float) $fee->portal_charge;
                                        @endphp
                                        <option value="{{ $fee->id }}"
                                                data-indigene="{{ $indigene }}"
                                                data-non-indigene="{{ $nonIndigene }}"
                                                data-portal="{{ (float) $fee->portal_charge }}">
                                            {{ $fee->name }}
                                            — ₦{{ number_format($nonIndigene, 2) }}
                                            (non-indigene)
                                            @if((float) $fee->portal_charge > 0)
                                                + ₦{{ number_format((float) $fee->portal_charge, 2) }} portal
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">
                                    Linking to a Fee makes the simulated payment count toward
                                    <code>SchoolFeeCalculator::totalPercentPaid</code> and
                                    unlock the exam-clearance gate. The amount below auto-fills
                                    to the Fee's price + portal charge.
                                </small>
                                @error('fee_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Amount (₦)</label>
                            <input type="number"
                                   name="amount"
                                   id="amount_input"
                                   class="form-control"
                                   min="1"
                                   step="0.01"
                                   value="5000"
                                   required>
                            <small class="text-muted">
                                Defaults to 5,000. Set this to whatever the catalogue row
                                lists — when you pick a Fee above, the amount auto-fills
                                to that fee's price + portal charge so the simulated row
                                matches the live Paystack amount.
                            </small>
                            @error('amount')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-bolt me-2"></i>Process Test Payment
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>What this does</h5>
            </div>
            <div class="card-body small">
                <ol class="mb-2">
                    <li>Writes a row to <code>payments</code> with
                        <code>gateway='test'</code>, <code>status='completed'</code></li>
                    <li>If you picked a Fee, the row is linked via <code>fee_id</code>
                        so exam-clearance / course-registration gates count it</li>
                    <li>Runs <code>ApplicantPaymentService::markCompleted()</code> —
                        same code path as a real Paystack callback</li>
                    <li>For migration triggers (Compulsory Fee, School Fees)
                        the applicant→student migration runs</li>
                    <li>An audit row is written to <code>activity_logs</code></li>
                </ol>
                <p class="mb-0 text-muted">
                    Test rows are easy to spot: <code>payments.gateway='test'</code>.
                    They're safe to keep (the live dashboard filters them out) or
                    delete via tinker.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * Auto-fill the Amount input when a Fee is picked.
 *
 * Without this, the user has to know that HIM 100L is ₦20 + portal charge,
 * type the number, and hope they got it right. Picking the Fee row fills
 * the amount to priceFor(non_indigene) + portal_charge — matching what
 * the live Paystack path charges the user.
 */
document.addEventListener('DOMContentLoaded', function () {
    const feeSelect = document.getElementById('fee_id');
    const amountInput = document.getElementById('amount_input');
    if (!feeSelect || !amountInput) {
        return;
    }

    feeSelect.addEventListener('change', function () {
        const option = feeSelect.options[feeSelect.selectedIndex];
        if (!option || !option.value) {
            // User picked "— None —" — leave the amount as the user typed it.
            return;
        }
        const nonIndigene = parseFloat(option.dataset.nonIndigene || '0') || 0;
        const portal = parseFloat(option.dataset.portal || '0') || 0;
        const total = nonIndigene + portal;
        amountInput.value = total.toFixed(2);
    });
});
</script>
@endpush
