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
@endsection