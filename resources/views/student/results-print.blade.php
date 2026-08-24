<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Results - Print</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .info { margin: 20px 0; }
        .info p { margin: 4px 0; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    @if(isset($student))
        @include('partials.print.student-watermark')
    @endif
    @include('partials.print.institution-header')
    <h1>Academic Results</h1>

    @if(isset($student))
    <div class="info">
        <p><strong>Name:</strong> {{ $student->full_name ?? $student->name ?? 'N/A' }}</p>
        <p><strong>Matric Number:</strong> {{ $student->matric_no ?? $student->matric_number ?? 'N/A' }}</p>
        <p><strong>Programme:</strong> {{ $student->programme->name ?? $student->programme_name ?? 'N/A' }}</p>
        <p><strong>Session:</strong> {{ $semester->session ?? '' }}</p>
    </div>
    @endif

    @if(isset($results) && count($results) > 0)
    <table>
        <thead>
            <tr>
                <th>Course Code</th>
                <th>Course Title</th>
                <th>Credit Unit</th>
                <th>Score</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $result)
                <tr>
                    <td>{{ $result->course->code ?? $result->course_code ?? 'N/A' }}</td>
                    <td>{{ $result->course->title ?? $result->course_title ?? 'N/A' }}</td>
                    <td>{{ $result->course->credit_unit ?? $result->credit_unit ?? 0 }}</td>
                    <td>{{ $result->total ?? $result->score ?? '-' }}</td>
                    <td>{{ $result->grade ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align:center;">No results available.</p>
    @endif

    <div class="no-print" style="text-align:center; margin-top:30px;">
        <button onclick="window.print()" style="padding:10px 20px;">Print Results</button>
    </div>
</body>
</html>
