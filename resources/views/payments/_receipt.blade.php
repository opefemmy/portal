{{--
    Shared payment-receipt body. Used by the student, bursar, and
    applicant receipt views so the brand header, watermarks, and
    payment-detail table stay in sync.

    Required view variables:
      $payment         App\Models\Payment — the row being receipted.
      $logoUrl         ?string  — institution logo URL, already resolved
                                  by the controller (or null when the
                                  institution has not uploaded one).
      $feeTypeLabel    string   — the polymorphic "Fee Type" string the
                                  controller computed. We don't recompute
                                  it here so the receipt view never lies
                                  about what was paid.
      $payerMatric     ?string  — matric number when $payment->student is
                                  set, otherwise null.

    The partial draws:
      1. Watermark layer (logo + payer name + matric + purpose) —
         fixed-positioned behind the content with low opacity so it
         survives both screen and print.
      2. Brand header — logo on the left, institution name + address
         on the right.
      3. Title block — "OFFICIAL PAYMENT RECEIPT" + reference + date
         + status badge.
      4. Payer / Payment detail tables.
      5. Footer with verification note.
--}}

@php
    // Pull institution info once. The values are cached at the layout
    // level; calling get() here is cheap and keeps the partial
    // self-contained (the partial can be rendered outside the
    // application's main layout when wrapped by payments/_receipt_pdf).
    $institutionName    = \App\Models\SystemSetting::getInstitutionName();
    $institutionAddress = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_ADDRESS);
    $institutionPhone   = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_PHONE);
    $institutionEmail   = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_EMAIL);

    // Receipt status badge.
    $statusLabel = $payment->status
        ? ucfirst((string) $payment->status)
        : 'Unknown';
    $statusClass = match (true) {
        in_array($payment->status, ['completed', 'verified'], true) => 'success',
        $payment->status === 'pending'                              => 'warning',
        default                                                     => 'danger',
    };

    // Until the gateway confirms a payment the user shouldn't be
    // reading an "OFFICIAL RECEIPT" — they're looking at the
    // invoice they're about to pay. Flip the title and the "Total
    // Paid" line so the same partial renders both surfaces honestly.
    $isPending = $payment->status === 'pending';
    $titleText = $isPending ? 'PAYMENT INVOICE' : 'OFFICIAL PAYMENT RECEIPT';

    // Watermark text. The user explicitly asked for the payer name,
    // matric number, and payment purpose to flood the page as a
    // tamper-evident stamp — keep all three here. Fallbacks preserve
    // a useful watermark even when the row is incomplete (e.g.
    // applicant-side pre-migration payments have no matric).
    $watermarkName    = $payment->payer_name
        ?? $payment->student?->user?->name
        ?? $payment->applicant?->full_name
        ?? 'Unknown payer';
    $watermarkMatric  = $payerMatric ?? '—';
    $watermarkPurpose = $feeTypeLabel ?: ($payment->payment_purpose ?: 'Payment');

    // Receipt No / Date / Transaction fallback chain — the existing
    // student/bursar views already do this; we centralise so all three
    // receipt surfaces render identically.
    $receiptRef = $payment->reference
        ?? $payment->payment_ref
        ?? $payment->transaction_id
        ?? 'N/A';
    $receiptDate = $payment->created_at
        ?? $payment->payment_date
        ?? null;
    $transactionId = $payment->transaction_id
        ?? $payment->payment_ref
        ?? $payment->reference
        ?? 'N/A';
@endphp

{{-- Inline CSS — same approach as applicant/payments/receipt.blade.php
     and the existing student/bursar receipt views. Keeping the styles
     inline means the partial works in both the full app layout (with
     Bootstrap) and the minimal PDF layout (without). --}}
<style>
    .receipt-shell {
        position: relative;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        padding: 32px 36px;
        margin: 0 auto;
        max-width: 820px;
        overflow: hidden;
    }
    .receipt-body { position: relative; z-index: 1; }

    /* Watermark layer — sits behind the receipt body. */
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

    /* Brand header */
    .receipt-brand {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 16px;
    }
    .receipt-brand-logo img {
        max-height: 90px;
        max-width: 110px;
        object-fit: contain;
    }
    .receipt-brand-logo {
        flex: 0 0 110px;
        text-align: center;
    }
    .receipt-brand-info { flex: 1; }
    .receipt-brand-name {
        margin: 0;
        font-size: 1.55rem;
        font-weight: 700;
        color: #1f4d2b;
    }
    .receipt-brand-line {
        margin: 2px 0;
        color: #444;
        font-size: 0.92rem;
    }

    .receipt-divider {
        height: 3px;
        background: linear-gradient(90deg, #1f4d2b 0%, #247D57 50%, #1f4d2b 100%);
        border-radius: 2px;
        margin: 12px 0 22px;
    }

    /* Title block */
    .receipt-title { text-align: center; margin-bottom: 28px; }
    .receipt-title h3 {
        margin: 0 0 12px;
        letter-spacing: 2px;
        font-size: 1.25rem;
        color: #1f4d2b;
        font-weight: 700;
    }
    .receipt-title-meta {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 18px;
        font-size: 0.95rem;
    }
    .receipt-meta-label {
        font-weight: 600;
        color: #555;
        margin-right: 4px;
    }
    .receipt-status {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 14px;
        font-weight: 600;
        color: #fff;
        font-size: 0.85rem;
    }
    .receipt-status-success { background: #28a745; }
    .receipt-status-warning { background: #ffc107; color: #333; }
    .receipt-status-danger  { background: #dc3545; }

    /* Section headings + tables */
    .receipt-section { margin-bottom: 22px; }
    .receipt-section-heading {
        font-size: 1rem;
        font-weight: 700;
        color: #1f4d2b;
        border-bottom: 1px solid #d6e4dc;
        padding-bottom: 6px;
        margin: 0 0 10px;
    }
    .receipt-table {
        width: 100%;
        border-collapse: collapse;
    }
    .receipt-table th, .receipt-table td {
        padding: 8px 12px;
        text-align: left;
        vertical-align: top;
    }
    .receipt-table th {
        width: 35%;
        font-weight: 600;
        color: #555;
        background: #f8faf9;
    }
    .receipt-table td { color: #111; }
    .receipt-amount {
        font-weight: 700;
        font-size: 1.05rem;
        color: #1f4d2b;
    }

    /* Footer */
    .receipt-footer {
        margin-top: 28px;
        padding-top: 14px;
        border-top: 1px dashed #c8d6cc;
        font-size: 0.85rem;
        color: #555;
    }
    .receipt-footer-muted {
        color: #888;
        font-size: 0.8rem;
        margin-top: 4px;
    }

    /* Print — bump watermark opacity so the printed copy is clearly
       marked. Keep all the screen-only chrome off the page (handled
       globally by the layout's @media print block; this is the
       receipt-specific print polish). */
    @media print {
        .receipt-shell {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .receipt-watermark-logo { opacity: 0.10; }
        .receipt-watermark-text { opacity: 0.12; }
        .receipt-divider { background: #1f4d2b; }
    }
</style>

<div class="receipt-shell">
    {{-- Watermark layer (sits behind everything; uses position:absolute
         so it covers the full receipt shell on screen and on print). --}}
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

    <div class="receipt-body">
        {{-- Brand header: logo + institution name + address. --}}
        <header class="receipt-brand">
            <div class="receipt-brand-logo">
                @if(!empty($logoUrl))
                    <img src="{{ $logoUrl }}" alt="{{ $institutionName }} logo">
                @endif
            </div>
            <div class="receipt-brand-info">
                <h2 class="receipt-brand-name">{{ $institutionName }}</h2>
                @if(!empty($institutionAddress))
                    <p class="receipt-brand-line">{{ $institutionAddress }}</p>
                @endif
                @if(!empty($institutionPhone))
                    <p class="receipt-brand-line"><i class="fas fa-phone me-1"></i>{{ $institutionPhone }}</p>
                @endif
                @if(!empty($institutionEmail))
                    <p class="receipt-brand-line"><i class="fas fa-envelope me-1"></i>{{ $institutionEmail }}</p>
                @endif
            </div>
        </header>

        <div class="receipt-divider"></div>

        {{-- Title block --}}
        <section class="receipt-title">
            <h3>{{ $titleText }}</h3>
            <div class="receipt-title-meta">
                <div><span class="receipt-meta-label">Receipt No:</span> <code>{{ $receiptRef }}</code></div>
                <div><span class="receipt-meta-label">Date:</span> {{ $receiptDate ? \Illuminate\Support\Carbon::parse($receiptDate)->format('d M, Y h:i A') : 'N/A' }}</div>
                <div>
                    <span class="receipt-meta-label">Status:</span>
                    <span class="receipt-status receipt-status-{{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
            </div>
        </section>

        {{-- Payer Details --}}
        <section class="receipt-section">
            <h5 class="receipt-section-heading">Payer Details</h5>
            <table class="receipt-table">
                <tbody>
                    @if($payment->student)
                        <tr>
                            <th>Matric Number</th>
                            <td><code>{{ $payerMatric ?: ($payment->student->matric_number ?? 'N/A') }}</code></td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>{{ $payment->student->user->name ?? $payment->payer_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $payment->student->user->email ?? $payment->payer_email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Department</th>
                            <td>{{ $payment->student->department->name ?? 'N/A' }}</td>
                        </tr>
                    @elseif($payment->applicant)
                        <tr>
                            <th>Application Number</th>
                            <td><code>{{ $payment->applicant->application_number ?? 'N/A' }}</code></td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>{{ $payment->applicant->full_name ?? $payment->payer_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $payment->applicant->email ?? $payment->payer_email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td>{{ $payment->applicant->phone ?? $payment->payer_phone ?? 'N/A' }}</td>
                        </tr>
                    @else
                        <tr>
                            <th>Payer Name</th>
                            <td>{{ $payment->payer_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $payment->payer_email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td>{{ $payment->payer_phone ?? 'N/A' }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </section>

        {{-- Payment Details --}}
        <section class="receipt-section">
            <h5 class="receipt-section-heading">Payment Details</h5>
            <table class="receipt-table">
                <tbody>
                    <tr>
                        <th>Fee Type</th>
                        <td>{{ $feeTypeLabel }}</td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td class="receipt-amount">₦{{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Gateway</th>
                        <td>{{ ucfirst((string) ($payment->gateway ?? $payment->payment_method ?? 'N/A')) }}</td>
                    </tr>
                    @if(!empty($payment->portal_charge) && (float) $payment->portal_charge > 0)
                        <tr>
                            <th>Portal Charge</th>
                            <td>₦{{ number_format((float) $payment->portal_charge, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th>Total Paid</th>
                        <td class="receipt-amount">
                            @if($isPending)
                                <span class="receipt-status receipt-status-warning">Pending — not yet paid</span>
                            @else
                                ₦{{ number_format((float) ($payment->total_amount ?? ($payment->amount + ($payment->portal_charge ?? 0))), 2) }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Transaction ID</th>
                        <td><code>{{ $transactionId }}</code></td>
                    </tr>
                </tbody>
            </table>
        </section>

        {{-- Footer --}}
        <footer class="receipt-footer">
            <p>
                This is a computer-generated receipt issued by
                <strong>{{ $institutionName }}</strong> on
                {{ \Illuminate\Support\Carbon::now()->format('d M, Y \a\t h:i A') }}.
            </p>
            <p class="receipt-footer-muted">
                Verify this receipt at the institution portal using the Receipt No above.
                For enquiries, contact the bursary with the Transaction ID.
            </p>
        </footer>
    </div>
</div>
