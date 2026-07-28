@extends('layouts.app')

@section('title', 'Upload Courses')

@section('content')
<div class="page-header">
    <h4>Upload Courses</h4>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Upload Courses from Excel/CSV</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.courses.upload') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="file" class="form-label">Select File (CSV or Excel)</label>
                        <input type="file" name="file" id="file" class="form-control" accept=".csv,.xlsx,.xls" required>
                        <small class="text-muted">Maximum file size: 2MB</small>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i> Upload Courses
                        </button>
                        <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </form>

                @if(session('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('warning'))
                <div class="alert alert-warning mt-3">
                    {{ session('warning') }}
                    @if(session('errors'))
                    <ul class="mt-2 mb-0">
                        @foreach(session('errors') as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger mt-3">
                    {{ session('error') }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Instructions</h5>
            </div>
            <div class="card-body">
                <h6>File Format:</h6>
                <p class="small">Upload a CSV or Excel file (.csv, .xlsx, .xls) with the following columns:</p>

                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Column</th>
                            <th>Required</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>code</td><td><span class="badge bg-danger">Yes</span></td></tr>
                        <tr><td>title</td><td><span class="badge bg-danger">Yes</span></td></tr>
                        <tr><td>units</td><td><span class="badge bg-danger">Yes</span></td></tr>
                        <tr><td>semester</td><td><span class="badge bg-danger">Yes</span></td></tr>
                        <tr><td>school_code</td><td><span class="badge bg-danger">Yes</span></td></tr>
                        <tr><td>department_code</td><td><span class="badge bg-danger">Yes</span></td></tr>
                        <tr><td>programme_code</td><td><span class="badge bg-danger">Yes</span></td></tr>
                        <tr><td>level</td><td><span class="badge bg-danger">Yes</span></td></tr>
                        <tr><td>description</td><td><span class="badge bg-secondary">No</span></td></tr>
                    </tbody>
                </table>

                <h6>Example Data:</h6>
                <pre class="small bg-light p-2">code,title,units,semester,school_code,department_code,programme_code,level,description
CSC101,Computer Science 101,3,first,Science,Computer Science,Computer Engineering,100,Intro to computing
CSC102,Data Structures,3,second,Science,Computer Science,Computer Engineering,100,Arrays and linked lists</pre>

                <h6>Notes:</h6>
                <ul class="small">
                    <li>semester must be "first" or "second"</li>
                    <li>school_code, department_code, programme_code must exist in the database</li>
                    <li>Existing courses will be updated if they match (same code, school, department, programme, level)</li>
                </ul>

                <a href="{{ asset('templates/courses_upload_template.csv') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-download me-1"></i> Download Template
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
