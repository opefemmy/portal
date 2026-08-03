@php
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

// Resolve institution info (used for letterhead + registrar signature block)
$institutionName    = SystemSetting::get('institution_name', 'Ekiti State College of Technology');
$institutionShort   = SystemSetting::get('institution_short_name', 'EKSCOTECH');
$institutionAddress = SystemSetting::get('institution_address', '');
$institutionPhone   = SystemSetting::get('institution_phone', '');
$institutionEmail   = SystemSetting::get('institution_email', '');

// Resolve registrar's display name. Settings first (most explicit), then the
// admin user assigned the registrar role, then a generic fallback.
$registrarName = SystemSetting::get('registrar_name');
if (! $registrarName) {
    try {
        if (Schema::hasTable('users') && Schema::hasTable('roles')) {
            $registrar = \App\Models\User::whereHas('role', function ($q) {
                $q->where('slug', 'registrar');
            })->first();
            $registrarName = $registrar?->name ?: 'Registrar';
        } else {
            $registrarName = 'Registrar';
        }
    } catch (\Throwable $e) {
        $registrarName = 'Registrar';
    }
}

// Resolve registrar signature image (uploaded via registrar letter settings).
$registrarSignaturePath = SystemSetting::get('registrar_signature_path');
$signatureUrl = null;
if ($registrarSignaturePath) {
    $fullPath = public_path('storage/' . $registrarSignaturePath);
    if (file_exists($fullPath)) {
        $signatureUrl = asset('storage/' . $registrarSignaturePath);
    }
}

// Resolve logo URL (prefer public/images/logo.png, fall back to storage)
$logoUrl = null;
$publicLogo = public_path('images/logo.png');
if (file_exists($publicLogo)) {
    $logoUrl = asset('images/logo.png') . '?v=' . time();
} else {
    $storedLogo = SystemSetting::get('institution_logo');
    if ($storedLogo && file_exists(storage_path('app/public/' . $storedLogo))) {
        $logoUrl = asset('storage/' . $storedLogo);
    }
}

$letterDate    = now()->format('l, d F Y');
$matricNumber  = $student?->matric_number ?: ($applicant->matric_number ?: null);
$fullName      = $applicant->full_name ?: trim(($applicant->surname ?? '') . ' ' . ($applicant->first_name ?? ''));
$programmeName = $applicant->programme?->name;
$departmentName= $applicant->department?->name;
$schoolName    = $applicant->school?->name;
$sessionName   = $applicant->session?->name;

// Level of entry is always "100 Level" per admissions policy. The raw
// applicants.entry_level field is "UTME" or "DE" and was previously
// concatenated with "00 Level" producing "UTME00 Level" — keep this
// hard-coded so future changes to entry_level don't break the letter.
$levelOfEntry = '100 Level';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admission Letter - {{ $fullName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        html, body {
            background: #e9ecef;
            margin: 0;
            padding: 0;
            color: #111;
            font-family: 'Georgia', 'Times New Roman', serif;
        }
        .admission-letter-wrap {
            background: #e9ecef;
            padding: 30px 0;
            min-height: 100vh;
        }
        .admission-letter {
            position: relative;
            background: #fff;
            max-width: 820px;
            margin: 0 auto;
            padding: 60px 70px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            color: #222;
            overflow: hidden;
            min-height: 1056px; /* ~A4 height at 96dpi */
        }

        /* Watermark logo — repeats behind the text */
        .admission-letter .watermark {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 0;
        }
        .admission-letter .watermark img {
            width: 60%;
            max-width: 480px;
            opacity: 0.07;
            filter: grayscale(100%);
        }
        .admission-letter > * { position: relative; z-index: 1; }

        .letter-header {
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 3px double #198754;
            padding-bottom: 18px;
            margin-bottom: 28px;
        }
        .letter-header .logo {
            width: 90px;
            height: 90px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .letter-header .institution {
            flex: 1;
            text-align: center;
        }
        .letter-header .institution h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1a1a1a;
        }
        .letter-header .institution .address {
            font-size: 12px;
            color: #555;
            margin-top: 4px;
            line-height: 1.4;
        }
        .letter-header .institution .contact {
            font-size: 11px;
            color: #777;
            margin-top: 2px;
        }

        .letter-title {
            text-align: center;
            margin: 20px 0 28px;
        }
        .letter-title h2 {
            font-size: 28px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 2px;
            color: #198754;
        }
        .letter-title .subtitle {
            font-size: 13px;
            color: #555;
            margin-top: 4px;
            font-style: italic;
        }

        .letter-meta {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 22px;
        }
        .letter-meta strong { color: #198754; }

        .letter-body p {
            font-size: 14px;
            line-height: 1.75;
            margin-bottom: 14px;
            text-align: justify;
        }
        .letter-body table.admission-details {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0 22px;
            font-size: 13px;
        }
        .letter-body table.admission-details th,
        .letter-body table.admission-details td {
            border: 1px solid #c8d3df;
            padding: 9px 12px;
            text-align: left;
            vertical-align: top;
        }
        .letter-body table.admission-details th {
            background: #f1f6f3;
            color: #1a1a1a;
            font-weight: 600;
            width: 35%;
        }

        /* Registrar signature block — sits alone (no applicant signature) */
        .letter-footer {
            margin-top: 60px;
            display: flex;
            justify-content: flex-end;
        }
        .signature-block {
            text-align: center;
            min-width: 280px;
        }
        .signature-block .signature-image {
            max-height: 80px;
            margin: 0 auto 4px;
            display: block;
        }
        .signature-block .signature-line {
            border-top: 1px solid #333;
            margin-top: 8px;
            padding-top: 6px;
            font-weight: 700;
            font-size: 14px;
        }
        .signature-block .signature-name {
            font-size: 13px;
            color: #1a1a1a;
            margin-top: 2px;
            font-weight: 600;
        }
        .signature-block .signature-title {
            font-size: 11px;
            color: #777;
        }
        .signature-block .signature-date {
            font-size: 11px;
            color: #777;
            margin-top: 4px;
        }
        .signature-block .stamp-area {
            margin-top: 14px;
            font-size: 10px;
            color: #999;
            font-style: italic;
        }

        /* Action bar (hidden in print) */
        .letter-actions {
            max-width: 820px;
            margin: 20px auto 0;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Print rules — must strip EVERYTHING outside the letter so the
           browser's "Save as PDF" / "Print" only ever captures the letter. */
        @media print {
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .admission-letter-wrap {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                min-height: auto !important;
            }
            .letter-actions { display: none !important; }
            .admission-letter {
                box-shadow: none !important;
                margin: 0 auto !important;
                padding: 18mm 16mm !important;
                width: 210mm !important;
                max-width: 210mm !important;
                min-height: 297mm !important;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            .admission-letter .watermark img {
                opacity: 0.08 !important;
            }
            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admission-letter-wrap">
        <div class="admission-letter">
            {{-- Watermark --}}
            <div class="watermark" aria-hidden="true">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="">
                @endif
            </div>

            {{-- Header / Letterhead --}}
            <div class="letter-header">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Institution Logo" class="logo">
                @else
                    <div style="width:90px;height:90px;background:#e9ecef;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#999;">
                        <i class="fas fa-university fa-3x"></i>
                    </div>
                @endif
                <div class="institution">
                    <h1>{{ $institutionName }}</h1>
                    @if($institutionAddress)
                        <div class="address">{{ $institutionAddress }}</div>
                    @endif
                    @if($institutionPhone || $institutionEmail)
                        <div class="contact">
                            @if($institutionPhone) Tel: {{ $institutionPhone }} @endif
                            @if($institutionPhone && $institutionEmail) &middot; @endif
                            @if($institutionEmail) {{ $institutionEmail }} @endif
                        </div>
                    @endif
                </div>
                @if($logoUrl)
                    {{-- Visual filler to keep the institution name centered --}}
                    <img src="{{ $logoUrl }}" alt="" class="logo" aria-hidden="true" style="visibility:hidden;">
                @endif
            </div>

            {{-- Title --}}
            <div class="letter-title">
                <h2>Admission Letter</h2>
                <div class="subtitle">Office of the Registrar</div>
            </div>

            {{-- Meta --}}
            <div class="letter-meta">
                <div><strong>Date:</strong> {{ $letterDate }}</div>
                <div><strong>Ref:</strong> {{ $applicant->application_number }}</div>
            </div>

            {{-- Body --}}
            <div class="letter-body">
                <p><strong>{{ $fullName }}</strong><br>
                Application Number: <code>{{ $applicant->application_number }}</code><br>
                @if($applicant->email)Email: {{ $applicant->email }}@endif
                @if($applicant->phone) &middot; Phone: {{ $applicant->phone }}@endif
                </p>

                <p><strong>Dear {{ $applicant->first_name ?: $fullName }},</strong></p>

                <p>
                    On behalf of the <strong>{{ $institutionName }}</strong>, I am pleased to inform
                    you that you have been offered <strong>provisional admission</strong> into the
                    following programme for the <strong>{{ $sessionName ?? 'current' }} academic session</strong>.
                </p>

                <table class="admission-details">
                    <tr>
                        <th>School / Faculty</th>
                        <td>{{ $schoolName ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Department</th>
                        <td>{{ $departmentName ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Programme</th>
                        <td>{{ $programmeName ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Level of Entry</th>
                        <td>{{ $levelOfEntry }}</td>
                    </tr>
                    <tr>
                        <th>Mode of Study</th>
                        <td>{{ ucfirst($applicant->mode_of_study ?? 'Full-time') }}</td>
                    </tr>
                    @if($matricNumber)
                    <tr>
                        <th>Matriculation Number</th>
                        <td><code>{{ $matricNumber }}</code></td>
                    </tr>
                    @endif
                    <tr>
                        <th>Session</th>
                        <td>{{ $sessionName ?? 'N/A' }}</td>
                    </tr>
                </table>

                <p>
                    Your acceptance fee payment has been verified and confirmed. You are required to
                    present this letter together with your original credentials (O'Level certificate,
                    birth certificate, and any other relevant documents) to the Admissions Office on
                    or before the resumption date to complete your registration formalities.
                </p>

                <p>
                    Please note that this admission is subject to the verification of all credentials
                    submitted during your application. Any discrepancy found may lead to the withdrawal
                    of this offer.
                </p>

                <p>
                    We congratulate you on this achievement and wish you a successful academic career
                    at {{ $institutionShort }}.
                </p>
            </div>

            {{-- Registrar Signature Block (only block — applicant signature removed) --}}
            <div class="letter-footer">
                <div class="signature-block">
                    @if($signatureUrl)
                        <img src="{{ $signatureUrl }}" alt="Registrar signature" class="signature-image">
                    @else
                        <div style="height: 80px;"></div>
                    @endif
                    <div class="signature-line">{{ $registrarName }}</div>
                    <div class="signature-name">Registrar</div>
                    <div class="signature-title">{{ $institutionName }}</div>
                    <div class="signature-date">Date of Issue: {{ $letterDate }}</div>
                    <div class="stamp-area">[Official Stamp]</div>
                </div>
            </div>
        </div>

        {{-- Action bar (hidden in print) — single Print button covers "Save as PDF" too --}}
        <div class="letter-actions">
            <a href="{{ route('applicant.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print / Save as PDF
            </button>
        </div>
    </div>
</body>
</html>
