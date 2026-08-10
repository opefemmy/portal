<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Registration Form - {{ auth()->user()->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #247D57;
            --primary-dark: #1E6A4A;
        }
        html, body {
            background: #e9ecef;
            margin: 0;
            padding: 0;
            color: #111;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
        }

        .invoice-wrap {
            background: #e9ecef;
            padding: 30px 0;
            min-height: 100vh;
        }

        .invoice {
            position: relative;
            background: #fff;
            max-width: 820px;
            margin: 0 auto;
            padding: 50px 60px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            color: #222;
            overflow: hidden;
            min-height: 1056px; /* ~A4 height at 96dpi */
        }

        /* Watermark logo — sits behind all content */
        .invoice .watermark {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 0;
        }
        .invoice .watermark img {
            width: 60%;
            max-width: 480px;
            opacity: 0.07;
            filter: grayscale(100%);
        }
        .invoice > * { position: relative; z-index: 1; }

        .invoice-header {
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 3px double var(--primary);
            padding-bottom: 18px;
            margin-bottom: 28px;
        }
        .invoice-header .logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .invoice-header .institution {
            flex: 1;
            text-align: center;
        }
        .invoice-header h1 {
            color: var(--primary);
            font-size: 22pt;
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .invoice-header p {
            margin: 2px 0 0;
            font-size: 10pt;
        }
        .invoice-header .logo-spacer {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
        }

        .student-info {
            margin-bottom: 30px;
        }
        .student-info table td {
            padding: 5px 10px;
        }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-section table td {
            padding: 30px 10px;
            border-top: 1px solid #ccc;
        }

        .invoice-actions {
            max-width: 820px;
            margin: 20px auto 0;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media print {
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .invoice-wrap {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                min-height: auto !important;
            }
            .invoice-actions { display: none !important; }
            .invoice {
                box-shadow: none !important;
                margin: 0 auto !important;
                padding: 18mm 16mm !important;
                width: 210mm !important;
                max-width: 210mm !important;
                min-height: 297mm !important;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            .invoice .watermark img {
                opacity: 0.08 !important;
            }
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    @php
    $student = \App\Models\Student::where('user_id', auth()->id())->first();
    $registeredCourses = \App\Models\StudentCourse::where('student_id', $student->id)
        ->where('status', 'registered')
        ->with('course')
        ->get();
    $session = \App\Models\Session::getCurrentSession();

    // Resolve institution info
    $institutionName    = \App\Models\SystemSetting::get('institution_name', 'Institution Management Portal');
    $institutionAddress = \App\Models\SystemSetting::get('institution_address', 'University Road, City, State');
    $institutionPhone   = \App\Models\SystemSetting::get('institution_phone', '+2348000000000');
    $institutionEmail   = \App\Models\SystemSetting::get('institution_email', 'info@portal.edu');

    // Resolve logo URL (prefer public/images/logo.png, fall back to storage)
    $logoUrl = null;
    $publicLogo = public_path('images/logo.png');
    if (file_exists($publicLogo)) {
        $logoUrl = asset('images/logo.png') . '?v=' . time();
    } else {
        $storedLogo = \App\Models\SystemSetting::get('institution_logo');
        if ($storedLogo && file_exists(storage_path('app/public/' . $storedLogo))) {
            $logoUrl = asset('storage/' . $storedLogo);
        }
    }
    @endphp

    <div class="invoice-wrap">
        <div class="invoice">
            {{-- Watermark --}}
            <div class="watermark" aria-hidden="true">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="">
                @endif
            </div>

            {{-- Letterhead --}}
            <div class="invoice-header">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Institution Logo" class="logo">
                @else
                    <div class="logo" style="background:#e9ecef;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#999;">
                        <i class="fas fa-university fa-3x"></i>
                    </div>
                @endif
                <div class="institution">
                    <h1>{{ $institutionName }}</h1>
                    @if($institutionAddress)
                        <p>{{ $institutionAddress }}</p>
                    @endif
                    <p>Phone: {{ $institutionPhone }} | Email: {{ $institutionEmail }}</p>
                </div>
                {{-- Visual filler to keep the institution name centered --}}
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="" class="logo" aria-hidden="true" style="visibility:hidden;">
                @else
                    <div class="logo-spacer" aria-hidden="true"></div>
                @endif
            </div>

            <h3 class="text-center mb-4">COURSE REGISTRATION FORM</h3>

            <div class="student-info">
                <table class="table table-bordered">
                    <tr>
                        <td><strong>Matric Number:</strong> {{ $student->matric_number }}</td>
                        <td><strong>Name:</strong> {{ auth()->user()->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Department:</strong> {{ $student->department->name ?? 'N/A' }}</td>
                        <td><strong>Programme:</strong> {{ $student->programme->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Level:</strong> {{ $student->level_display ?? $student->level }}</td>
                        <td><strong>Session:</strong> {{ $session->name ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>

            <h5>Registered Courses</h5>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>S/N</th>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Units</th>
                        <th>Semester</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalUnits = 0; @endphp
                    @forelse($registeredCourses as $index => $course)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $course->course->code ?? 'N/A' }}</td>
                        <td>{{ $course->course->title ?? 'N/A' }}</td>
                        <td>{{ $course->course->units ?? 0 }}</td>
                        <td>{{ $course->semester }}</td>
                    </tr>
                    @php $totalUnits += $course->course->units ?? 0; @endphp
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">No courses registered.</td>
                    </tr>
                    @endforelse
                    <tr class="table-secondary">
                        <td colspan="3" class="text-end"><strong>Total Units:</strong></td>
                        <td><strong>{{ $totalUnits }}</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <div class="signature-section">
                <table class="table">
                    <tr>
                        <td width="50%">
                            <p><strong>Student's Signature:</strong> _______________________</p>
                            <p class="mt-3">Date: _______________________</p>
                        </td>
                        <td width="50%">
                            <p><strong>Academic Officer's Signature:</strong> _______________________</p>
                            <p class="mt-3">Date: _______________________</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="text-center mt-4 text-muted">
                <p>Generated on: {{ date('d M Y, h:i A') }}</p>
                <p class="no-print">This is a computer-generated document.</p>
            </div>
        </div>

        {{-- Action bar (hidden in print) --}}
        <div class="invoice-actions no-print">
            <a href="{{ route('student.courses') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print / Save as PDF
            </button>
        </div>
    </div>
</body>
</html>