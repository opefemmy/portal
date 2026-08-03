@extends('layouts.app')

@section('title', 'Edit Result')

@section('content')
@php
    /** @var \App\Models\Result $result */
    /** @var \App\Models\StudentCourse $studentCourse */
    $student = $studentCourse->student ?? null;
    $user = $student->user ?? null;
    $course = $studentCourse->course ?? null;
@endphp

<div class="page-header">
    <div class="row">
        <div class="col-md-8">
            <h4>Edit Result</h4>
            <p class="text-muted mb-0">
                @if($course)
                    {{ $course->code }} - {{ $course->title }}
                @endif
            </p>
        </div>
        <div class="col-md-4 text-end">
            @if($course)
                <a href="{{ route('lecturer.courses.results', $course) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Results
                </a>
            @else
                <a href="{{ route('lecturer.courses') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to My Courses
                </a>
            @endif
        </div>
    </div>
</div>

@if($result->status === 'approved')
    <div class="alert alert-warning">
        <i class="fas fa-lock me-2"></i>
        This result has been approved and can no longer be edited.
    </div>
@endif

{{-- Student + result context --}}
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Student Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">Matric No</small>
                <div><strong>{{ $student->matric_number ?? 'N/A' }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Full Name</small>
                <div><strong>{{ $user->name ?? 'N/A' }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Course</small>
                <div><strong>{{ $course->code ?? 'N/A' }} — {{ $course->title ?? '' }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Current Status</small>
                <div>
                    @if($result->status === 'approved')
                        <span class="badge bg-success">Approved</span>
                    @elseif($result->status === 'pending_approval')
                        <span class="badge bg-warning text-dark">Pending HOD</span>
                    @else
                        <span class="badge bg-secondary">{{ $result->status }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Score edit form --}}
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Scores</h5>
    </div>
    <div class="card-body">
        @if($result->status === 'approved')
            <div class="alert alert-info mb-0">
                Scores below are shown read-only because this result is already approved.
            </div>
        @endif

        <form method="POST" action="{{ route('lecturer.result.update', $result) }}" class="mt-3">
            @csrf
            @method('PUT')

            {{-- Hidden scope fields: the controller's enforceScope() guard
                 verifies these against the course's school/department/
                 programme/session/level, so the picker values must travel
                 with the form. --}}
            @if($course)
                <input type="hidden" name="school_id" value="{{ $course->school_id }}">
                <input type="hidden" name="department_id" value="{{ $course->department_id }}">
                <input type="hidden" name="programme_id" value="{{ $course->programme_id }}">
                <input type="hidden" name="session_id" value="{{ $studentCourse->session_id ?? '' }}">
                <input type="hidden" name="level" value="{{ $course->level }}">
            @endif

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">CA 1 <small class="text-muted">(max 40)</small></label>
                    <input
                        type="number"
                        name="ca1"
                        class="form-control score-input"
                        value="{{ old('ca1', $result->ca1 ?? 0) }}"
                        min="0" max="40" step="0.01"
                        @disabled($result->status === 'approved')
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">CA 2 <small class="text-muted">(max 40)</small></label>
                    <input
                        type="number"
                        name="ca2"
                        class="form-control score-input"
                        value="{{ old('ca2', $result->ca2 ?? 0) }}"
                        min="0" max="40" step="0.01"
                        @disabled($result->status === 'approved')
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Exam <small class="text-muted">(max 60)</small></label>
                    <input
                        type="number"
                        name="exam"
                        class="form-control score-input"
                        value="{{ old('exam', $result->exam ?? 0) }}"
                        min="0" max="60" step="0.01"
                        @disabled($result->status === 'approved')
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total</label>
                    <div class="form-control-plaintext">
                        <strong class="total-score">
                            {{ number_format((float)($result->ca1 ?? 0) + (float)($result->ca2 ?? 0) + (float)($result->exam ?? 0), 2) }}
                        </strong>
                        <small class="text-muted ms-2">/ 100</small>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <label class="form-label">Grade</label>
                    <div class="form-control-plaintext">
                        <strong class="grade-display">{{ $result->grade ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Remarks</label>
                    <div class="form-control-plaintext text-muted">
                        {{ $result->remarks ?? '—' }}
                    </div>
                </div>
            </div>

            @if($result->status !== 'approved')
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    @if($course)
                        <a href="{{ route('lecturer.courses.results', $course) }}" class="btn btn-outline-secondary btn-lg">
                            Cancel
                        </a>
                    @endif
                </div>
                <small class="text-muted d-block mt-2">
                    Saving will reset this result to <span class="badge bg-warning text-dark">Pending HOD</span> so it goes through approval again.
                </small>
            @endif
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scoreInputs = document.querySelectorAll('.score-input');

    function recompute() {
        const form = document.querySelector('form');
        if (!form) return;
        const ca1 = parseFloat(form.querySelector('input[name="ca1"]').value) || 0;
        const ca2 = parseFloat(form.querySelector('input[name="ca2"]').value) || 0;
        const exam = parseFloat(form.querySelector('input[name="exam"]').value) || 0;
        const total = ca1 + ca2 + exam;

        const totalEl = document.querySelector('.total-score');
        if (totalEl) totalEl.textContent = total.toFixed(2);

        // Match the same grade thresholds used in results-enter.blade.php.
        let grade = '-';
        if (total >= 70) grade = 'A';
        else if (total >= 60) grade = 'B';
        else if (total >= 50) grade = 'C';
        else if (total >= 45) grade = 'D';
        else if (total >= 40) grade = 'E';
        else if (total > 0) grade = 'F';

        const gradeEl = document.querySelector('.grade-display');
        if (gradeEl) gradeEl.textContent = grade;
    }

    scoreInputs.forEach(input => {
        input.addEventListener('input', recompute);
    });

    recompute();
});
</script>
@endpush
@endsection