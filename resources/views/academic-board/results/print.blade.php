<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Final Approved Result — {{ $student->user->name ?? 'Student' }}</title>
    <link href="https://cdnjsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        html, body { background: #f4f6fa; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; }
        .print-sheet {
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            padding: 18mm 16mm;
            margin: 30px auto;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }
        .print-sheet h1 {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 18px;
            margin: 18px 0;
        }
        .print-sheet table.meta td {
            padding: 4px 10px 4px 0;
            vertical-align: top;
            font-size: 14px;
        }
        .print-sheet table.scores {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 13px;
        }
        .print-sheet table.scores th,
        .print-sheet table.scores td {
            border: 1px solid #c0c4d0;
            padding: 8px;
            text-align: left;
        }
        .print-sheet table.scores th { background: #f0f2fa; }
        .actions {
            max-width: 210mm;
            margin: 0 auto 16px;
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
                padding: 16mm 14mm !important;
            }
            @page { size: A4 portrait; margin: 0; }
        }
    </style>
</head>
<body>
<div class="actions">
    <button type="button" class="btn btn-secondary" onclick="window.close()">Close</button>
    <a href="{{ route('academic-board.results.print-student', $student) }}"
       class="btn btn-outline-dark"
       target="_blank"
       title="Open the student's full transcript in a new tab">
        <i class="fas fa-file-alt me-1"></i>View Full Transcript
    </a>
    <button type="button" class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print me-1"></i>Print
    </button>
</div>

<div class="print-sheet">
    @include('partials.print.student-watermark')
    @include('partials.print.institution-header')

    <h1>Final Approved Result</h1>

    <table class="meta">
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
            <td><strong>School:</strong></td>
            <td>{{ $student->school->name ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Session:</strong></td>
            <td>{{ $result->studentCourse->session->name ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Date Approved:</strong></td>
            <td>{{ $result->approved_at ? $result->approved_at->format('d M Y') : '—' }}</td>
        </tr>
    </table>

    <table class="scores">
        <thead>
            <tr>
                <th>Course Code</th>
                <th>Course Title</th>
                <th>CA1</th>
                <th>CA2</th>
                <th>Exam</th>
                <th>Total</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $result->studentCourse->course->code ?? '—' }}</td>
                <td>{{ $result->studentCourse->course->title ?? '—' }}</td>
                <td>{{ $result->ca1 ?? 0 }}</td>
                <td>{{ $result->ca2 ?? 0 }}</td>
                <td>{{ $result->exam ?? 0 }}</td>
                <td>{{ $result->total_score ?? 0 }}</td>
                <td><strong>{{ $result->grade ?? '—' }}</strong></td>
            </tr>
        </tbody>
    </table>

    @include('admin.transcripts._signing_block')

    <p class="text-muted small mt-4 text-center">
        Generated on {{ now()->format('d M Y, H:i') }} ·
        Approved by {{ $result->approvedBy->name ?? 'Academic Board' }}
    </p>
</div>
</body>
</html>