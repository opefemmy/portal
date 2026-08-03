@extends('layouts.app')

@section('title', 'Transcript - ' . $student->matric_number)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>Transcript</h4>
        <p class="text-muted mb-0">{{ $student->matric_number }} - {{ $student->user->name ?? 'N/A' }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.transcripts.print', $student) }}" class="btn btn-success" target="_blank">
            <i class="fas fa-print me-2"></i>Print Transcript
        </a>
        <a href="{{ route('admin.transcripts.index') }}" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Department:</strong> {{ $student->department->name ?? 'N/A' }}
            </div>
            <div class="col-md-3">
                <strong>Programme:</strong> {{ $student->programme->name ?? 'N/A' }}
            </div>
            <div class="col-md-3">
                <strong>Level:</strong> {{ $student->level_display }}
            </div>
            <div class="col-md-3">
                <strong>CGPA:</strong> <span class="text-success fw-bold">{{ $cgpa }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5>Academic Record</h5>
        <table class="table table-bordered">
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
                <tr>
                    <td colspan="8" class="text-center py-4">No results found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(isset($previousResults) && $previousResults->count())
<div class="card mt-3">
    <div class="card-body">
        <h5>Previous / Historical Results</h5>
        <p class="text-muted small">Ingested from previous institutions — included in the transcript.</p>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Session / Sem / Level</th>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Units</th>
                    <th>Total</th>
                    <th>Grade</th>
                    <th>Point</th>
                    <th>Source</th>
                </tr>
            </thead>
            <tbody>
                @foreach($previousResults as $pr)
                <tr>
                    <td>{{ $pr->session_name }} · {{ ucfirst($pr->semester) }} · L{{ $pr->level ?? '—' }}</td>
                    <td>{{ $pr->course_code }}</td>
                    <td>{{ $pr->course_title ?? '—' }}</td>
                    <td>{{ $pr->units }}</td>
                    <td>{{ number_format($pr->total_score, 1) }}</td>
                    <td><strong>{{ $pr->grade ?? '—' }}</strong></td>
                    <td>{{ $pr->grade_point ?? '—' }}</td>
                    <td><small>{{ $pr->source_institution ?? '—' }}</small></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection