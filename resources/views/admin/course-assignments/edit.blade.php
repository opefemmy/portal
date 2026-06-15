@extends('layouts.app')

@section('title', 'Edit Course Assignment')

@section('content')
<div class="page-header">
    <h4>Edit Course Assignment</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.course-assignments.update', $assignment) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <input type="text" class="form-control" value="{{ $assignment->course->code ?? 'N/A' }} - {{ $assignment->course->title ?? 'N/A' }}" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="lecturer_id" class="form-label">Lecturer</label>
                        <select class="form-select @error('lecturer_id') is-invalid @enderror"
                                id="lecturer_id" name="lecturer_id" required>
                            <option value="">Select Lecturer</option>
                            @foreach($lecturers as $lecturer)
                                <option value="{{ $lecturer->id }}" {{ $assignment->lecturer_id == $lecturer->id ? 'selected' : '' }}>
                                    {{ $lecturer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('lecturer_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Session</label>
                        <input type="text" class="form-control" value="{{ $assignment->session->name ?? 'N/A' }}" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select @error('semester') is-invalid @enderror"
                                id="semester" name="semester" required>
                            <option value="First" {{ $assignment->semester == 'First' ? 'selected' : '' }}>First Semester</option>
                            <option value="Second" {{ $assignment->semester == 'Second' ? 'selected' : '' }}>Second Semester</option>
                        </select>
                        @error('semester')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Assignment
                </button>
                <a href="{{ route('admin.course-assignments.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection