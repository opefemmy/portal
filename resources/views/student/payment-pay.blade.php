@extends('layouts.app')

@section('title', 'Make Payment')

@section('content')
<div class="page-header">
    <h4>Payment Details</h4>
</div>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">{{ $fee->name }}</h5>
            </div>
            <div class="card-body">
                <table class="table mb-0">
                    <tr>
                        <td><strong>Fee Type:</strong></td>
                        <td>{{ $fee->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Session:</strong></td>
                        <td>{{ $fee->session->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Your Category:</strong></td>
                        <td>
                            <span class="badge bg-{{ $category === 'indigene' ? 'success' : 'secondary' }}">
                                {{ ucfirst(str_replace('_', '-', $category)) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Full Fee:</strong></td>
                        <td>₦{{ number_format($fee->priceFor($category), 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Portal Charge:</strong></td>
                        <td>₦{{ number_format((float) $fee->portal_charge, 2) }}</td>
                    </tr>
                </table>

                <hr>

                <form method="POST" action="{{ route('student.payments.initiate', $fee) }}">
                    @csrf

                    <label class="form-label fw-bold">Choose how to pay</label>

                    @if(empty($percents))
                        <div class="alert alert-info">
                            You have already completed the required installments for this fee.
                        </div>
                    @else
                        <div class="list-group mb-3">
                            @foreach($percents as $percent)
                                @php
                                    $subtitle = match($percent) {
                                        100 => 'Covers both semesters. Enables exam clearance.',
                                        60  => 'First semester only. 40% balance due later.',
                                        40  => 'Second-semester balance after the 60% first installment.',
                                        default => '',
                                    };
                                @endphp
                                <label class="list-group-item d-flex align-items-start gap-2">
                                    <input type="radio" name="percent" value="{{ $percent }}"
                                           class="form-check-input mt-1"
                                           {{ $loop->first ? 'checked' : '' }}>
                                    <div>
                                        <div class="fw-bold">
                                            {{ $percent }}% —
                                            ₦{{ number_format($pricing[$percent], 2) }}
                                            @if($percent === 100 && (float) $fee->portal_charge > 0)
                                                <small class="text-muted">(includes portal charge)</small>
                                            @endif
                                        </div>
                                        <small class="text-muted">{{ $subtitle }}</small>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary btn-lg w-100"
                            {{ empty($percents) ? 'disabled' : '' }}>
                        <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                    </button>
                </form>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-lock me-1"></i>Secure payment powered by {{ ucfirst($gateway->provider ?? 'Payment Gateway') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection