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
                        <div class="mb-3">
                            <label class="form-label">Amount (₦)</label>
                            <input type="number"
                                   name="amount"
                                   class="form-control"
                                   min="100"
                                   step="0.01"
                                   value="5000"
                                   required>
                            <small class="text-muted">
                                Defaults to 5,000 — set this to whatever the
                                catalogue row lists so the simulated row matches.
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
