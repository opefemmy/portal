@extends('layouts.app')

@section('title', 'Upload Admission List')

@section('content')
<div class="page-header">
    <h4>Upload Admission List by Department</h4>
    <p class="text-muted">Upload a CSV/Excel file with admitted applicants for a specific department.</p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>Upload Admission List</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('registrar.admission.uploadByDepartment') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Select Department *</label>
                        <select name="department_id" id="department_id" class="form-select" required>
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->school->name ?? '' }} - {{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="file" class="form-label">Upload File (CSV/Excel) *</label>
                        <input type="file" name="file" id="file" class="form-control" accept=".csv,.xlsx,.xls" required>
                        <small class="text-muted">
                            Format: Application Number, Status (admitted/rejected)
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File Format Example:</label>
                        <div class="bg-light p-3 rounded">
                            <code>
                                application_number,status<br>
                                APP-ABC12345,admitted<br>
                                APP-ABC12346,admitted<br>
                                APP-ABC12347,rejected
                            </code>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload Admission List
                    </button>

                    <a href="{{ route('registrar.admission') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Instructions</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li class="mb-2">Select the department from the dropdown</li>
                    <li class="mb-2">Prepare your file in CSV or Excel format</li>
                    <li class="mb-2">Include two columns: Application Number and Status</li>
                    <li class="mb-2">Status should be "admitted" or "rejected"</li>
                    <li class="mb-2">Click Upload to process</li>
                </ol>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This will update existing records. Already admitted students will not be duplicated.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection