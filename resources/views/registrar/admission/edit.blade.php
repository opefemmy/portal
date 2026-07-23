@extends('layouts.app')

@section('title', 'Edit Applicant')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Edit Applicant</h4>
    <a href="{{ route('registrar.admission') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to List
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('registrar.admission.update', $applicant->id) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Personal Information -->
                <div class="col-md-6">
                    <h5 class="mb-3">Personal Information</h5>

                    <div class="mb-3">
                        <label for="surname" class="form-label">Surname <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="surname" name="surname" value="{{ old('surname', $applicant->surname) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $applicant->first_name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="middle_name" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name', $applicant->middle_name) }}">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $applicant->email) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $applicant->phone) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" {{ old('gender', $applicant->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender', $applicant->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender', $applicant->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                </div>

                <!-- Programme Selection -->
                <div class="col-md-6">
                    <h5 class="mb-3">Programme Selection</h5>

                    <div class="mb-3">
                        <label for="school_id" class="form-label">School <span class="text-danger">*</span></label>
                        <select class="form-select" id="school_id" name="school_id" required>
                            <option value="">Select School</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" {{ old('school_id', $applicant->school_id) == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                        <select class="form-select" id="department_id" name="department_id" required>
                            <option value="">Select Department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id', $applicant->department_id) == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="programme_id" class="form-label">Programme <span class="text-danger">*</span></label>
                        <select class="form-select" id="programme_id" name="programme_id" required>
                            <option value="">Select Programme</option>
                            @foreach($programmes as $programme)
                                <option value="{{ $programme->id }}" {{ old('programme_id', $applicant->programme_id) == $programme->id ? 'selected' : '' }}>{{ $programme->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session <span class="text-danger">*</span></label>
                        <select class="form-select" id="session_id" name="session_id" required>
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" {{ old('session_id', $applicant->session_id) == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="centre_id" class="form-label">Preferred Centre <span class="text-danger">*</span></label>
                        <select class="form-select" id="centre_id" name="centre_id" required>
                            <option value="">Select Centre</option>
                            @foreach($centres as $centre)
                                <option value="{{ $centre->id }}" {{ old('centre_id', $applicant->centre_id) == $centre->id ? 'selected' : '' }}>{{ $centre->name }} ({{ $centre->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('registrar.admission') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Applicant
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('school_id').addEventListener('change', function() {
    const schoolId = this.value;
    const deptSelect = document.getElementById('department_id');
    const progSelect = document.getElementById('programme_id');

    // Reset options
    deptSelect.innerHTML = '<option value="">Select Department</option>';
    progSelect.innerHTML = '<option value="">Select Programme</option>';

    if (schoolId) {
        // Fetch departments
        fetch(`/api/departments/${schoolId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = dept.name;
                    deptSelect.appendChild(option);
                });
            });
    }
});

document.getElementById('department_id').addEventListener('change', function() {
    const departmentId = this.value;
    const progSelect = document.getElementById('programme_id');

    // Reset options
    progSelect.innerHTML = '<option value="">Select Programme</option>';

    if (departmentId) {
        // Fetch programmes
        fetch(`/api/programmes/${departmentId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(prog => {
                    const option = document.createElement('option');
                    option.value = prog.id;
                    option.textContent = prog.name;
                    progSelect.appendChild(option);
                });
            });
    }
});
</script>
@endpush
@endsection
