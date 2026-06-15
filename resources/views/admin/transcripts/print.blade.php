<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transcript - {{ $student->matric_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 12pt; }
        .header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #1a237e; margin: 0; }
        .student-info { margin-bottom: 20px; }
        .student-info table td { padding: 5px 10px; }
        .footer { margin-top: 30px; text-align: center; font-size: 10pt; }
        .no-print { margin-bottom: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="no-print text-end">
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-2"></i>Print</button>
            <a href="{{ route('admin.transcripts.show', $student) }}" class="btn btn-secondary">Back</a>
        </div>

        <div class="header">
            <h1>{{ \App\Models\Setting::get('institution_name', 'Institution Management Portal') }}</h1>
            <p>OFFICIAL ACADEMIC TRANSCRIPT</p>
        </div>

        <div class="student-info">
            <table class="table table-bordered">
                <tr>
                    <td><strong>Matric Number:</strong> {{ $student->matric_number }}</td>
                    <td><strong>Name:</strong> {{ $student->user->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Department:</strong> {{ $student->department->name ?? 'N/A' }}</td>
                    <td><strong>Programme:</strong> {{ $student->programme->name ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <h5>Academic Record</h5>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Units</th>
                    <th>CA</th>
                    <th>Exam</th>
                    <th>Total</th>
                    <th>Grade</th>
                    <th>Point</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $result)
                <tr>
                    <td>{{ $result->studentCourse->course->code ?? 'N/A' }}</td>
                    <td>{{ $result->studentCourse->course->title ?? 'N/A' }}</td>
                    <td>{{ $result->studentCourse->course->units ?? 0 }}</td>
                    <td>{{ $result->ca ?? 0 }}</td>
                    <td>{{ $result->exam ?? 0 }}</td>
                    <td>{{ $result->total_score ?? 0 }}</td>
                    <td><strong>{{ $result->grade ?? '-' }}</strong></td>
                    <td>{{ $result->grade_point ?? 0 }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center">No results</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="row mt-4">
            <div class="col-md-6">
                <p><strong>CGPA:</strong> {{ $cgpa }}</p>
            </div>
        </div>

        <div class="footer">
            <p>Generated on: {{ date('d M Y, h:i A') }}</p>
            <p>This is a computer-generated document.</p>
        </div>
    </div>
</body>
</html>