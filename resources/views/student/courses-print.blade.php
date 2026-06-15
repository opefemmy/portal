<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Registration Form - {{ auth()->user()->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
        }
        .invoice-header {
            text-align: center;
            border-bottom: 2px solid #1a237e;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .invoice-header h1 {
            color: #1a237e;
            font-size: 24pt;
            margin-bottom: 5px;
        }
        .invoice-header p {
            margin: 0;
            font-size: 10pt;
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
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .no-print {
                display: none;
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
    @endphp

    <div class="container mt-4">
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print
            </button>
            <a href="{{ route('student.courses') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>

        <div class="invoice-header">
            <h1>{{ \App\Models\Setting::get('institution_name', 'Institution Management Portal') }}</h1>
            <p>{{ \App\Models\Setting::get('institution_address', 'University Road, City, State') }}</p>
            <p>Phone: {{ \App\Models\Setting::get('institution_phone', '+2348000000000') }} | Email: {{ \App\Models\Setting::get('institution_email', 'info@portal.edu') }}</p>
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
                    <td><strong>Level:</strong> {{ $student->level_display }}</td>
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
</body>
</html>