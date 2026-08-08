@extends('layouts.app')

@section('title', 'Payment Receipt — ' . config('app.name'))

@php
    // Resolve a few display fields the template needs from both row types.
    // For online Payment: $payment->amount / $payment->total_amount /
    //   payment_purpose / reference / payment_method / payer_* / created_at.
    // For ExternalPayment: $payment->amount / payment_status /
    //   transaction_id / payment_channel / applicant_name / email /
    //   payment_date / description / paymentType.
    $institution        = \App\Models\SystemSetting::getInstitutionName();
    $institutionAddress = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_ADDRESS);

    if ($isExternal) {
        /** @var \App\Models\ExternalPayment $payment */
        $reference     = $payment->transaction_id;
        $amount        = (float) $payment->amount;
        $purpose       = $payment->paymentType?->purpose
            ?: $payment->description
            ?: 'other';
        $purposeLabel  = $payment->paymentType?->name
            ?: ucfirst(str_replace('_', ' ', (string) $purpose));
        $channelLabel  = ucfirst(str_replace('_', ' ', (string) ($payment->payment_channel ?: 'bank_transfer')));
        $statusLabel   = ucfirst((string) $payment->payment_status);
        $statusClass   = $payment->payment_status === 'completed' ? 'success' : 'warning';
        $paidAt        = $payment->payment_date ?: $payment->validated_at;
        $payerName     = $payment->applicant_name;
        $payerEmail    = $payment->email;
    } else {
        /** @var \App\Models\Payment $payment */
        $reference     = $payment->reference ?: $payment->payment_ref ?: $payment->transaction_id;
        $amount        = (float) ($payment->total_amount ?: $payment->amount);
        $purpose       = $payment->payment_purpose ?: $payment->fee_type;
        $purposeLabel  = $feeTypeLabel ?: (
            $payment->payment_purpose
                ? ucfirst(str_replace('_', ' ', $payment->payment_purpose))
                : ($payment->fee->name ?? 'Payment')
        );
        $channelLabel  = ucfirst(str_replace('_', ' ', (string) ($payment->payment_method ?: $payment->gateway ?: 'online')));
        $statusLabel   = ucfirst((string) $payment->status);
        $statusClass   = $payment->status === 'completed' ? 'success' : 'warning';
        $paidAt        = $payment->payment_date ?: $payment->created_at;
        $payerName     = $payment->payer_name;
        $payerEmail    = $payment->payer_email;
    }

    // Watermark text — same anti-forgery pattern as the student/bursar
    // receipt partials. The applicant side has no matric, so we use the
    // application number instead so the watermark is still useful.
    $watermarkName    = $payerName ?: $applicant->full_name;
    $watermarkMatric  = $payerMatric ?: ($applicant->application_number ?? '—');
    $watermarkPurpose = $purposeLabel;
@endphp

@section('content')
<style>
    .receipt {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    .receipt-header {
        text-align: center;
        border-bottom: 2px solid #247D57;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
    .receipt-header h2 {
        color: #247D57;
        margin-bottom: 5px;
    }
    .receipt-header .receipt-address {
        font-size: 0.9rem;
        color: #555;
        margin: 0 0 8px;
    }
    .receipt-details {
        margin: 20px 0;
        position: relative;
        z-index: 1;
    }
    .receipt-details table {
        width: 100%;
    }
    .receipt-details th {
        text-align: left;
        padding: 10px;
        background: #f8f9fa;
        width: 40%;
    }
    .receipt-details td {
        padding: 10px;
    }
    .total-amount {
        font-size: 1.5rem;
        font-weight: bold;
        color: #247D57;
        background: #e8f5e9;
        padding: 15px;
        border-radius: 5px;
        text-align: center;
    }
    .status-badge {
        padding: 5px 15px;
        border-radius: 20px;
        display: inline-block;
    }
    .status-badge.bg-success { background: #28a745; color: white; }
    .status-badge.bg-warning { background: #ffc107; color: #333; }

    /* Watermark layer — sits behind the receipt body. Same pattern as
       payments/_receipt.blade.php so the applicant-side receipt looks
       identical to the student/bursar one. */
    .receipt-watermark {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
    }
    .receipt-watermark-logo {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        width: 540px;
        max-width: 90%;
        opacity: 0.06;
    }
    .receipt-watermark-text {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 28px;
        font-weight: 700;
        letter-spacing: 4px;
        text-transform: uppercase;
        color: #000;
        opacity: 0.07;
        text-align: center;
        line-height: 1.4;
        white-space: nowrap;
    }

    @media print {
        body { background: white; }
        .receipt { box-shadow: none; }
        .no-print { display: none; }
        .receipt-watermark-logo { opacity: 0.10; }
        .receipt-watermark-text { opacity: 0.12; }
    }
</style>

<div class="container py-4">
    <div class="receipt">
        {{-- Watermark layer (logo + payer name + matric/app-number +
             payment purpose, repeated diagonally behind everything). --}}
        <div class="receipt-watermark" aria-hidden="true">
            @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="" class="receipt-watermark-logo">
            @endif
            <div class="receipt-watermark-text">
                <div>{{ $watermarkName }}</div>
                <div>{{ $watermarkMatric }}</div>
                <div>{{ $watermarkPurpose }}</div>
            </div>
        </div>

        <div class="receipt-header">
            {{-- Brand logo (small) — same resolution chain as the
                 shared partial, but inline here so we don't break the
                 polymorphic $isExternal branch below. --}}
            @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="{{ $institution }} logo"
                     style="max-height: 80px; max-width: 110px; object-fit: contain; margin-bottom: 8px;">
            @endif
            <h2>{{ $institution }}</h2>
            @if(!empty($institutionAddress))
                <p class="receipt-address">{{ $institutionAddress }}</p>
            @endif
            <p>Payment Receipt</p>
        </div>

        <div class="receipt-details">
            <table class="table table-bordered">
                <tr>
                    <th>Payment Reference</th>
                    <td><code>{{ $reference }}</code></td>
                </tr>
                <tr>
                    <th>Payment Date</th>
                    <td>
                        @if($paidAt instanceof \Illuminate\Support\Carbon)
                            {{ $paidAt->format('d M Y, h:i A') }}
                        @elseif($paidAt)
                            {{ \Illuminate\Support\Carbon::parse($paidAt)->format('d M Y, h:i A') }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="status-badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                </tr>
                <tr>
                    <th>Source</th>
                    <td>
                        @if($isExternal)
                            <span class="badge bg-info">Bank Transfer (validated)</span>
                        @else
                            <span class="badge bg-primary">Online Payment</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="receipt-details">
            <h5>Payer Information</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Applicant Name</th>
                    <td>{{ $payerName ?? $applicant->full_name }}</td>
                </tr>
                <tr>
                    <th>Application Number</th>
                    <td><code>{{ $applicant->application_number }}</code></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $payerEmail ?: ($applicant->user->email ?? '—') }}</td>
                </tr>
                @unless($isExternal)
                    <tr>
                        <th>Phone</th>
                        <td>{{ $payment->payer_phone ?? ($applicant->phone ?? '—') }}</td>
                    </tr>
                @endunless
            </table>
        </div>

        <div class="receipt-details">
            <h5>Payment Details</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Payment Purpose</th>
                    <td>{{ $purposeLabel }}</td>
                </tr>
                <tr>
                    <th>Channel</th>
                    <td>{{ $channelLabel }}</td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td>₦{{ number_format($amount, 2) }}</td>
                </tr>
                @unless($isExternal)
                    @if(($payment->portal_charge ?? 0) > 0)
                        <tr>
                            <th>Portal Charge</th>
                            <td>₦{{ number_format($payment->portal_charge, 2) }}</td>
                        </tr>
                    @endif
                @endunless
            </table>
        </div>

        <div class="total-amount mt-4">
            Total Paid: ₦{{ number_format($amount, 2) }}
        </div>

        <div class="text-center mt-4 no-print d-flex justify-content-center flex-wrap gap-2">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print Receipt
            </button>
            @unless($isExternal)
                {{-- PDF download only for online Payment rows — ExternalPayment
                     rows don't have the polymorphic receipt-pdf endpoint. --}}
                <a href="{{ route('payments.receipt.pdf', $payment) }}" class="btn btn-outline-primary" target="_blank">
                    <i class="fas fa-file-pdf me-2"></i>Download PDF
                </a>
            @endunless
            <a href="{{ route('applicant.payments.history') }}" class="btn btn-outline-secondary">
                <i class="fas fa-list me-2"></i>Back to History
            </a>
        </div>
    </div>
</div>
@endsection