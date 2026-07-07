@extends('layouts.app')

@section('title', 'Upload Admission Letter Template')

@section('content')
<div class="page-header">
    <h4>Upload Admission Letter Template</h4>
    <p class="text-muted">Upload a PDF or Word template for admission letters.</p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-file-signature me-2"></i>Upload Template</h5>
            </div>
            <div class="card-body">
                @if($template)
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Current template: <strong>{{ $template }}</strong>
                </div>
                @endif

                <form method="POST" action="{{ route('registrar.admission.uploadTemplate') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="template" class="form-label">Select Template File (PDF, DOC, DOCX) *</label>
                        <input type="file" name="template" id="template" class="form-control" accept=".pdf,.doc,.docx" required>
                        <small class="text-muted">Maximum file size: 5MB</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Template Variables:</label>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-1">Use these placeholders in your template:</p>
                            <code>
                                {{ $student_name }} - Student's Full Name<br>
                                {{ $matric_number }} - Matric Number<br>
                                {{ $department }} - Department Name<br>
                                {{ $programme }} - Programme Name<br>
                                {{ $session }} - Academic Session<br>
                                {{ $level }} - Level<br>
                                {{ $admission_date }} - Date of Admission
                            </code>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload Template
                    </button>

                    <a href="{{ route('registrar.admission.settings') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Settings
                    </a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Template Guidelines</h5>
            </div>
            <div class="card-body">
                <ul>
                    <li class="mb-2">Use PDF for final letters</li>
                    <li class="mb-2">Use Word (DOC/DOCX) if you need to customize</li>
                    <li class="mb-2">Include institution letterhead</li>
                    <li class="mb-2">Add space for signatures</li>
                    <li class="mb-2">Include terms and conditions</li>
                </ul>
                <div class="alert alert-warning">
                    <i class="fas fa-lightbulb me-2"></i>
                    The system will generate individual letters for each admitted student.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection