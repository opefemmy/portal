@extends('layouts.app')

@section('title', 'Admission Settings')

@section('content')
<div class="page-header">
    <h4>Admission Settings</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('registrar.admission.updateSettings') }}">
            @csrf

            <div class="mb-4">
                <h5 class="border-bottom pb-2">Admission Number Configuration</h5>
                <div class="mb-3">
                    <label for="admission_number_prefix" class="form-label">Admission Number Prefix</label>
                    <input type="text" class="form-control @error('admission_number_prefix') is-invalid @enderror"
                           id="admission_number_prefix" name="admission_number_prefix"
                           value="{{ old('admission_number_prefix', $admission_number_prefix) }}" maxlength="10">
                    <small class="text-muted">e.g., ADM, APP, etc.</small>
                </div>
            </div>

            <div class="mb-4">
                <h5 class="border-bottom pb-2">Admission Letter Template</h5>
                <div class="mb-3">
                    <label for="admission_letter_template" class="form-label">Letter Template</label>
                    <textarea class="form-control @error('admission_letter_template') is-invalid @enderror"
                              id="admission_letter_template" name="admission_letter_template" rows="10"
                              placeholder="Enter admission letter template with placeholders like {{ '{student_name}' }}, {{ '{department}' }}, {{ '{session}' }}">{{ old('admission_letter_template', $admission_letter_template) }}</textarea>
                    <small class="text-muted">Available placeholders: {student_name}, {email}, {department}, {programme}, {session}, {admission_number}</small>
                </div>
            </div>

            <div class="mb-4">
                <h5 class="border-bottom pb-2">Automation Settings</h5>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="auto_create_student" name="auto_create_student" value="1"
                           {{ old('auto_create_student', $auto_create_student) ? 'checked' : '' }}>
                    <label class="form-check-label" for="auto_create_student">
                        Auto-create student account when applicant is admitted
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
                <a href="{{ route('registrar.admission') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection