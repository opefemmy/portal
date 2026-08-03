<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Clearance — {{ $student->user->name ?? 'Student' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            background: #f4f6fa;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .letter-wrap {
            display: flex;
            justify-content: center;
            padding: 30px 15px;
        }

        .letter {
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            padding: 22mm 18mm;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            color: #222;
            line-height: 1.55;
        }

        .letter-header {
            text-align: center;
            border-bottom: 3px double #1a237e;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }

        .letter-header .institution {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #1a237e;
            margin-bottom: 4px;
        }

        .letter-header .address {
            font-size: 12px;
            color: #555;
        }

        .letter h1 {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 18px;
            margin: 18px 0;
        }

        .letter .meta {
            margin: 16px 0;
            font-size: 14px;
        }

        .letter .meta td {
            padding: 4px 10px 4px 0;
            vertical-align: top;
        }

        .letter table.fees {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            font-size: 13px;
        }

        .letter table.fees th,
        .letter table.fees td {
            border: 1px solid #c0c4d0;
            padding: 8px;
            text-align: left;
        }

        .letter table.fees th {
            background: #f0f2fa;
        }

        .letter .signatures {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }

        .letter .signature-block {
            flex: 1;
            text-align: center;
            font-size: 13px;
        }

        .letter .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 6px;
        }

        .letter .stamp {
            margin-top: 30px;
            text-align: right;
            font-size: 12px;
            color: #777;
        }

        .letter-actions {
            max-width: 210mm;
            margin: 16px auto;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        @media print {
            body, html {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .letter-actions, .main-header, .main-sidebar, .main-footer,
            .navbar, .sidebar { display: none !important; }
            .letter-wrap { padding: 0 !important; }
            .letter {
                box-shadow: none !important;
                margin: 0 auto !important;
                padding: 18mm 16mm !important;
                width: 210mm !important;
                max-width: 210mm !important;
            }
            @page { size: A4 portrait; margin: 0; }
        }
    </style>
</head>
<body>

<div class="letter-actions">
    <button type="button" class="btn btn-secondary" onclick="window.close()">Close</button>
    <button type="button" class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print me-1"></i>Print
    </button>
</div>

<div class="letter-wrap">
    <div class="letter">
        <div class="letter-header">
            <div class="institution">{{ \App\Models\SystemSetting::get('institution_name', config('app.name', 'Institution Portal')) }}</div>
            <div class="address">{{ \App\Models\SystemSetting::get('institution_address', 'Official Address on File') }}</div>
        </div>

        <h1>Examination Clearance Certificate</h1>

        <div class="meta">
            <table>
                <tr>
                    <td><strong>Student Name:</strong></td>
                    <td>{{ $student->user->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Matric Number:</strong></td>
                    <td>{{ $student->matric_number ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Department:</strong></td>
                    <td>{{ $student->department->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Programme:</strong></td>
                    <td>{{ $student->programme->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Level:</strong></td>
                    <td>{{ $student->level ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Session:</strong></td>
                    <td>{{ $session->name ?? '—' }} {{ $session->semester ?? '' }}</td>
                </tr>
                <tr>
                    <td><strong>Date Issued:</strong></td>
                    <td>{{ now()->format('d M Y') }}</td>
                </tr>
            </table>
        </div>

        <p>
            This is to certify that the above-named student has paid all required
            school fees for the {{ $session->name ?? 'current' }} session and is
            <strong>cleared to sit for the examinations</strong>.
        </p>

        <h3 style="margin-top:30px; font-size:15px;">Fees Paid in Full</h3>
        <table class="fees">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fee</th>
                    <th>Amount (₦)</th>
                    <th>Reference</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($perFeeStatus as $i => $row)
                    @php $first = $row['payments']->first(); @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            {{ $row['fee']->name }}
                            <br><small class="text-muted">{{ ucfirst(str_replace('_', '-', $row['category'])) }} · 100%</small>
                        </td>
                        <td>{{ number_format($row['price'] + $row['portal'], 2) }}</td>
                        <td><code>{{ $first->reference ?? '—' }}</code></td>
                        <td>{{ optional($first?->created_at)->format('d M Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line">Bursar</div>
            </div>
            <div class="signature-block">
                <div class="signature-line">Registrar</div>
            </div>
        </div>

        <div class="stamp">
            Generated by {{ config('app.name', 'Institution Portal') }} on {{ now()->format('d M Y, H:i') }}
        </div>
    </div>
</div>

</body>
</html>