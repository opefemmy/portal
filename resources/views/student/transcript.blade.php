{{--
    Official Academic Transcript — student-facing.

    Layout mirrors the reference PDF (`docs/OLORUNKOSEBI Adelowo_s
    Academic Transcript.pdf`):
      • Page 1: institutional letterhead (via the shared
        `partials.print.institution-header`), a tiled anti-fraud
        watermark (`partials.print.student-watermark`), the title
        "OFFICIAL ACADEMIC TRANSCRIPT", and a 9-row biodata block.
      • Per session (one section each): a heading row, then a course
        table per semester with S/N | Code | Title | CU | Score |
        Grade | GP | Remark columns, followed by the per-semester
        totals row (TCP / TLU, TGP / TLU, GPA).
      • After the last session: a Cumulative Summary block
        (TCP, TCE, CTLP, CTLU, CGPA, CLASS OF DEGREE AWARDED).
      • Final page: the Grading System legend (driven by the
        `grading_scales` table — admin-editable, not hard-coded) and
        the signature block.

    The same view serves two response types:
      • GET /student/results/transcript           → HTML (this file + layouts.app)
      • GET /student/results/transcript/print     → DOMPDF stream (A4 portrait)

    DOMPDF picks up the `@page` rule and the `.page-break` divs to
    produce the multi-page layout the reference shows. The on-screen
    HTML ignores `@page` but honours `.page-break` for browser print.

    The "Date of Graduation" cell is a dash because the system has no
    `students.date_of_graduation` column today; add one in a future
    migration and the cell picks it up automatically.
--}}
@extends('layouts.app')

@section('title', 'Official Academic Transcript')

@push('styles')
<style>
    @page { size: A4 portrait; margin: 12mm; }

    .transcript-page {
        font-family: 'Times New Roman', Times, serif;
        color: #000;
        position: relative;
    }

    .transcript-title {
        text-align: center;
        letter-spacing: 1px;
        font-weight: 700;
        font-size: 18pt;
        margin: 12px 0 0 0;
    }

    .transcript-subtitle {
        text-align: center;
        font-style: italic;
        font-size: 11pt;
        margin: 0 0 14px 0;
    }

    .transcript-biodata,
    .transcript-biodata th,
    .transcript-biodata td {
        border: 1px solid #000;
        border-collapse: collapse;
    }
    .transcript-biodata {
        width: 100%;
        margin-bottom: 16px;
    }
    .transcript-biodata th {
        background: #f0f0f0;
        text-align: left;
        width: 32%;
        padding: 5px 8px;
        font-size: 10pt;
        font-weight: 700;
    }
    .transcript-biodata td {
        padding: 5px 8px;
        font-size: 10pt;
    }
    .transcript-biodata .inline-extra {
        font-size: 9pt;
        color: #555;
    }

    .transcript-semester-heading {
        background: #000;
        color: #fff;
        padding: 5px 10px;
        margin: 14px 0 6px 0;
        font-weight: 700;
        font-size: 11pt;
        letter-spacing: 0.5px;
    }
    .transcript-semester-sub {
        margin: 6px 0 4px 0;
        font-weight: 700;
        font-size: 10.5pt;
    }

    .transcript-table,
    .transcript-table th,
    .transcript-table td {
        border: 1px solid #000;
        border-collapse: collapse;
    }
    .transcript-table {
        width: 100%;
        margin-bottom: 4px;
    }
    .transcript-table th {
        background: #f0f0f0;
        font-size: 9.5pt;
        padding: 4px 6px;
        text-align: left;
    }
    .transcript-table td {
        font-size: 9.5pt;
        padding: 3px 6px;
    }
    .transcript-table .center { text-align: center; }
    .transcript-table tfoot td {
        background: #fafafa;
        font-weight: 700;
    }

    .transcript-cumulative {
        margin-top: 18px;
        border: 2px solid #000;
        padding: 10px 14px;
    }
    .transcript-cumulative table { width: 100%; }
    .transcript-cumulative td {
        padding: 4px 0;
        font-size: 11pt;
    }
    .transcript-cumulative td.label {
        font-weight: 700;
        width: 65%;
    }
    .transcript-cumulative td.value {
        font-weight: 700;
        text-align: right;
    }
    .transcript-cumulative tr.classification td {
        font-size: 12pt;
        border-top: 1px solid #000;
        padding-top: 8px;
    }

    .transcript-legend-wrap {
        margin-top: 18px;
    }
    .transcript-legend-note {
        font-size: 8.5pt;
        color: #555;
        margin-top: 4px;
    }

    .transcript-signatures {
        margin-top: 28px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }
    .transcript-sigblock {
        border-top: 1px solid #000;
        padding-top: 6px;
        font-size: 9.5pt;
    }
    .transcript-sigblock strong { display: block; }

    .transcript-issuance {
        margin-top: 10px;
        font-size: 8.5pt;
        color: #555;
        text-align: center;
    }

    .page-break { page-break-after: always; }
    @media print {
        .no-print { display: none !important; }
    }
</style>
@endpush

@section('content')
<div class="transcript-page">

    {{-- Reusable anti-fraud watermark + institutional letterhead. --}}
    @include('partials.print.student-watermark', ['student' => $student])
    @include('partials.print.institution-header')

    <h2 class="transcript-title">OFFICIAL ACADEMIC TRANSCRIPT</h2>
    <p class="transcript-subtitle">Transcript of Academic Record</p>

    {{-- Biodata block ------------------------------------------------- --}}
    <table class="transcript-biodata">
        <tr>
            <th>Name</th>
            <td>
                {{ $student->user->name ?? 'N/A' }}
                <span class="inline-extra">
                    (Sex: {{ ucfirst((string) ($student->user->gender ?? 'N/A')) }}
                    &nbsp;|&nbsp; D.O.B: {{ optional($student->user->date_of_birth)->format('d/m/Y') ?: 'N/A' }}
                    &nbsp;|&nbsp; State: {{ $student->applicant->state_of_origin ?? 'N/A' }})
                </span>
            </td>
        </tr>
        <tr>
            <th>Registration Number</th>
            <td>{{ $student->applicant->jamb_registration_number ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Matric Number</th>
            <td>{{ $student->matric_number ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Programme</th>
            <td>{{ $student->programme->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Department</th>
            <td>{{ $student->department->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>School</th>
            <td>{{ $student->department->school->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Year of Admission</th>
            <td>{{ $student->applicant->session->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Mode of Entry</th>
            <td>{{ ucfirst((string) ($student->applicant->mode_of_study ?? 'N/A')) }}</td>
        </tr>
        <tr>
            <th>Date of Graduation</th>
            <td>
                {{-- No `students.date_of_graduation` column today. The dash keeps the
                     row present so the layout matches the reference; a future migration
                     can populate it and this cell picks it up automatically. --}}
                —
            </td>
        </tr>
    </table>

    {{-- Per-session → per-semester tables ------------------------------ --}}
    @forelse($allResults as $sessionBlock)
        <div class="page-break"></div>

        <h4 class="transcript-semester-heading">
            {{ $sessionBlock['session']->name }}
        </h4>

        @foreach($sessionBlock['semesters'] as $semesterBlock)
            @php
                $stats = $semesterBlock['stats'] ?? [];
                $tcp   = $stats['tcp'] ?? null;
                $tlu   = $stats['tlu'] ?? null;
                $gpa   = $stats['gpa'] ?? null;
            @endphp

            <div class="transcript-semester-sub">
                {{ $semesterBlock['semester']->name }} Semester
            </div>

            <table class="transcript-table">
                <thead>
                    <tr>
                        <th class="center" style="width:5%;">S/N</th>
                        <th style="width:12%;">Course Code</th>
                        <th>Course Title</th>
                        <th class="center" style="width:7%;">CU</th>
                        <th class="center" style="width:8%;">Score</th>
                        <th class="center" style="width:7%;">Grade</th>
                        <th class="center" style="width:7%;">GP</th>
                        <th class="center" style="width:10%;">Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($semesterBlock['results'] as $i => $result)
                        @php $course = $result->studentCourse->course ?? null; @endphp
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td>{{ $course->code ?? 'N/A' }}</td>
                            <td>{{ $course->title ?? $course->name ?? 'N/A' }}</td>
                            <td class="center">{{ $course->units ?? 0 }}</td>
                            <td class="center">{{ $result->total_score !== null ? rtrim(rtrim(number_format((float) $result->total_score, 2, '.', ''), '0'), '.') : '—' }}</td>
                            <td class="center">{{ $result->grade ?? '—' }}</td>
                            <td class="center">{{ $result->grade_point !== null ? number_format((float) $result->grade_point, 2) : '—' }}</td>
                            <td class="center">
                                {{ $result->remarks ?? ($result->grade === 'F' ? 'FAIL' : 'PASS') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    {{-- The reference shows two totals rows per semester: a
                         "(TCP / TLU)" row and a "(TGP / TLU) GPA" row.
                         `tcp` from ResultComputationService IS the total load
                         points sum (Σ Unit × GradePoint), so it serves as
                         both the row-1 and row-2 numerator. --}}
                    <tr>
                        <td colspan="3" class="center" style="text-align:right;">(TCP</td>
                        <td class="center">{{ $tcp !== null ? number_format($tcp, 2) : '—' }}</td>
                        <td colspan="2" class="center" style="text-align:right;">/ TLU</td>
                        <td class="center">{{ $tlu !== null ? rtrim(rtrim(number_format($tlu, 2, '.', ''), '0'), '.') : '—' }})</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="center" style="text-align:right;">(TGP</td>
                        <td class="center">{{ $tcp !== null ? number_format($tcp, 2) : '—' }}</td>
                        <td colspan="2" class="center" style="text-align:right;">/ TLU</td>
                        <td class="center" colspan="2">
                            {{ $tlu !== null ? rtrim(rtrim(number_format($tlu, 2, '.', ''), '0'), '.') : '—' }})
                            &nbsp; GPA: <strong>{{ $gpa !== null ? number_format($gpa, 2) : '—' }}</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        @endforeach
    @empty
        <p class="text-center text-muted">No academic record available yet.</p>
    @endforelse

    {{-- Cumulative summary -------------------------------------------- --}}
    <div class="page-break"></div>

    <h4 class="transcript-semester-heading">Cumulative Summary</h4>
    <div class="transcript-cumulative">
        <table>
            <tr>
                <td class="label">Total Credit Points (TCP)</td>
                <td class="value">{{ isset($cumulative['tcp']) ? number_format($cumulative['tcp'], 2) : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Total Credits Earned (TCE)</td>
                <td class="value">{{ isset($cumulative['tup']) ? rtrim(rtrim(number_format($cumulative['tup'], 2, '.', ''), '0'), '.') : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Cumulative Total Load Points (CTLP)</td>
                <td class="value">{{ isset($cumulative['tcp']) ? number_format($cumulative['tcp'], 2) : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Cumulative Total Load Units (CTLU)</td>
                <td class="value">{{ isset($cumulative['tlu']) ? rtrim(rtrim(number_format($cumulative['tlu'], 2, '.', ''), '0'), '.') : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Cumulative Grade Point Average (CGPA)</td>
                <td class="value">{{ isset($cumulative['cgpa']) ? number_format($cumulative['cgpa'], 2) : '—' }}</td>
            </tr>
            <tr class="classification">
                <td class="label">CLASS OF DEGREE AWARDED:</td>
                <td class="value">{{ $academicRemark ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- Grading system ------------------------------------------------- --}}
    <div class="transcript-legend-wrap">
        <h4 class="transcript-semester-heading">Grading System</h4>
        <table class="transcript-table">
            <thead>
                <tr>
                    <th>Score Range</th>
                    <th>Grade</th>
                    <th>Grade Point</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                @foreach(\App\Models\GradingScale::orderBy('sort_order')->get() as $scale)
                    <tr>
                        <td>{{ $scale->min_score }}–{{ $scale->max_score }}</td>
                        <td class="center"><strong>{{ $scale->grade }}</strong></td>
                        <td class="center">{{ number_format($scale->grade_point, 2) }}</td>
                        <td class="center">{{ strtoupper($scale->remark) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="transcript-legend-note">
            NBTE/NUC 4.0-weight scale. The legend is rendered from the
            <code>grading_scales</code> table — admin-editable.
        </p>
    </div>

    {{-- Signature block ------------------------------------------------ --}}
    <h4 class="transcript-semester-heading">Certifications &amp; Signatures</h4>
    <div class="transcript-signatures">
        <div class="transcript-sigblock">
            <strong>D. Oyinloye, FOAAL</strong>
            For: Registrar
            <br>Date Issued: {{ now()->format('d/m/Y') }}
        </div>
        <div class="transcript-sigblock">
            <strong>Alhaji Asiwaju A. Akintola</strong>
            Bursar
        </div>
        <div class="transcript-sigblock">
            <strong>Mr. Akinola F. Oyeleke, FOAAL, FOFAAL, FCA</strong>
            Director — ICAN examination passed at intermediate level
        </div>
    </div>
    <p class="transcript-issuance">
        Official stamp, PIN, and barcode are applied at issuance by the Registrar's office.
        This electronic copy is generated from the live <code>results</code> table.
    </p>

    {{-- On-screen only: action buttons. The PDF stream route doesn't
         render this section (it only renders the printable content),
         because the DOMPDF view passes a flag the controller sets. --}}
    @if(!isset($pdfMode))
        <div class="no-print text-center mt-4">
            <a href="{{ route('student.results.transcript.print') }}" class="btn btn-primary" target="_blank">
                <i class="fas fa-download me-1"></i> Download as PDF
            </a>
            <button type="button" onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print me-1"></i> Print
            </button>
        </div>
    @endif
</div>
@endsection
