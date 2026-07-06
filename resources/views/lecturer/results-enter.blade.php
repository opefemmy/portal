@extends('layouts.app')

@section('title', 'Enter Results - ' . $course->code)

@section('content')
<div class="page-header">
    <div class="row">
        <div class="col-md-8">
            <h4>Enter Results: {{ $course->code }} - {{ $course->title }}</h4>
            <p class="text-muted mb-0">
                Department: {{ $assignment->department->name ?? 'N/A' }} |
                Level: {{ $course->level }}
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('lecturer.courses.students', $course) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Students
            </a>
        </div>
    </div>
</div>

{{-- Bulk Upload Form --}}
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>Bulk Upload via Excel</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('lecturer.courses.bulk', $course) }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-md-8">
                <label class="form-label">Select Excel File</label>
                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                <small class="text-muted">Format: Matric No, Fullname, CA1, CA2, Exam</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-dark w-100">
                    <i class="fas fa-upload me-2"></i>Upload Results
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Manual Entry Form --}}
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Manual Entry</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('lecturer.courses.results.store', $course) }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th>Matric No</th>
                            <th>Student Name</th>
                            <th width="100">CA1<br><small>(max 40)</small></th>
                            <th width="100">CA2<br><small>(max 40)</small></th>
                            <th width="100">Exam<br><small>(max 60)</small></th>
                            <th width="100">Total</th>
                            <th width="80">Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($studentCourses as $index => $sc)
                        @php
                            $existingResult = $existingResults[$sc->id] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <input type="hidden" name="results[{{ $index }}][student_course_id]" value="{{ $sc->id }}">
                                <strong>{{ $sc->student->matric_number }}</strong>
                            </td>
                            <td>{{ $sc->student->user->name }}</td>
                            <td>
                                <input type="number" name="results[{{ $index }}][ca1]" class="form-control score-input"
                                    value="{{ $existingResult->ca1 ?? 0 }}" min="0" max="40" step="0.01">
                            </td>
                            <td>
                                <input type="number" name="results[{{ $index }}][ca2]" class="form-control score-input"
                                    value="{{ $existingResult->ca2 ?? 0 }}" min="0" max="40" step="0.01">
                            </td>
                            <td>
                                <input type="number" name="results[{{ $index }}][exam]" class="form-control score-input"
                                    value="{{ $existingResult->exam ?? 0 }}" min="0" max="60" step="0.01">
                            </td>
                            <td class="text-center">
                                <strong class="total-score">
                                    {{ ($existingResult->ca1 ?? 0) + ($existingResult->ca2 ?? 0) + ($existingResult->exam ?? 0) }}
                                </strong>
                            </td>
                            <td class="text-center">
                                <strong class="grade-display">
                                    {{ $existingResult->grade ?? '-' }}
                                </strong>
                            </td>
                            <td>
                                @if($existingResult)
                                    @if($existingResult->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @elseif($existingResult->status === 'pending_approval')
                                        <span class="badge bg-warning">Pending</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $existingResult->status }}</span>
                                    @endif
                                @else
                                    <span class="badge bg-danger">New</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No students registered for this course.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($studentCourses->count() > 0)
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>Save Results
                </button>
                <a href="{{ route('lecturer.courses.template', $course) }}" class="btn btn-outline-dark btn-lg ms-2">
                    <i class="fas fa-download me-2"></i>Download Template
                </a>
            </div>
            @endif
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scoreInputs = document.querySelectorAll('.score-input');

    scoreInputs.forEach(input => {
        input.addEventListener('input', function() {
            const row = this.closest('tr');
            const ca1 = parseFloat(row.querySelector('input[name$="[ca1]"]').value) || 0;
            const ca2 = parseFloat(row.querySelector('input[name$="[ca2]"]').value) || 0;
            const exam = parseFloat(row.querySelector('input[name$="[exam]"]').value) || 0;

            const total = ca1 + ca2 + exam;
            row.querySelector('.total-score').textContent = total.toFixed(2);

            // Calculate grade
            let grade = '-';
            if (total >= 70) grade = 'A';
            else if (total >= 60) grade = 'B';
            else if (total >= 50) grade = 'C';
            else if (total >= 45) grade = 'D';
            else if (total >= 40) grade = 'E';
            else if (total > 0) grade = 'F';

            row.querySelector('.grade-display').textContent = grade;
        });
    });
});
</script>
@endpush
@endsection