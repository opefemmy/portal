@extends('layouts.app')

@section('title', 'Assign Course to Lecturer')

@section('content')
<div class="page-header">
    <h4>Assign Course to Lecturer</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.course-assignments.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Course</label>
                        <select class="form-select @error('course_id') is-invalid @enderror"
                                id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->title }} ({{ $course->department->name ?? 'N/A' }})</option>
                            @endforeach
                        </select>
                        @error('course_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="lecturer_id" class="form-label">Lecturer</label>
                        <select class="form-select @error('lecturer_id') is-invalid @enderror"
                                id="lecturer_id" name="lecturer_id" required>
                            <option value="">Select Lecturer</option>
                            @foreach($lecturers as $lecturer)
                                <option value="{{ $lecturer->id }}">{{ $lecturer->name }}</option>
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
                        <label for="session_id" class="form-label">Session</label>
                        <select class="form-select @error('session_id') is-invalid @enderror"
                                id="session_id" name="session_id" required>
                            <option value="">Select Session</option>
                            @foreach(\App\Models\Session::all() as $session)
                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                            @endforeach
                        </select>
                        @error('session_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select @error('semester') is-invalid @enderror"
                                id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <option value="First">First Semester</option>
                            <option value="Second">Second Semester</option>
                        </select>
                        @error('semester')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Assign Course
                </button>
                <a href="{{ route('admin.course-assignments.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection