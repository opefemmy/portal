@php
    use App\Models\SystemSetting;
    $institutionName = SystemSetting::get('institution_name', 'Ekiti State College of Technology');
    $institutionAddress = SystemSetting::get('institution_address', '');
    $institutionPhone = SystemSetting::get('institution_phone', '');
    $institutionEmail = SystemSetting::get('institution_email', '');
    $institutionWebsite = SystemSetting::get('institution_website', '');
    $registrarSignature = SystemSetting::get('registrar_signature_path', null);
    $signatureUrl = $registrarSignature && file_exists(public_path('storage/' . $registrarSignature))
        ? asset('storage/' . $registrarSignature)
        : null;
    $registrarName = SystemSetting::get('registrar_name');
    if (! $registrarName) {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users') && \Illuminate\Support\Facades\Schema::hasTable('roles')) {
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
    $letterBody = SystemSetting::get('admission_letter_body', "We are pleased to inform you that you have been offered provisional admission into the {programme} programme of the {department}, {school}, for the {session} academic session.\n\nPlease complete the acceptance process by paying the required fees before the deadline.");
    $letterFeesRaw = SystemSetting::get('admission_letter_fees', '[]');
    $letterFees = json_decode($letterFeesRaw, true);
    if (!is_array($letterFees)) { $letterFees = []; }
    $letterFees = array_filter($letterFees, function($f) { return !empty($f['name']) && !empty($f['amount']); });

    $fullName = trim(($applicant->surname ?? '') . ' ' . ($applicant->first_name ?? '') . ' ' . ($applicant->middle_name ?? ''));
    $replacements = [
        '{name}' => $fullName,
        '{programme}' => $applicant->programme->name ?? 'N/A',
        '{department}' => $applicant->department->name ?? 'N/A',
        '{school}' => $applicant->school->name ?? 'N/A',
        '{session}' => optional($applicant->session)->name ?? 'N/A',
        '{matric_number}' => $applicant->matric_number ?? 'N/A',
        '{admission_date}' => optional($applicant->admission_date)->format('d F Y') ?? 'N/A',
    ];
    $renderedBody = strtr($letterBody, $replacements);
    $totalFee = array_sum(array_column($letterFees, 'amount'));
    $logoUrl = file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : null;
    $refNumber = 'ADM/' . date('Y') . '/' . str_pad($applicant->id, 5, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admission Letter - {{ $fullName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; background: #f3f4f6; margin: 0; padding: 20px; color: #111; }
        .letter-page {
            position: relative;
            background: white;
            max-width: 800px;
            margin: 0 auto;
            padding: 60px 70px 60px 70px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 1100px;
            overflow: hidden;
        }
        .letter-page::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            height: 500px;
            background: url('{{ $logoUrl }}') no-repeat center;
            background-size: contain;
            opacity: 0.06;
            z-index: 0;
            pointer-events: none;
        }
        .letter-content { position: relative; z-index: 1; }
        .letterhead { text-align: center; border-bottom: 3px double #247D57; padding-bottom: 18px; margin-bottom: 24px; }
        .letterhead .logo { max-height: 80px; margin-bottom: 8px; }
        .letterhead h1 { font-size: 22pt; margin: 0; color: #247D57; letter-spacing: 1px; font-weight: bold; }
        .letterhead p { margin: 2px 0; font-size: 10pt; color: #333; }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 24px; font-size: 10.5pt; }
        .recipient { margin-bottom: 22px; font-size: 11pt; }
        .recipient strong { text-decoration: underline; }
        .subject { text-align: center; font-weight: bold; text-transform: uppercase; margin: 22px 0 18px; font-size: 12pt; text-decoration: underline; }
        .body { line-height: 1.8; font-size: 11.5pt; text-align: justify; margin-bottom: 22px; }
        .body p { margin-bottom: 14px; }
        .fees-section { margin: 24px 0; padding: 14px 18px; border: 1px solid #ccc; border-radius: 6px; background: #fafafa; }
        .fees-section h3 { font-size: 11pt; margin: 0 0 10px; text-transform: uppercase; color: #247D57; }
        .fees-table { width: 100%; border-collapse: collapse; font-size: 10.5pt; }
        .fees-table th, .fees-table td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; }
        .fees-table th { background: #f0f3f0; }
        .fees-table .amount { text-align: right; }
        .footer-block { margin-top: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
        .sign-block { text-align: center; min-width: 220px; }
        .signature-img { max-height: 70px; margin-bottom: 4px; }
        .sign-line { border-top: 1px solid #333; padding-top: 4px; font-size: 10pt; }
        .footer-note { margin-top: 32px; font-size: 9pt; text-align: center; color: #555; border-top: 1px solid #ddd; padding-top: 10px; }
        .no-print { max-width: 800px; margin: 0 auto 16px; text-align: right; }
        @media print {
            body { background: white; padding: 0; }
            .letter-page { box-shadow: none; padding: 30px 40px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print me-2"></i>Print / Save as PDF
        </button>
        <a href="{{ route('registrar.admission.byDepartment') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>

    <div class="letter-page">
        <div class="letter-content">
            {{-- Letterhead --}}
            <div class="letterhead">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo" class="logo">
                @endif
                <h1>{{ strtoupper($institutionName) }}</h1>
                @if($institutionAddress)<p>{{ $institutionAddress }}</p>@endif
                <p>
                    @if($institutionPhone)Tel: {{ $institutionPhone }}@endif
                    @if($institutionEmail) | Email: {{ $institutionEmail }}@endif
                    @if($institutionWebsite) | {{ $institutionWebsite }}@endif
                </p>
            </div>

            <div class="meta-row">
                <div><strong>Ref:</strong> {{ $refNumber }}</div>
                <div><strong>Date:</strong> {{ now()->format('d F, Y') }}</div>
            </div>

            <div class="recipient">
                <strong>{{ $fullName }}</strong><br>
                Application No: {{ $applicant->application_number }}<br>
                @if($applicant->email){{ $applicant->email }}<br>@endif
                @if($applicant->phone){{ $applicant->phone }}@endif
            </div>

            <div class="subject">Letter of Provisional Admission</div>

            <div class="body">
                @foreach(explode("\n\n", $renderedBody) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>

            @if(count($letterFees) > 0)
            <div class="fees-section">
                <h3><i class="fas fa-receipt me-2"></i>Fees Payable on Acceptance</h3>
                <table class="fees-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">S/N</th>
                            <th>Item</th>
                            <th class="amount" style="width: 140px;">Amount (₦)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($letterFees as $i => $fee)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $fee['name'] }}</td>
                                <td class="amount">{{ number_format((float)$fee['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2" class="text-end"><strong>Total</strong></td>
                            <td class="amount"><strong>₦{{ number_format($totalFee, 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
                <p class="small mt-2 mb-0 text-muted">
                    Please bring a printed copy of this letter when reporting for registration.
                </p>
            </div>
            @endif

            <div class="footer-block">
                <div>
                    <strong>Acceptance Instructions:</strong>
                    <ul class="small mb-0 mt-2">
                        <li>Pay all fees listed above at the bursary or via the online portal.</li>
                        <li>Report on the date communicated by your department.</li>
                        <li>Bring originals and copies of all credentials.</li>
                    </ul>
                </div>
                <div class="sign-block">
                    @if($signatureUrl)
                        <img src="{{ $signatureUrl }}" alt="Signature" class="signature-img">
                    @else
                        <div style="height: 70px;"></div>
                    @endif
                    <div class="sign-line">
                        <strong>{{ $registrarName }}</strong><br>
                        <span class="small">Registrar, {{ $institutionName }}</span>
                    </div>
                </div>
            </div>

            <div class="footer-note">
                This is a computer-generated document. Generated on {{ now()->format('d F Y, h:i A') }}.
            </div>
        </div>
    </div>
</body>
</html>
