<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission List - {{ date('Y') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #1a237e; margin: 0; }
        .table th { background-color: #1a237e; color: white; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="no-print mb-3 text-end">
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-2"></i>Print</button>
            <a href="{{ route('registrar.admission') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
        </div>

        <div class="header">
            <h1>{{ \App\Models\Setting::get('institution_name', 'Institution Management Portal') }}</h1>
            <p>ADMITTED STUDENTS LIST - {{ date('Y') }}</p>
        </div>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Application No.</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Programme</th>
                    <th>Date Admitted</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admitted as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->application_number ?? 'N/A' }}</td>
                    <td>{{ $student->full_name ?? $student->user->name ?? 'N/A' }}</td>
                    <td>{{ $student->email ?? $student->user->email ?? 'N/A' }}</td>
                    <td>{{ $student->department->name ?? 'N/A' }}</td>
                    <td>{{ $student->programme->name ?? 'N/A' }}</td>
                    <td>{{ $student->updated_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No admitted students.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            <p><strong>Total Admitted:</strong> {{ $admitted->count() }}</p>
            <p><strong>Date Printed:</strong> {{ date('d M Y, h:i A') }}</p>
        </div>
    </div>
</body>
</html>