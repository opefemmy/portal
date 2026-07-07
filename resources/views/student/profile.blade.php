@extends('layouts.app')

@section('title', 'Complete Your Profile')

@section('content')
@php $user = auth()->user(); @endphp
<div class="page-header">
    <h4>Complete Your Profile</h4>
    <p class="text-muted">Please complete your profile to access all features.</p>
</div>

@if(session('info'))
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Passport Upload -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-camera me-2"></i>Passport Photo</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 text-center">
                @if($user->passport)
                    <img src="{{ asset('uploads/passports/' . $user->passport) }}" alt="Passport" class="img-thumbnail" style="max-width: 150px; border: 3px solid #1a237e;">
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center" style="width: 150px; height: 150px; margin: 0 auto;">
                        <i class="fas fa-user fa-3x text-muted"></i>
                    </div>
                @endif
            </div>
            <div class="col-md-9">
                <form method="POST" action="{{ route('student.profile.passport') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="passport" class="form-label">Upload Passport (JPEG, PNG, JPG - Max 2MB)</label>
                        <input type="file" name="passport" class="form-control" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload Passport
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Guidance Details -->
<div class="card mb-4 border-info">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-user-friends me-2"></i>Guidance Details (Required)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Please provide your parent/guardian or guidance contact information.</p>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="guidance_name" class="form-label">Guidance Name *</label>
                    <input type="text" class="form-control @error('guidance_name') is-invalid @endif"
                           id="guidance_name" name="guidance_name" value="{{ old('guidance_name', $user->guidance_name) }}" required>
                    @error('guidance_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="guidance_phone" class="form-label">Guidance Phone *</label>
                    <input type="text" class="form-control @error('guidance_phone') is-invalid @endif"
                           id="guidance_phone" name="guidance_phone" value="{{ old('guidance_phone', $user->guidance_phone) }}" required>
                    @error('guidance_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="guidance_address" class="form-label">Guidance Address</label>
                    <textarea class="form-control @error('guidance_address') is-invalid @endif"
                              id="guidance_address" name="guidance_address" rows="2">{{ old('guidance_address', $user->guidance_address) }}</textarea>
                    @error('guidance_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-school me-2"></i>Academic Details</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('student.profile.update') }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="matric_number" class="form-label">Matric Number</label>
                        <input type="text" class="form-control @error('matric_number') is-invalid @endif"
                               id="matric_number" name="matric_number" value="{{ old('matric_number', $student->matric_number) }}">
                        @error('matric_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="school_id" class="form-label">School *</label>
                        <select class="form-select @error('school_id') is-invalid @endif"
                                id="school_id" name="school_id" required>
                            <option value="">Select School</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" {{ $student->school_id == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                            @endforeach
                        </select>
                        @error('school_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department *</label>
                        <select class="form-select @error('department_id') is-invalid @endif"
                                id="department_id" name="department_id" required>
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $student->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="programme_id" class="form-label">Programme *</label>
                        <select class="form-select @error('programme_id') is-invalid @endif"
                                id="programme_id" name="programme_id" required>
                            <option value="">Select Programme</option>
                            @foreach($programmes as $prog)
                                <option value="{{ $prog->id }}" {{ $student->programme_id == $prog->id ? 'selected' : '' }}>{{ $prog->name }}</option>
                            @endforeach
                        </select>
                        @error('programme_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session *</label>
                        <select class="form-select @error('session_id') is-invalid @endif"
                                id="session_id" name="session_id" required>
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" {{ $student->session_id == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                            @endforeach
                        </select>
                        @error('session_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="level" class="form-label">Level *</label>
                        <select class="form-select @error('level') is-invalid @endif"
                                id="level" name="level" required>
                            <option value="">Select Level</option>
                            <option value="1" {{ $student->level == 1 ? 'selected' : '' }}>100L / ND1</option>
                            <option value="2" {{ $student->level == 2 ? 'selected' : '' }}>200L / ND</option>
                            <option value="3" {{ $student->level == 3 ? 'selected' : '' }}>300L / HND1</option>
                            <option value="4" {{ $student->level == 4 ? 'selected' : '' }}>400L / HND2</option>
                            <option value="5" {{ $student->level == 5 ? 'selected' : '' }}>500L</option>
                            <option value="6" {{ $student->level == 6 ? 'selected' : '' }}>600L</option>
                        </select>
                        @error('level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-2"></i>Save All Details
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const schoolSelect = document.getElementById('school_id');
    const departmentSelect = document.getElementById('department_id');
    const programmeSelect = document.getElementById('programme_id');

    // Store initial department and programme values
    const currentDepartmentId = {{ $student->department_id ?? 'null' }};
    const currentProgrammeId = {{ $student->programme_id ?? 'null' }};

    // When school changes, load departments
    schoolSelect.addEventListener('change', function() {
        const schoolId = this.value;
        departmentSelect.innerHTML = '<option value="">Select Department</option>';
        programmeSelect.innerHTML = '<option value="">Select Programme</option>';
        departmentSelect.disabled = true;
        programmeSelect.disabled = true;

        if (schoolId) {
            fetch(`/api/departments/${schoolId}`)
                .then(response => response.json())
                .then(data => {
                    departmentSelect.disabled = false;
                    data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.name;
                        if (dept.id === currentDepartmentId) {
                            option.selected = true;
                            // Load programmes for current department
                            loadProgrammes(dept.id);
                        }
                        departmentSelect.appendChild(option);
                    });
                });
        }
    });

    // When department changes, load programmes
    departmentSelect.addEventListener('change', function() {
        const departmentId = this.value;
        programmeSelect.innerHTML = '<option value="">Select Programme</option>';
        programmeSelect.disabled = true;

        if (departmentId) {
            loadProgrammes(departmentId);
        }
    });

    function loadProgrammes(departmentId) {
        fetch(`/api/programmes/${departmentId}`)
            .then(response => response.json())
            .then(data => {
                programmeSelect.disabled = false;
                data.forEach(prog => {
                    const option = document.createElement('option');
                    option.value = prog.id;
                    option.textContent = prog.name;
                    if (prog.id === currentProgrammeId) {
                        option.selected = true;
                    }
                    programmeSelect.appendChild(option);
                });
            });
    }

    // Trigger initial load if school is already selected
    if (schoolSelect.value) {
        schoolSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
@endsection