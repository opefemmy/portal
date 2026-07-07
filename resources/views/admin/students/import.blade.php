@extends('layouts.app')

@section('title', 'Import Students')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Import Students</h4>
    <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Students
    </a>
</div>

<!-- Success Message -->
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Error Messages -->
@if(session('errorCount'))
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    {{ session('errorCount') }} errors occurred during import.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('errors'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <h6>Import Errors:</h6>
    <ul class="mb-0">
        @foreach(session('errors') as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Upload CSV File</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Select CSV File</label>
                        <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv,.txt" required>
                        <small class="text-muted">Maximum file size: 10MB</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Import Students
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-file-download me-2"></i>Template</h5>
            </div>
            <div class="card-body">
                <p>Download the CSV template to see the required format:</p>
                <a href="{{ route('admin.students.import.template') }}" class="btn btn-outline-info w-100">
                    <i class="fas fa-download me-2"></i>Download Template
                </a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>CSV Format</h5>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Required Columns:</strong></p>
                <ul class="small">
                    <li>MatricNumber</li>
                    <li>FirstName</li>
                    <li>MiddleName (optional)</li>
                    <li>LastName</li>
                </ul>
                <p class="mb-2"><strong>Optional Columns:</strong></p>
                <ul class="small">
                    <li>YearOfEntry</li>
                    <li>School</li>
                    <li>Department</li>
                    <li>Programme</li>
                    <li>Level</li>
                    <li>StateOfOrigin</li>
                    <li>LGA</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Sample CSV Format</h5>
    </div>
    <div class="card-body">
        <pre class="bg-light p-3">MatricNumber,FirstName,MiddleName,LastName,YearOfEntry,School,Department,Programme,Level,StateOfOrigin,LGA
ND/2024/001,John,Doe,Smith,2024,School of Computing,Computer Science,ND,1,Lagos,Ikeja
ND/2024/002,Jane,,Adebola,2024,School of Business,Business Administration,HND,2,Ogun,Abeokuta South</pre>
    </div>
</div>
@endsection