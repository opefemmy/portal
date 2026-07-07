@extends('layouts.app')

@section('title', 'Admission Settings')

@section('content')
<div class="page-header">
    <h4>Admission Settings</h4>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Fee Configuration</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('registrar.admission.updateSettings') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="admission_application_fee_amount" class="form-label">Application Form Fee (₦)</label>
                        <input type="number" class="form-control @error('admission_application_fee_amount') is-invalid @enderror"
                               id="admission_application_fee_amount" name="admission_application_fee_amount"
                               value="{{ old('admission_application_fee_amount', $admission_application_fee_amount ?? 5000) }}" min="0">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="admission_accept_fee_amount" class="form-label">Acceptance Fee (₦)</label>
                        <input type="number" class="form-control @error('admission_accept_fee_amount') is-invalid @enderror"
                               id="admission_accept_fee_amount" name="admission_accept_fee_amount"
                               value="{{ old('admission_accept_fee_amount', $admission_accept_fee_amount ?? 10000) }}" min="0">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="admission_school_fee_amount" class="form-label">School Fees (₦)</label>
                        <input type="number" class="form-control @error('admission_school_fee_amount') is-invalid @enderror"
                               id="admission_school_fee_amount" name="admission_school_fee_amount"
                               value="{{ old('admission_school_fee_amount', $admission_school_fee_amount ?? 50000) }}" min="0">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" class="form-check-input" id="admission_require_application_fee" name="admission_require_application_fee" value="1"
                                   {{ old('admission_require_application_fee', $admission_require_application_fee ?? false) == 'true' ? 'checked' : '' }}>
                            <label class="form-check-label" for="admission_require_application_fee">
                                Require Application Fee Before Form Submission
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <h5 class="mb-3">Form & Process Settings</h5>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="admission_form_open" name="admission_form_open" value="1"
                                   {{ old('admission_form_open', $admission_form_open ?? false) == 'true' ? 'checked' : '' }}>
                            <label class="form-check-label" for="admission_form_open">
                                Open Admission Form
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="admission_form_penalty" name="admission_form_penalty" value="1"
                                   {{ old('admission_form_penalty', $admission_form_penalty ?? false) == 'true' ? 'checked' : '' }}>
                            <label class="form-check-label" for="admission_form_penalty">
                                Enable Late Application Penalty
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="admission_form_penalty_amount" class="form-label">Penalty Amount (₦)</label>
                        <input type="number" class="form-control @error('admission_form_penalty_amount') is-invalid @endif"
                               id="admission_form_penalty_amount" name="admission_form_penalty_amount"
                               value="{{ old('admission_form_penalty_amount', $admission_form_penalty_amount ?? 0) }}" min="0">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-2"></i>Save All Settings
                </button>
                <a href="{{ route('registrar.admission') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Admission Letter</h5>
            </div>
            <div class="card-body">
                <p>Manage the admission letter template and generate letters for admitted students.</p>
                <a href="{{ route('registrar.admission.uploadTemplate') }}" class="btn btn-info">
                    <i class="fas fa-file-signature me-2"></i>Manage Letter Template
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>Bulk Upload</h5>
            </div>
            <div class="card-body">
                <p>Upload admission list in bulk by department.</p>
                <a href="{{ route('registrar.admission.uploadByDepartment') }}" class="btn btn-warning">
                    <i class="fas fa-upload me-2"></i>Upload Admission List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection