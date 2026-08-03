@extends('layouts.app')

@section('title', 'Upload Previous Results')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Upload Previous Results</h4>
    <a href="{{ route('admin.previous-results.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to list
    </a>
</div>

@if(session('import_summary'))
    @php $s = session('import_summary'); @endphp
    <div class="alert alert-{{ empty($s['errors']) ? 'success' : 'warning' }}">
        <strong>Imported {{ $s['imported'] }} row(s).</strong>
        @if(!empty($s['errors']))
            <div class="mt-2">
                <strong>{{ count($s['errors']) }} row(s) had errors:</strong>
                <ul class="mb-0 mt-1">
                    @foreach(array_slice($s['errors'], 0, 25) as $err)
                        <li>Row {{ $err['row'] }}: {{ $err['error'] }}</li>
                    @endforeach
                    @if(count($s['errors']) > 25)
                        <li><em>… and {{ count($s['errors']) - 25 }} more.</em></li>
                    @endif
                </ul>
            </div>
        @endif
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Upload CSV / XLSX</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.previous-results.upload') }}"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold">Result file</label>
                        <input type="file" name="file" accept=".csv,.xlsx,.xlsm,.txt"
                               class="form-control @error('file') is-invalid @enderror" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            Accepted formats: CSV or XLSX (max 5 MB).
                            <a href="{{ route('admin.previous-results.template') }}">Download the CSV template</a>.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>How it works</h6>
            </div>
            <div class="card-body">
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Download the CSV template.</li>
                    <li class="mb-2">Fill in one row per course score per student per session.</li>
                    <li class="mb-2"><strong>matric_number</strong> must match an existing student — orphan rows are skipped.</li>
                    <li class="mb-2">Total score is required. If you leave <em>grade</em> blank, the importer computes it from the existing grade rules.</li>
                    <li>Re-importing the same row overwrites the previous version.</li>
                </ol>
            </div>
        </div>

        <div class="card mt-3 border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="fas fa-columns me-2"></i>Required columns</h6>
            </div>
            <div class="card-body small">
                <code>matric_number</code>, <code>course_code</code>, <code>session_name</code>, <code>total_score</code>.
                Optional: <code>course_title</code>, <code>units</code>, <code>semester</code>, <code>level</code>,
                <code>ca</code>, <code>test</code>, <code>assignment</code>, <code>exam</code>,
                <code>grade</code>, <code>grade_point</code>, <code>remarks</code>, <code>source_institution</code>.
            </div>
        </div>
    </div>
</div>
@endsection