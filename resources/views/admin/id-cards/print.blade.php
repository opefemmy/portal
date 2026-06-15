<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Cards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; }
        .id-card {
            width: 3.375in;
            height: 2.125in;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px;
            margin: 5px;
            display: inline-block;
            background: white;
            page-break-inside: avoid;
        }
        .id-card-header {
            background: linear-gradient(135deg, #1a237e, #6a1b9a);
            color: white;
            padding: 8px;
            margin: -10px -10px 10px -10px;
            border-radius: 6px 6px 0 0;
            text-align: center;
        }
        .id-card-header h6 { margin: 0; font-size: 10pt; }
        .id-card-body { text-align: center; }
        .id-photo {
            width: 60px;
            height: 70px;
            background: #eee;
            border: 2px solid #1a237e;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        .id-info { font-size: 9pt; }
        .id-info .name { font-weight: bold; font-size: 10pt; }
        .id-info .matric { color: #1a237e; font-weight: bold; }
        .id-footer {
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px solid #eee;
            font-size: 8pt;
            color: #666;
        }
        .no-print { margin-bottom: 20px; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .id-card { margin: 2px; }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="no-print text-end mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print ID Cards
            </button>
            <a href="{{ route('admin.id-cards.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>

        <div class="id-cards-container">
            @forelse($students as $student)
            <div class="id-card">
                <div class="id-card-header">
                    <h6>{{ \App\Models\Setting::get('institution_name', 'Institution') }}</h6>
                </div>
                <div class="id-card-body">
                    <div class="id-photo">
                        <i class="fas fa-user fa-2x"></i>
                    </div>
                    <div class="id-info">
                        <div class="name">{{ $student->user->name ?? 'N/A' }}</div>
                        <div class="matric">{{ $student->matric_number }}</div>
                        <div>{{ $student->department->code ?? 'N/A' }} - {{ $student->programme->name ?? 'N/A' }}</div>
                        <div>Level: {{ $student->level_display }}</div>
                    </div>
                </div>
                <div class="id-footer">
                    Valid: {{ date('Y') }} | ID: {{ $student->id }}
                </div>
            </div>
            @empty
            <div class="alert alert-warning">No students selected.</div>
            @endforelse
        </div>
    </div>
</body>
</html>