@extends('layouts.app')

@section('title', 'Apply Now')

@section('content')
<div class="page-header">
    <h4>Application Form</h4>
</div>

<form method="POST" action="{{ route('public.apply.submit') }}" enctype="multipart/form-data">
    @csrf

    {{-- Personal Information --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Personal Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Surname *</label>
                    <input type="text" name="surname" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Gender *</label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Place of Birth</label>
                    <input type="text" name="place_of_birth" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Religion</label>
                    <input type="text" name="religion" class="form-control" placeholder="e.g., Christianity, Islam">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">Select</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Genotype</label>
                    <select name="genotype" class="form-select">
                        <option value="">Select</option>
                        <option value="AA">AA</option>
                        <option value="AS">AS</option>
                        <option value="SS">SS</option>
                        <option value="AC">AC</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Disability</label>
                    <select name="disability" class="form-select">
                        <option value="none">None</option>
                        <option value="physical">Physical</option>
                        <option value="visual">Visual</option>
                        <option value="hearing">Hearing</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-12 mb-3" id="disability-details" style="display: none;">
                    <label class="form-label">Describe Disability</label>
                    <textarea name="disability_details" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Contact Information --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Contact Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Address *</label>
                    <textarea name="address" class="form-control" rows="2" required></textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">State *</label>
                    <select name="state_id" id="state_id" class="form-select" required>
                        <option value="">Select State</option>
                        @foreach($states as $state)
                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">LGA *</label>
                    <select name="lga_id" id="lga_id" class="form-select" required>
                        <option value="">Select LGA</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nationality *</label>
                    <select name="nationality_id" class="form-select" required>
                        <option value="">Select Nationality</option>
                        @foreach($nationalities as $nation)
                        <option value="{{ $nation->id }}">{{ $nation->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Programme Selection --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Programme Selection</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">School *</label>
                    <select name="school_id" id="school_id" class="form-select" required>
                        <option value="">Select School</option>
                        @foreach($schools as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department *</label>
                    <select name="department_id" id="department_id" class="form-select" required>
                        <option value="">Select Department</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Programme *</label>
                    <select name="programme_id" id="programme_id" class="form-select" required>
                        <option value="">Select Programme</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Session *</label>
                    <select name="session_id" class="form-select" required>
                        <option value="">Select Session</option>
                        @foreach($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- O-Level Results --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">O-Level Results (First Sitting)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel1_subject1" class="form-control" placeholder="Subject 1">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade1" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1">A1</option>
                        <option value="B2">B2</option>
                        <option value="B3">B3</option>
                        <option value="C4">C4</option>
                        <option value="C5">C5</option>
                        <option value="C6">C6</option>
                        <option value="D7">D7</option>
                        <option value="E8">E8</option>
                        <option value="F9">F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel1_subject2" class="form-control" placeholder="Subject 2">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade2" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1">A1</option>
                        <option value="B2">B2</option>
                        <option value="B3">B3</option>
                        <option value="C4">C4</option>
                        <option value="C5">C5</option>
                        <option value="C6">C6</option>
                        <option value="D7">D7</option>
                        <option value="E8">E8</option>
                        <option value="F9">F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel1_subject3" class="form-control" placeholder="Subject 3">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade3" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1">A1</option>
                        <option value="B2">B2</option>
                        <option value="B3">B3</option>
                        <option value="C4">C4</option>
                        <option value="C5">C5</option>
                        <option value="C6">C6</option>
                        <option value="D7">D7</option>
                        <option value="E8">E8</option>
                        <option value="F9">F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel1_subject4" class="form-control" placeholder="Subject 4">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade4" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1">A1</option>
                        <option value="B2">B2</option>
                        <option value="B3">B3</option>
                        <option value="C4">C4</option>
                        <option value="C5">C5</option>
                        <option value="C6">C6</option>
                        <option value="D7">D7</option>
                        <option value="E8">E8</option>
                        <option value="F9">F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel1_subject5" class="form-control" placeholder="Subject 5">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade5" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1">A1</option>
                        <option value="B2">B2</option>
                        <option value="B3">B3</option>
                        <option value="C4">C4</option>
                        <option value="C5">C5</option>
                        <option value="C6">C6</option>
                        <option value="D7">D7</option>
                        <option value="E8">E8</option>
                        <option value="F9">F9</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="number" name="olevel1_exam_year" class="form-control" placeholder="Exam Year" min="2000" max="2030">
                </div>
            </div>
        </div>
    </div>

    {{-- Extra Curricular Activities --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Extra Curricular Activities</h5>
        </div>
        <div class="card-body">
            <textarea name="extra_curricular" class="form-control" rows="3" placeholder="List any clubs, sports, competitions, etc."></textarea>
        </div>
    </div>

    {{-- Passport Upload --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Passport Photograph</h5>
        </div>
        <div class="card-body">
            <input type="file" name="passport" class="form-control" accept="image/*">
            <small class="text-muted">Upload a recent passport photograph (max 2MB)</small>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-paper-plane me-2"></i>Submit Application
        </button>
    </div>
</form>

@push('scripts')
<script>
    // Show disability details if "other" is selected
    document.querySelector('select[name="disability"]').addEventListener('change', function() {
        const details = document.getElementById('disability-details');
        if (this.value === 'other' || this.value === 'physical' || this.value === 'visual' || this.value === 'hearing') {
            details.style.display = 'block';
        } else {
            details.style.display = 'none';
        }
    });

    // Load LGA when state is selected
    document.getElementById('state_id').addEventListener('change', function() {
        const stateId = this.value;
        const lgaSelect = document.getElementById('lga_id');
        lgaSelect.innerHTML = '<option value="">Loading...</option>';

        fetch(`/applicant/lgas/${stateId}`)
            .then(response => response.json())
            .then(data => {
                lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                data.forEach(lga => {
                    const option = document.createElement('option');
                    option.value = lga.id;
                    option.textContent = lga.name;
                    lgaSelect.appendChild(option);
                });
            });
    });

    // Load departments when school is selected
    document.getElementById('school_id').addEventListener('change', function() {
        const schoolId = this.value;
        const deptSelect = document.getElementById('department_id');
        deptSelect.innerHTML = '<option value="">Loading...</option>';

        fetch(`/applicant/departments/${schoolId}`)
            .then(response => response.json())
            .then(data => {
                deptSelect.innerHTML = '<option value="">Select Department</option>';
                data.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = dept.name;
                    deptSelect.appendChild(option);
                });
            });
    });
</script>
@endpush
@endsection