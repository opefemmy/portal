@extends('layouts.app')

@section('title', 'Edit Course')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Edit Course</h4>
    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Courses
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.courses.update', $course) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Course Code</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $course->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Course Title</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $course->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Units</label>
                    <input type="number" name="units" class="form-control @error('units') is-invalid @enderror"
                           value="{{ old('units', $course->units) }}" min="1" max="10" required>
                    @error('units')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-control @error('semester') is-invalid @enderror" required>
                        <option value="first" {{ old('semester', $course->semester) == 'first' ? 'selected' : '' }}>First</option>
                        <option value="second" {{ old('semester', $course->semester) == 'second' ? 'selected' : '' }}>Second</option>
                    </select>
                    @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">School</label>
                    <select name="school_id" class="form-control @error('school_id') is-invalid @enderror" required>
                        @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ old('school_id', $course->school_id) == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                    @error('school_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control @error('department_id') is-invalid @enderror" required>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id', $course->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Programme</label>
                    <select name="programme_id" class="form-control @error('programme_id') is-invalid @enderror" required>
                        @foreach($programmes as $prog)
                        <option value="{{ $prog->id }}" {{ old('programme_id', $course->programme_id) == $prog->id ? 'selected' : '' }}>{{ $prog->name }}</option>
                        @endforeach
                    </select>
                    @error('programme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Level</label>
                    <input type="number" name="level" class="form-control @error('level') is-invalid @enderror"
                           value="{{ old('level', $course->level) }}" min="1" max="6" required>
                    @error('level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $course->description) }}</textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Update Course
            </button>
            <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection