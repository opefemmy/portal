@extends('layouts.app')

@section('title', 'Upload Results')

@section('content')
<div class="page-header">
    <h4>Upload Results</h4>
</div>

<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Instructions</h5>
    </div>
    <div class="card-body">
        <ol>
            <li>Download the template file to see the required format</li>
            <li>Fill in the data using the template format</li>
            <li>Select the course, session, and semester for the results</li>
            <li>Upload the CSV file</li>
        </ol>
        <a href="{{ route('admin.results.template') }}" class="btn btn-outline-primary">
            <i class="fas fa-download me-2"></i>Download Template
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.results.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Course *</label>
                        <select class="form-select @error('course_id') is-invalid @endif" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            @foreach(\App\Models\Course::all() as $course)
                                <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->title }}</option>
                            @endforeach
                        </select>
                        @error('course_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session *</label>
                        <select class="form-select @error('session_id') is-invalid @endif" id="session_id" name="session_id" required>
                            <option value="">Select Session</option>
                            @foreach(\App\Models\Session::all() as $session)
                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                            @endforeach
                        </select>
                        @error('session_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester *</label>
                        <select class="form-select @error('semester') is-invalid @endif" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <option value="first">First Semester</option>
                            <option value="second">Second Semester</option>
                        </select>
                        @error('semester')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label">CSV File *</label>
                <input type="file" class="form-control @error('file') is-invalid @endif"
                       id="file" name="file" accept=".csv,.xlsx,.xls" required>
                @error('file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @endif
                <small class="text-muted">Accepted formats: CSV, XLSX, XLS</small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i>Upload Results
                </button>
                <a href="{{ route('admin.results.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection