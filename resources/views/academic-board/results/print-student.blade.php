<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Result — {{ $student->user->name ?? 'Student' }} ({{ $student->matric_number ?? '' }})</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ==========================================================
           RESULT_1.jpg layout — Landscape A4 with:
             · institution letterhead (normal orientation) at top
             · COURSE TITLE table
             · One wide scores grid (Current / Previous / Cumulative
               columns + Carry Over / Pass / Fail / Outstanding)
             · SUMMARY OF RESULTS table + GRADING SCALE table
               side-by-side
             · 4 horizontal signatures at the bottom
             · HOD · Dean · Registrar · Rector
           ========================================================== */
        @page { size: A4 landscape; margin: 10mm 12mm; }
        html, body { background: #f4f6fa; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10px; }
        .print-sheet {
            background: #fff;
            width: 297mm;
            min-height: 210mm;
            padding: 0;
            margin: 18px auto;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }

        /* Border around the entire page content — matches the
           heavy outer frame on the reference image. */
        .sheet-frame {
            border: 2px solid #000;
            padding: 6mm 8mm 5mm 8mm;
            position: relative;
            min-height: calc(210mm - 20mm);
        }

        /* Title block (top centre) — bold + spaced like reference */
        .title-strip {
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 14px;
            margin-bottom: 3mm;
            border-bottom: 1px solid #000;
            padding-bottom: 2mm;
        }
        .title-strip small {
            display: block;
            font-size: 9px;
            font-weight: 500;
            text-transform: none;
            letter-spacing: .5px;
            color: #444;
            margin-top: 1mm;
        }

        /* Student-info strip directly under the letterhead */
        .student-strip {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 9.5px;
            margin: 2mm 0 3mm;
            padding: 2mm 4mm;
            border: 1px solid #555;
            border-radius: 2px;
            background: #f8f9fb;
        }
        .student-strip .info-cell { flex: 1; }
        .student-strip .info-cell .label {
            text-transform: uppercase;
            font-size: 8px;
            color: #555;
            letter-spacing: .8px;
        }
        .student-strip .info-cell .value {
            font-weight: 600;
            font-size: 10px;
        }

        /* COURSE TITLE / scores / summary / grading tables */
        table.course-title, .scores-grid, .summary-table, .grading-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        table.course-title th, table.course-title td,
        .scores-grid th, .scores-grid td,
        .summary-table th, .summary-table td,
        .grading-table th, .grading-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }
        table.course-title th, .scores-grid thead th,
        .summary-table th, .grading-table th {
            background: #e9ecf3;
            font-weight: 700;
            text-align: center;
        }
        .scores-grid thead .group-head {
            background: #d6dcec;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 9px;
        }

        /* Bottom row: two side-by-side small tables */
        .bottom-row { display: flex; gap: 6mm; margin-top: 4mm; }
        .bottom-row > div { flex: 1; }

        .summary-table .label { text-align: left; font-weight: 600; }
        .grading-table th:first-child { text-align: center; }

        /* Signatures strip — 4 horizontal placeholders across the
           bottom of the page. Names pull from system_settings;
           line is reserved for wet-ink signature. */
        .signature-row {
            margin-top: 6mm;
            display: flex;
            gap: 4mm;
            page-break-inside: avoid;
        }
        .signature-row .sig {
            flex: 1;
            text-align: center;
        }
        .signature-row .sig .line {
            border-top: 1px solid #000;
            min-height: 36px;
            padding-top: 3px;
        }
        .signature-row .sig .name {
            font-weight: 700;
            font-size: 9.5px;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .signature-row .sig .role {
            font-size: 8px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.1;
        }
        .signature-row .sig .date {
            font-size: 7.5px;
            color: #555;
            margin-top: 1mm;
        }

        .actions {
            max-width: 297mm;
            margin: 0 auto 12px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        @media print {
            html, body { background: #fff !important; }
            .actions, .navbar, .sidebar, .main-header, .main-footer { display: none !important; }
            .print-sheet {
                box-shadow: none !important;
                margin: 0 auto !important;
                padding: 0 !important;
                width: 297mm !important;
                min-height: 210mm !important;
            }
            @page { size: A4 landscape; margin: 10mm 12mm; }
        }
    </style>
</head>
<body>
<div class="actions">
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.close()">
        <i class="fas fa-times me-1"></i>Close
    </button>
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="fas fa-print me-1"></i>Print Student Result
    </button>
</div>

<div class="print-sheet">
    <div class="sheet-frame">

        {{-- Top: institution letterhead (normal orientation, centred) --}}
        <div class="title-strip">
            {{ \App\Models\SystemSetting::getInstitutionName() }}
            <small>{{ \App\Models\SystemSetting::get('institution_address') }}
                @if(\App\Models\SystemSetting::get('institution_phone'))  ·  {{ \App\Models\SystemSetting::get('institution_phone') }} @endif
                @if(\App\Models\SystemSetting::get('institution_email'))  ·  {{ \App\Models\SystemSetting::get('institution_email') }} @endif
            </small>
            <small style="margin-top: 1mm;">
                Office of the Registrar · Academic Transcript · Generated {{ now()->format('d/m/Y') }}
            </small>
        </div>

        {{-- Student-info strip — name, matric, programme, dept,
             school, session — so the printout is self-identifying
             even without the score grid. --}}
        <div class="student-strip">
            <div class="info-cell">
                <div class="label">Name</div>
                <div class="value">{{ $student->user->name ?? '—' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Matric No.</div>
                <div class="value">{{ $student->matric_number ?? '—' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Programme</div>
                <div class="value">{{ $student->programme->name ?? '—' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Department</div>
                <div class="value">{{ $student->department->name ?? '—' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">School</div>
                <div class="value">{{ $student->school->name ?? '—' }}</div>
            </div>
        </div>

        {{-- COURSE TITLE table — top of page, the small one --}}
        <table class="course-title">
            <thead>
                <tr>
                    <th style="width: 28px;">S/N</th>
                    <th>COURSE TITLE</th>
                    <th style="width: 70px;">CODES</th>
                    <th style="width: 48px;">UNITS</th>
                    <th style="width: 60px;">TOTAL UNITS</th>
                </tr>
            </thead>
            <tbody>
                @php $runningUnits = 0; @endphp
                @forelse($rows as $r)
                    @php $runningUnits += $r['units']; @endphp
                    <tr>
                        <td>{{ $r['sn'] }}</td>
                        <td style="text-align: left;">
                            {{ ucwords(strtolower(str_replace('_',' ', $r['code']))) }}
                        </td>
                        <td>{{ $r['code'] }}</td>
                        <td>{{ $r['units'] }}</td>
                        <td>{{ $runningUnits }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-2">No approved results found.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>TOTAL UNITS</strong></td>
                    <td><strong>{{ $runningUnits }}</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        {{-- Big scores grid: 3 column groups (Current / Previous /
             Cumulative) × 6 columns each, plus Carry Over /
             Pass/Fail Repeat / Outstanding. --}}
        <table class="scores-grid" style="margin-top: 3mm;">
            <colgroup>
                <col style="width: 28px;"> {{-- S/N --}}
                <col style="width: 80px;"> {{-- Matric / Name --}}
                {{-- CURRENT (6 columns): each course code + score + grade --}}
                <col span="6" style="width: 30px;">
                {{-- PREVIOUS (6 columns): TCP / TLU / GPA --}}
                <col span="6" style="width: 30px;">
                {{-- CUMULATIVE (6 columns): TCP / TLU / GPA --}}
                <col span="6" style="width: 30px;">
                <col style="width: 60px;"> {{-- Carry Over --}}
                <col style="width: 60px;"> {{-- Pass/Fail Repeat --}}
                <col style="width: 60px;"> {{-- Outstanding --}}
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="3">S/N</th>
                    <th rowspan="3">Matric No.</th>
                    <th rowspan="3" style="text-align: left; padding-left: 6px;">NAME</th>
                    <th colspan="6" class="group-head">CURRENT SEMESTER / COURSE CODES / UNITS</th>
                    <th colspan="3" class="group-head">CURRENT</th>
                    <th colspan="3" class="group-head">PREVIOUS</th>
                    <th colspan="3" class="group-head">CUMULATIVE</th>
                    <th rowspan="3">CARRY OVER</th>
                    <th rowspan="3">PASS / FAIL REPEAT</th>
                    <th rowspan="3">OUTSTANDING</th>
                </tr>
                <tr>
                    {{-- Six "current-semester course slot" columns --
                         Each gets the course code + score columns --}}
                    @for($c = 1; $c <= 6; $c++)
                        <th>{{ $rows[($c - 1)]['code'] ?? '' }}</th>
                    @endfor
                    {{-- PREVIOUS headers: TCP / TLU / GPA repeated twice for
                         the reference's two-row header pattern --}}
                    <th>TCP</th><th>TLU</th><th>GPA</th>
                    <th>TCP</th><th>TLU</th><th>GPA</th>
                    <th>TCP</th><th>TLU</th><th>GPA</th>
                    <th>TCP</th><th>TLU</th><th>GPA</th>
                </tr>
                <tr>
                    {{-- Units row under current-semester course codes --}}
                    @for($c = 1; $c <= 6; $c++)
                        <th style="font-weight: 500; font-size: 8px;">
                            {{ isset($rows[$c - 1]) ? $rows[$c - 1]['units'] : '' }}
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $rows[0]['sn'] ?? '' }}</td>
                    <td>{{ $student->matric_number ?? '—' }}</td>
                    <td style="text-align: left; padding-left: 6px;">
                        {{ $student->user->name ?? '—' }}
                    </td>
                    {{-- Current semester: 6 cells (one per row's courses) --}}
                    @for($c = 0; $c < 6; $c++)
                        <td>
                            @if(isset($rows[$c]))
                                <strong>{{ $rows[$c]['grade'] }}</strong>
                            @endif
                        </td>
                    @endfor
                    {{-- PREVIOUS · CUMULATIVE: render the running totals row,
                         split across the 6 cells as the reference does --}}
                    @php
                        $tcp = $rows[0]['tcp'] ?? 0;
                        $tlu = $rows[0]['tlu']  ?? 0;
                        $gpa = $tlu > 0 ? round($tcp / $tlu, 2) : 0;
                    @endphp
                    <td><strong>{{ number_format($tcp, 1) }}</strong></td>
                    <td><strong>{{ $tlu }}</strong></td>
                    <td><strong>{{ number_format($gpa, 2) }}</strong></td>
                    <td colspan="3" style="font-size: 8px; color: #555;">— first attempt —</td>
                    <td><strong>{{ number_format($rows[0]['cu_tcp'] ?? 0, 1) }}</strong></td>
                    <td><strong>{{ $rows[0]['cu_tlu'] ?? 0 }}</strong></td>
                    <td><strong>{{ number_format($rows[0]['cu_gpa'] ?? 0, 2) }}</strong></td>
                    <td colspan="3" style="font-size: 8px; color: #555;">— only attempt —</td>
                    <td>{{ $rows[0]['carry']  ?? 'CLEARED' }}</td>
                    <td>{{ $rows[0]['repeat'] ?? 'PASS' }}</td>
                    <td>
                        @if(($rows[0]['repeat'] ?? '') === 'FAIL')
                            {{ $rows[0]['code'] }}
                        @endif
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr style="background: #f4f6fa;">
                    <td colspan="13" class="text-end"><strong>CUMULATIVE TOTALS</strong></td>
                    <td colspan="2"></td>
                    <td colspan="2"><strong>TCP: {{ number_format($cumulative_tcp, 2) }}</strong></td>
                    <td colspan="2"><strong>TLU: {{ $cumulative_tlu }}</strong></td>
                    <td colspan="2"><strong>GPA: {{ number_format($cumulative_gpa, 2) }}</strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>

        {{-- Bottom row: SUMMARY OF RESULTS (left) + GRADING SCALE (right) --}}
        <div class="bottom-row">
            <div>
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th colspan="2">SUMMARY OF RESULTS</th>
                        </tr>
                        <tr>
                            <th>Classification</th>
                            <th style="width: 50px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="label">3.50 – 4.00 Distinction</td><td>{{ $summary['distinction'] }}</td></tr>
                        <tr><td class="label">3.00 – 3.49 Upper Credit</td><td>{{ $summary['upper_credit'] }}</td></tr>
                        <tr><td class="label">2.50 – 2.99 Lower Credit</td><td>{{ $summary['lower_credit'] }}</td></tr>
                        <tr><td class="label">2.00 – 2.49 Pass</td><td>{{ $summary['pass'] }}</td></tr>
                        <tr><td class="label">0.00 – 1.99 Fail</td><td>{{ $summary['fail'] }}</td></tr>
                        <tr style="background: #eef0f6;">
                            <td class="label"><strong>TOTAL</strong></td>
                            <td><strong>{{ array_sum($summary) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div>
                <table class="grading-table">
                    <thead>
                        <tr>
                            <th colspan="3">CLASSIFICATION OF GRADE</th>
                        </tr>
                        <tr>
                            <th>Score Range</th>
                            <th>Letter Grade</th>
                            <th>Weight</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gradingScale as $g)
                            <tr>
                                <td>{{ $g->min_score }} – {{ $g->max_score }}</td>
                                <td><strong>{{ $g->grade }}</strong></td>
                                <td>{{ $g->gpa_weight }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Outstanding courses list (when any) --}}
        @if(!empty($outstanding))
            <div style="margin-top: 3mm; font-size: 9px;">
                <strong>Outstanding Courses to Repeat:</strong>
                @foreach($outstanding as $o)
                    <span class="badge bg-danger me-1">{{ $o['code'] }} ({{ $o['grade'] }})</span>
                @endforeach
            </div>
        @endif

        {{-- Signatures row — 4 placeholders across the bottom
             (HOD · Dean · Registrar · Rector). Names pull from
             system_settings; signature line is reserved for wet ink. --}}
        @php
            $signatories = [
                ['hod_name',       'Head of Department'],
                ['dean_name',      'Dean of School'],
                ['registrar_name', 'Registrar'],
                ['rector_name',    'Rector / President'],
            ];
            $today = now()->format('d M, Y');
        @endphp
        <div class="signature-row">
            @foreach($signatories as [$key, $role])
                <div class="sig">
                    <div class="line">
                        <div class="name">{{ \App\Models\SystemSetting::get($key, $role) }}</div>
                        <div class="role">{{ $role }}</div>
                    </div>
                    <div class="date">Date: ____ / ____ / {{ now()->format('Y') }}</div>
                </div>
            @endforeach
        </div>

        <div class="text-center text-muted" style="font-size: 8pt; margin-top: 4mm;">
            <em>
                This transcript is computer-generated and is valid only with the
                institutional seal and the signatures above. ·
                {{ \App\Models\SystemSetting::getInstitutionName() }}
                · Printed on {{ $today }}
            </em>
        </div>
    </div>
</div>
</body>
</html>
