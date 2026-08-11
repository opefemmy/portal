@extends('layouts.print')

@section('title', 'Receipt — ' . $payment->payment_ref)

@push('styles')
<style>
    /*
     * POS-style printable receipt.

     * Uses layouts.print (not layouts.app) so the portal chrome —
     * sidebar, topbar, header gradient, primary-button gradient, and
     * the .portal-page background image — is bypassed entirely. The
     * print stylesheet below also hard-pins the body background to
     * white in case any inherited rule from a parent layout still
     * tries to draw a wallpaper.
     *
     * Page size is 80mm auto for thermal-printer POS rolls; the
     * browser scales to A4/Letter if no POS printer is selected.
     */
    body {
        background: #fff !important;
        margin: 0;
        padding: 12px;
        font-family: 'Courier New', Courier, monospace;
        color: #000;
    }

    .receipt {
        width: 80mm;
        max-width: 100%;
        margin: 0 auto;
        font-size: 12px;
        line-height: 1.4;
    }

    .receipt header {
        text-align: center;
        border-bottom: 1px dashed #333;
        padding-bottom: 8px;
        margin-bottom: 10px;
    }
    .receipt header img {
        max-height: 50px;
        max-width: 60mm;
        margin: 0 auto 6px;
        display: block;
    }
    .receipt header h1 {
        font-size: 14px;
        font-weight: bold;
        margin: 0 0 2px;
    }
    .receipt header p {
        font-size: 11px;
        margin: 1px 0;
    }

    .receipt h3 {
        text-align: center;
        font-size: 13px;
        margin: 0 0 8px;
        letter-spacing: 1px;
    }
    .receipt h3.completed { color: #198754; }
    .receipt h3.pending   { color: #b8860b; }

    .receipt table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
    }
    .receipt th,
    .receipt td {
        text-align: left;
        padding: 2px 0;
        font-size: 12px;
        vertical-align: top;
    }
    .receipt th {
        width: 38%;
        font-weight: normal;
        color: #444;
    }
    .receipt td { font-weight: bold; }

    .receipt .totals td {
        font-weight: bold;
        border-top: 1px dashed #333;
        padding-top: 4px;
    }
    .receipt .totals .grand td {
        font-size: 13px;
        border-top: 1px solid #000;
        padding-top: 6px;
    }
    .receipt .totals td:last-child { text-align: right; }

    .receipt .barcode {
        text-align: center;
        font-family: monospace;
        letter-spacing: 2px;
        margin: 12px 0 4px;
        font-size: 11px;
    }

    .receipt .thankyou {
        text-align: center;
        margin-top: 12px;
        font-size: 11px;
        font-style: italic;
    }

    .receipt .actions {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
        margin: 16px 0 4px;
    }
    .receipt .actions a,
    .receipt .actions button {
        font-size: 12px;
        padding: 4px 10px;
    }

    .receipt .alert {
        margin: 0 0 10px;
        padding: 6px 8px;
        font-size: 11px;
        border-radius: 3px;
    }
    .receipt .alert-success { background: #d1e7dd; color: #0a3622; }
    .receipt .alert-info    { background: #cff4fc; color: #055160; }

    @media print {
        @page { size: 80mm auto; margin: 4mm; }
        body {
            background: #fff !important;
            margin: 0;
            padding: 0;
        }
        .receipt {
            width: 100%;
            font-size: 11px;
        }
        /* Hide all interactive chrome from the printout. */
        .receipt .actions,
        .receipt .no-print {
            display: none !important;
        }
        /* Force any inherited image / gradient off in print. */
        * {
            background-image: none !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@section('content')
@php
    // Display the most relevant timestamp — payment_date if the
    // payment is completed, otherwise the row's created_at so a
    // pending payment still shows when it was generated.
    $receiptDate = $payment->payment_date
        ?? $payment->created_at;
@endphp

<div class="receipt">

    {{-- Flash messages — visible on screen, hidden on printout. --}}
    @if(session('success'))
        <div class="alert alert-success no-print">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info no-print">{{ session('info') }}</div>
    @endif

    {{-- Hospital branding — logo + name + tagline + address --}}
    <header>
        @if(!empty($logo))
            <img src="{{ $logo }}" alt="Hospital logo">
        @endif
        <h1>{{ $institutionName }}</h1>
        @if(!empty($tagline))
            <p><em>{{ $tagline }}</em></p>
        @endif
        @if(!empty($address))
            <p>{{ $address }}</p>
        @endif
        @if(!empty($phone))
            <p>Tel: {{ $phone }}</p>
        @endif
        @if(!empty($email))
            <p>{{ $email }}</p>
        @endif
    </header>

    {{-- Heading — green when completed, amber when still pending --}}
    <h3 class="{{ $payment->status === 'completed' ? 'completed' : 'pending' }}">
        @if($payment->status === 'completed')
            PAYMENT RECEIPT
        @else
            PENDING PAYMENT
        @endif
    </h3>

    <table>
        <tr>
            <th>Receipt No</th>
            <td>{{ $payment->payment_ref }}</td>
        </tr>
        <tr>
            <th>Date</th>
            <td>{{ $receiptDate ? $receiptDate->format('d M Y, h:i A') : 'N/A' }}</td>
        </tr>
        <tr>
            <th>Patient</th>
            <td>{{ $payment->patient_name }}</td>
        </tr>
        @if(!empty($payment->patient_phone))
            <tr>
                <th>Phone</th>
                <td>{{ $payment->patient_phone }}</td>
            </tr>
        @endif
        <tr>
            <th>Service</th>
            <td>{{ $payment->service_name }}</td>
        </tr>
        <tr>
            <th>Method</th>
            <td>{{ ucfirst($payment->payment_method ?? 'pending') }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{{ strtoupper($payment->status) }}</td>
        </tr>
    </table>

    <table class="totals">
        <tr>
            <td>Service Amount</td>
            <td>₦{{ number_format((float) $payment->amount, 2) }}</td>
        </tr>
        <tr>
            <td>Portal Charge</td>
            <td>₦{{ number_format((float) $payment->portal_charge, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>TOTAL</td>
            <td>₦{{ number_format((float) $payment->total_amount, 2) }}</td>
        </tr>
    </table>

    <p class="thankyou">Thank you for choosing {{ $institutionName }}.</p>
    <p class="barcode">*{{ $payment->payment_ref }}*</p>

    {{-- Pay Now (test) — only when the payment is still pending. --}}
    @if($payment->status === 'pending')
        <div class="actions no-print">
            <form method="POST"
                  action="{{ route('patient-portal.payment.pay-test', $payment->id) }}"
                  onsubmit="return confirm('Complete this test payment now?');">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fas fa-credit-card"></i> Pay Now (Test)
                </button>
            </form>
        </div>
    @endif

    {{-- Print + Back buttons — hidden on the printed page. --}}
    <div class="actions no-print">
        <button onclick="window.print()" type="button" class="btn btn-primary btn-sm">
            <i class="fas fa-print"></i> Print
        </button>
        <a href="{{ route('patient-portal.dashboard') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>
@endsection