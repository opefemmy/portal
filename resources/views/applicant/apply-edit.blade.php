@extends('layouts.app')

@section('title', 'Edit Application')

@section('content')
<div class="page-header">
    <h4>Edit Application</h4>
</div>

<form method="POST" action="{{ route('applicant.application.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- Personal Information --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Personal Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Surname *</label>
                    <input type="text" name="surname" class="form-control" value="{{ old('surname', $applicant->surname ?? '') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $applicant->first_name ?? '') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $applicant->middle_name ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" value="{{ old('phone', $applicant->phone ?? '') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Gender *</label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select Gender</option>
                        <option value="Male" {{ old('gender', $applicant->gender ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender', $applicant->gender ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ old('gender', $applicant->gender ?? '') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $applicant->date_of_birth ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Place of Birth</label>
                    <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth', $applicant->place_of_birth ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Religion</label>
                    <input type="text" name="religion" class="form-control" value="{{ old('religion', $applicant->religion ?? '') }}" placeholder="e.g., Christianity, Islam">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">Select</option>
                        <option value="A+" {{ old('blood_group', $applicant->blood_group ?? '') == 'A+' ? 'selected' : '' }}>A+</option>
                        <option value="A-" {{ old('blood_group', $applicant->blood_group ?? '') == 'A-' ? 'selected' : '' }}>A-</option>
                        <option value="B+" {{ old('blood_group', $applicant->blood_group ?? '') == 'B+' ? 'selected' : '' }}>B+</option>
                        <option value="B-" {{ old('blood_group', $applicant->blood_group ?? '') == 'B-' ? 'selected' : '' }}>B-</option>
                        <option value="AB+" {{ old('blood_group', $applicant->blood_group ?? '') == 'AB+' ? 'selected' : '' }}>AB+</option>
                        <option value="AB-" {{ old('blood_group', $applicant->blood_group ?? '') == 'AB-' ? 'selected' : '' }}>AB-</option>
                        <option value="O+" {{ old('blood_group', $applicant->blood_group ?? '') == 'O+' ? 'selected' : '' }}>O+</option>
                        <option value="O-" {{ old('blood_group', $applicant->blood_group ?? '') == 'O-' ? 'selected' : '' }}>O-</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Genotype</label>
                    <select name="genotype" class="form-select">
                        <option value="">Select</option>
                        <option value="AA" {{ old('genotype', $applicant->genotype ?? '') == 'AA' ? 'selected' : '' }}>AA</option>
                        <option value="AS" {{ old('genotype', $applicant->genotype ?? '') == 'AS' ? 'selected' : '' }}>AS</option>
                        <option value="SS" {{ old('genotype', $applicant->genotype ?? '') == 'SS' ? 'selected' : '' }}>SS</option>
                        <option value="AC" {{ old('genotype', $applicant->genotype ?? '') == 'AC' ? 'selected' : '' }}>AC</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Disability</label>
                    <select name="disability" class="form-select" id="disability">
                        <option value="none" {{ old('disability', $applicant->disability ?? '') == 'none' ? 'selected' : '' }}>None</option>
                        <option value="physical" {{ old('disability', $applicant->disability ?? '') == 'physical' ? 'selected' : '' }}>Physical</option>
                        <option value="visual" {{ old('disability', $applicant->disability ?? '') == 'visual' ? 'selected' : '' }}>Visual</option>
                        <option value="hearing" {{ old('disability', $applicant->disability ?? '') == 'hearing' ? 'selected' : '' }}>Hearing</option>
                        <option value="other" {{ old('disability', $applicant->disability ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-12 mb-3" id="disability-details" style="{{ in_array($applicant->disability ?? '', ['physical', 'visual', 'hearing', 'other']) ? 'display:block' : 'display:none' }}">
                    <label class="form-label">Describe Disability</label>
                    <textarea name="disability_details" class="form-control" rows="2">{{ old('disability_details', $applicant->disability_details ?? '') }}</textarea>
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
                    <textarea name="address" class="form-control" rows="2" required>{{ old('address', $applicant->address ?? '') }}</textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">State *</label>
                    <select name="state_id" id="state_id" class="form-select" required>
                        <option value="">Select State</option>
                        @foreach($states as $state)
                        <option value="{{ $state->id }}" {{ old('state_id', $applicant->state_id ?? '') == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">LGA *</label>
                    <select name="lga_id" id="lga_id" class="form-select" required>
                        <option value="">Select LGA</option>
                        @if($applicant->lga)
                        <option value="{{ $applicant->lga->id }}" selected>{{ $applicant->lga->name }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nationality *</label>
                    <select name="nationality_id" class="form-select" required>
                        <option value="">Select Nationality</option>
                        @foreach($nationalities as $nation)
                        <option value="{{ $nation->id }}" {{ old('nationality_id', $applicant->nationality_id ?? '') == $nation->id ? 'selected' : '' }}>{{ $nation->name }}</option>
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
                        <option value="{{ $school->id }}" {{ old('school_id', $applicant->school_id ?? '') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department *</label>
                    <select name="department_id" id="department_id" class="form-select" required>
                        <option value="">Select Department</option>
                        @if($applicant->department)
                        <option value="{{ $applicant->department->id }}" selected>{{ $applicant->department->name }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Programme *</label>
                    <select name="programme_id" id="programme_id" class="form-select" required>
                        <option value="">Select Programme</option>
                        @if($applicant->programme)
                        <option value="{{ $applicant->programme->id }}" selected>{{ $applicant->programme->name }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Session *</label>
                    <select name="session_id" class="form-select" required>
                        <option value="">Select Session</option>
                        @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ old('session_id', $applicant->session_id ?? '') == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
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
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Examination Type</label>
                    <select name="olevel1_exam_type" class="form-select">
                        <option value="">Select Exam Type</option>
                        <option value="WAEC" {{ old('olevel1_exam_type', $applicant->olevel1_exam_type ?? '') == 'WAEC' ? 'selected' : '' }}>WAEC</option>
                        <option value="NECO" {{ old('olevel1_exam_type', $applicant->olevel1_exam_type ?? '') == 'NECO' ? 'selected' : '' }}>NECO</option>
                        <option value="NABTEB" {{ old('olevel1_exam_type', $applicant->olevel1_exam_type ?? '') == 'NABTEB' ? 'selected' : '' }}>NABTEB</option>
                        <option value="GCE" {{ old('olevel1_exam_type', $applicant->olevel1_exam_type ?? '') == 'GCE' ? 'selected' : '' }}>GCE (A'Level)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Examination Number</label>
                    <input type="text" name="olevel1_exam_number" class="form-control" value="{{ old('olevel1_exam_number', $applicant->olevel1_exam_number ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exam Year</label>
                    <select name="olevel1_exam_year" class="form-select">
                        <option value="">Select Year</option>
                        @for($year = date('Y'); $year >= date('Y') - 20; $year--)
                        <option value="{{ $year }}" {{ old('olevel1_exam_year', $applicant->olevel1_exam_year ?? '') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel1_subject1" class="form-control" placeholder="Subject 1" value="{{ old('olevel1_subject1', $applicant->olevel1_subject1 ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade1" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'A1' ? 'selected' : '' }}>A1</option>
                        <option value="B2" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'B2' ? 'selected' : '' }}>B2</option>
                        <option value="B3" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'B3' ? 'selected' : '' }}>B3</option>
                        <option value="C4" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'C4' ? 'selected' : '' }}>C4</option>
                        <option value="C5" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'C5' ? 'selected' : '' }}>C5</option>
                        <option value="C6" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'C6' ? 'selected' : '' }}>C6</option>
                        <option value="D7" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'D7' ? 'selected' : '' }}>D7</option>
                        <option value="E8" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'E8' ? 'selected' : '' }}>E8</option>
                        <option value="F9" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'F9' ? 'selected' : '' }}>F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel1_subject2" class="form-control" placeholder="Subject 2" value="{{ old('olevel1_subject2', $applicant->olevel1_subject2 ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade2" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'A1' ? 'selected' : '' }}>A1</option>
                        <option value="B2" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'B2' ? 'selected' : '' }}>B2</option>
                        <option value="B3" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'B3' ? 'selected' : '' }}>B3</option>
                        <option value="C4" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'C4' ? 'selected' : '' }}>C4</option>
                        <option value="C5" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'C5' ? 'selected' : '' }}>C5</option>
                        <option value="C6" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'C6' ? 'selected' : '' }}>C6</option>
                        <option value="D7" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'D7' ? 'selected' : '' }}>D7</option>
                        <option value="E8" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'E8' ? 'selected' : '' }}>E8</option>
                        <option value="F9" {{ old('olevel1_grade1', $applicant->olevel1_grade1 ?? '') == 'F9' ? 'selected' : '' }}>F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel1_subject2" class="form-control" placeholder="Subject 2" value="{{ old('olevel1_subject2', $applicant->olevel1_subject2 ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade2" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'A1' ? 'selected' : '' }}>A1</option>
                        <option value="B2" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'B2' ? 'selected' : '' }}>B2</option>
                        <option value="B3" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'B3' ? 'selected' : '' }}>B3</option>
                        <option value="C4" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'C4' ? 'selected' : '' }}>C4</option>
                        <option value="C5" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'C5' ? 'selected' : '' }}>C5</option>
                        <option value="C6" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'C6' ? 'selected' : '' }}>C6</option>
                        <option value="D7" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'D7' ? 'selected' : '' }}>D7</option>
                        <option value="E8" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'E8' ? 'selected' : '' }}>E8</option>
                        <option value="F9" {{ old('olevel1_grade2', $applicant->olevel1_grade2 ?? '') == 'F9' ? 'selected' : '' }}>F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel1_subject3" class="form-control" placeholder="Subject 3" value="{{ old('olevel1_subject3', $applicant->olevel1_subject3 ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade3" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1" {{ old('olevel1_grade3', $applicant->olevel1_grade3 ?? '') == 'A1' ? 'selected' : '' }}>A1</option>
                        <option value="B2" {{ old('olevel1_grade3', $applicant->olevel1_grade3 ?? '') == 'B2' ? 'selected' : '' }}>B2</option>
                        <option value="B3" {{ old('olevel1_grade3', $applicant->olevel1_grade3 ?? '') == 'B3' ? 'selected' : '' }}>B3</option>
                        <option value="C4" {{ old('olevel1_grade3', $applicant->olevel1_grade3 ?? '') == 'C4' ? 'selected' : '' }}>C4</option>
                        <option value="C5" {{ old('olevel1_grade3', $applicant->olevel1_grade3 ?? '') == 'C5' ? 'selected' : '' }}>C5</option>
                        <option value="C6" {{ old('olevel1_grade3', $applicant->olevel1_grade3 ?? '') == 'C6' ? 'selected' : '' }}>C6</option>
                        <option value="D7" {{ old('olevel1_grade3', $applicant->olevel1_grade3 ?? '') == 'D7' ? 'selected' : '' }}>D7</option>
                        <option value="E8" {{ old('olevel1_grade3', $applicant->olevel1_grade3 ?? '') == 'E8' ? 'selected' : '' }}>E8</option>
                        <option value="F9" {{ old('olevel1_grade3', $applicant->olevel1_grade3 ?? '') == 'F9' ? 'selected' : '' }}>F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel1_subject4" class="form-control" placeholder="Subject 4" value="{{ old('olevel1_subject4', $applicant->olevel1_subject4 ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade4" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1" {{ old('olevel1_grade4', $applicant->olevel1_grade4 ?? '') == 'A1' ? 'selected' : '' }}>A1</option>
                        <option value="B2" {{ old('olevel1_grade4', $applicant->olevel1_grade4 ?? '') == 'B2' ? 'selected' : '' }}>B2</option>
                        <option value="B3" {{ old('olevel1_grade4', $applicant->olevel1_grade4 ?? '') == 'B3' ? 'selected' : '' }}>B3</option>
                        <option value="C4" {{ old('olevel1_grade4', $applicant->olevel1_grade4 ?? '') == 'C4' ? 'selected' : '' }}>C4</option>
                        <option value="C5" {{ old('olevel1_grade4', $applicant->olevel1_grade4 ?? '') == 'C5' ? 'selected' : '' }}>C5</option>
                        <option value="C6" {{ old('olevel1_grade4', $applicant->olevel1_grade4 ?? '') == 'C6' ? 'selected' : '' }}>C6</option>
                        <option value="D7" {{ old('olevel1_grade4', $applicant->olevel1_grade4 ?? '') == 'D7' ? 'selected' : '' }}>D7</option>
                        <option value="E8" {{ old('olevel1_grade4', $applicant->olevel1_grade4 ?? '') == 'E8' ? 'selected' : '' }}>E8</option>
                        <option value="F9" {{ old('olevel1_grade4', $applicant->olevel1_grade4 ?? '') == 'F9' ? 'selected' : '' }}>F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel1_subject5" class="form-control" placeholder="Subject 5" value="{{ old('olevel1_subject5', $applicant->olevel1_subject5 ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade5" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1" {{ old('olevel1_grade5', $applicant->olevel1_grade5 ?? '') == 'A1' ? 'selected' : '' }}>A1</option>
                        <option value="B2" {{ old('olevel1_grade5', $applicant->olevel1_grade5 ?? '') == 'B2' ? 'selected' : '' }}>B2</option>
                        <option value="B3" {{ old('olevel1_grade5', $applicant->olevel1_grade5 ?? '') == 'B3' ? 'selected' : '' }}>B3</option>
                        <option value="C4" {{ old('olevel1_grade5', $applicant->olevel1_grade5 ?? '') == 'C4' ? 'selected' : '' }}>C4</option>
                        <option value="C5" {{ old('olevel1_grade5', $applicant->olevel1_grade5 ?? '') == 'C5' ? 'selected' : '' }}>C5</option>
                        <option value="C6" {{ old('olevel1_grade5', $applicant->olevel1_grade5 ?? '') == 'C6' ? 'selected' : '' }}>C6</option>
                        <option value="D7" {{ old('olevel1_grade5', $applicant->olevel1_grade5 ?? '') == 'D7' ? 'selected' : '' }}>D7</option>
                        <option value="E8" {{ old('olevel1_grade5', $applicant->olevel1_grade5 ?? '') == 'E8' ? 'selected' : '' }}>E8</option>
                        <option value="F9" {{ old('olevel1_grade5', $applicant->olevel1_grade5 ?? '') == 'F9' ? 'selected' : '' }}>F9</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- O-Level Results (Second Sitting) --}}
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">O-Level Results (Second Sitting) - Optional</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Examination Type</label>
                    <select name="olevel2_exam_type" class="form-select">
                        <option value="">Select Exam Type</option>
                        <option value="WAEC" {{ old('olevel2_exam_type', $applicant->olevel2_exam_type ?? '') == 'WAEC' ? 'selected' : '' }}>WAEC</option>
                        <option value="NECO" {{ old('olevel2_exam_type', $applicant->olevel2_exam_type ?? '') == 'NECO' ? 'selected' : '' }}>NECO</option>
                        <option value="NABTEB" {{ old('olevel2_exam_type', $applicant->olevel2_exam_type ?? '') == 'NABTEB' ? 'selected' : '' }}>NABTEB</option>
                        <option value="GCE" {{ old('olevel2_exam_type', $applicant->olevel2_exam_type ?? '') == 'GCE' ? 'selected' : '' }}>GCE (A'Level)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Examination Number</label>
                    <input type="text" name="olevel2_exam_number" class="form-control" value="{{ old('olevel2_exam_number', $applicant->olevel2_exam_number ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exam Year</label>
                    <select name="olevel2_exam_year" class="form-select">
                        <option value="">Select Year</option>
                        @for($year = date('Y'); $year >= date('Y') - 20; $year--)
                        <option value="{{ $year }}" {{ old('olevel2_exam_year', $applicant->olevel2_exam_year ?? '') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel2_subject1" class="form-control" placeholder="Subject 1" value="{{ old('olevel2_subject1', $applicant->olevel2_subject1 ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel2_grade1" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1" {{ old('olevel2_grade1', $applicant->olevel2_grade1 ?? '') == 'A1' ? 'selected' : '' }}>A1</option>
                        <option value="B2" {{ old('olevel2_grade1', $applicant->olevel2_grade1 ?? '') == 'B2' ? 'selected' : '' }}>B2</option>
                        <option value="B3" {{ old('olevel2_grade1', $applicant->olevel2_grade1 ?? '') == 'B3' ? 'selected' : '' }}>B3</option>
                        <option value="C4" {{ old('olevel2_grade1', $applicant->olevel2_grade1 ?? '') == 'C4' ? 'selected' : '' }}>C4</option>
                        <option value="C5" {{ old('olevel2_grade1', $applicant->olevel2_grade1 ?? '') == 'C5' ? 'selected' : '' }}>C5</option>
                        <option value="C6" {{ old('olevel2_grade1', $applicant->olevel2_grade1 ?? '') == 'C6' ? 'selected' : '' }}>C6</option>
                        <option value="D7" {{ old('olevel2_grade1', $applicant->olevel2_grade1 ?? '') == 'D7' ? 'selected' : '' }}>D7</option>
                        <option value="E8" {{ old('olevel2_grade1', $applicant->olevel2_grade1 ?? '') == 'E8' ? 'selected' : '' }}>E8</option>
                        <option value="F9" {{ old('olevel2_grade1', $applicant->olevel2_grade1 ?? '') == 'F9' ? 'selected' : '' }}>F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel2_subject2" class="form-control" placeholder="Subject 2" value="{{ old('olevel2_subject2', $applicant->olevel2_subject2 ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel2_grade2" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1" {{ old('olevel2_grade2', $applicant->olevel2_grade2 ?? '') == 'A1' ? 'selected' : '' }}>A1</option>
                        <option value="B2" {{ old('olevel2_grade2', $applicant->olevel2_grade2 ?? '') == 'B2' ? 'selected' : '' }}>B2</option>
                        <option value="B3" {{ old('olevel2_grade2', $applicant->olevel2_grade2 ?? '') == 'B3' ? 'selected' : '' }}>B3</option>
                        <option value="C4" {{ old('olevel2_grade2', $applicant->olevel2_grade2 ?? '') == 'C4' ? 'selected' : '' }}>C4</option>
                        <option value="C5" {{ old('olevel2_grade2', $applicant->olevel2_grade2 ?? '') == 'C5' ? 'selected' : '' }}>C5</option>
                        <option value="C6" {{ old('olevel2_grade2', $applicant->olevel2_grade2 ?? '') == 'C6' ? 'selected' : '' }}>C6</option>
                        <option value="D7" {{ old('olevel2_grade2', $applicant->olevel2_grade2 ?? '') == 'D7' ? 'selected' : '' }}>D7</option>
                        <option value="E8" {{ old('olevel2_grade2', $applicant->olevel2_grade2 ?? '') == 'E8' ? 'selected' : '' }}>E8</option>
                        <option value="F9" {{ old('olevel2_grade2', $applicant->olevel2_grade2 ?? '') == 'F9' ? 'selected' : '' }}>F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel2_subject3" class="form-control" placeholder="Subject 3" value="{{ old('olevel2_subject3', $applicant->olevel2_subject3 ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel2_grade3" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1" {{ old('olevel2_grade3', $applicant->olevel2_grade3 ?? '') == 'A1' ? 'selected' : '' }}>A1</option>
                        <option value="B2" {{ old('olevel2_grade3', $applicant->olevel2_grade3 ?? '') == 'B2' ? 'selected' : '' }}>B2</option>
                        <option value="B3" {{ old('olevel2_grade3', $applicant->olevel2_grade3 ?? '') == 'B3' ? 'selected' : '' }}>B3</option>
                        <option value="C4" {{ old('olevel2_grade3', $applicant->olevel2_grade3 ?? '') == 'C4' ? 'selected' : '' }}>C4</option>
                        <option value="C5" {{ old('olevel2_grade3', $applicant->olevel2_grade3 ?? '') == 'C5' ? 'selected' : '' }}>C5</option>
                        <option value="C6" {{ old('olevel2_grade3', $applicant->olevel2_grade3 ?? '') == 'C6' ? 'selected' : '' }}>C6</option>
                        <option value="D7" {{ old('olevel2_grade3', $applicant->olevel2_grade3 ?? '') == 'D7' ? 'selected' : '' }}>D7</option>
                        <option value="E8" {{ old('olevel2_grade3', $applicant->olevel2_grade3 ?? '') == 'E8' ? 'selected' : '' }}>E8</option>
                        <option value="F9" {{ old('olevel2_grade3', $applicant->olevel2_grade3 ?? '') == 'F9' ? 'selected' : '' }}>F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel2_subject4" class="form-control" placeholder="Subject 4" value="{{ old('olevel2_subject4', $applicant->olevel2_subject4 ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel2_grade4" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1" {{ old('olevel2_grade4', $applicant->olevel2_grade4 ?? '') == 'A1' ? 'selected' : '' }}>A1</option>
                        <option value="B2" {{ old('olevel2_grade4', $applicant->olevel2_grade4 ?? '') == 'B2' ? 'selected' : '' }}>B2</option>
                        <option value="B3" {{ old('olevel2_grade4', $applicant->olevel2_grade4 ?? '') == 'B3' ? 'selected' : '' }}>B3</option>
                        <option value="C4" {{ old('olevel2_grade4', $applicant->olevel2_grade4 ?? '') == 'C4' ? 'selected' : '' }}>C4</option>
                        <option value="C5" {{ old('olevel2_grade4', $applicant->olevel2_grade4 ?? '') == 'C5' ? 'selected' : '' }}>C5</option>
                        <option value="C6" {{ old('olevel2_grade4', $applicant->olevel2_grade4 ?? '') == 'C6' ? 'selected' : '' }}>C6</option>
                        <option value="D7" {{ old('olevel2_grade4', $applicant->olevel2_grade4 ?? '') == 'D7' ? 'selected' : '' }}>D7</option>
                        <option value="E8" {{ old('olevel2_grade4', $applicant->olevel2_grade4 ?? '') == 'E8' ? 'selected' : '' }}>E8</option>
                        <option value="F9" {{ old('olevel2_grade4', $applicant->olevel2_grade4 ?? '') == 'F9' ? 'selected' : '' }}>F9</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="olevel2_subject5" class="form-control" placeholder="Subject 5" value="{{ old('olevel2_subject5', $applicant->olevel2_subject5 ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel2_grade5" class="form-select">
                        <option value="">Grade</option>
                        <option value="A1" {{ old('olevel2_grade5', $applicant->olevel2_grade5 ?? '') == 'A1' ? 'selected' : '' }}>A1</option>
                        <option value="B2" {{ old('olevel2_grade5', $applicant->olevel2_grade5 ?? '') == 'B2' ? 'selected' : '' }}>B2</option>
                        <option value="B3" {{ old('olevel2_grade5', $applicant->olevel2_grade5 ?? '') == 'B3' ? 'selected' : '' }}>B3</option>
                        <option value="C4" {{ old('olevel2_grade5', $applicant->olevel2_grade5 ?? '') == 'C4' ? 'selected' : '' }}>C4</option>
                        <option value="C5" {{ old('olevel2_grade5', $applicant->olevel2_grade5 ?? '') == 'C5' ? 'selected' : '' }}>C5</option>
                        <option value="C6" {{ old('olevel2_grade5', $applicant->olevel2_grade5 ?? '') == 'C6' ? 'selected' : '' }}>C6</option>
                        <option value="D7" {{ old('olevel2_grade5', $applicant->olevel2_grade5 ?? '') == 'D7' ? 'selected' : '' }}>D7</option>
                        <option value="E8" {{ old('olevel2_grade5', $applicant->olevel2_grade5 ?? '') == 'E8' ? 'selected' : '' }}>E8</option>
                        <option value="F9" {{ old('olevel2_grade5', $applicant->olevel2_grade5 ?? '') == 'F9' ? 'selected' : '' }}>F9</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="number" name="olevel2_exam_year" class="form-control" placeholder="Exam Year" min="2000" max="2030" value="{{ old('olevel2_exam_year', $applicant->olevel2_exam_year ?? '') }}">
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
            <textarea name="extra_curricular" class="form-control" rows="3" placeholder="List any clubs, sports, competitions, etc.">{{ old('extra_curricular', $applicant->extra_curricular ?? '') }}</textarea>
        </div>
    </div>

    {{-- Guardian Information --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Guardian / Parent Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Guardian Name</label>
                    <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $applicant->guardian_name ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Relationship</label>
                    <select name="guardian_relationship" class="form-select">
                        <option value="">Select Relationship</option>
                        <option value="Father" {{ old('guardian_relationship', $applicant->guardian_relationship ?? '') == 'Father' ? 'selected' : '' }}>Father</option>
                        <option value="Mother" {{ old('guardian_relationship', $applicant->guardian_relationship ?? '') == 'Mother' ? 'selected' : '' }}>Mother</option>
                        <option value="Guardian" {{ old('guardian_relationship', $applicant->guardian_relationship ?? '') == 'Guardian' ? 'selected' : '' }}>Guardian</option>
                        <option value="Uncle" {{ old('guardian_relationship', $applicant->guardian_relationship ?? '') == 'Uncle' ? 'selected' : '' }}>Uncle</option>
                        <option value="Aunt" {{ old('guardian_relationship', $applicant->guardian_relationship ?? '') == 'Aunt' ? 'selected' : '' }}>Aunt</option>
                        <option value="Brother" {{ old('guardian_relationship', $applicant->guardian_relationship ?? '') == 'Brother' ? 'selected' : '' }}>Brother</option>
                        <option value="Sister" {{ old('guardian_relationship', $applicant->guardian_relationship ?? '') == 'Sister' ? 'selected' : '' }}>Sister</option>
                        <option value="Other" {{ old('guardian_relationship', $applicant->guardian_relationship ?? '') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Guardian Phone</label>
                    <input type="tel" name="guardian_phone" class="form-control" value="{{ old('guardian_phone', $applicant->guardian_phone ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Guardian Email</label>
                    <input type="email" name="guardian_email" class="form-control" value="{{ old('guardian_email', $applicant->guardian_email ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="guardian_occupation" class="form-control" value="{{ old('guardian_occupation', $applicant->guardian_occupation ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Guardian Address</label>
                    <textarea name="guardian_address" class="form-control" rows="1">{{ old('guardian_address', $applicant->guardian_address ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Passport Upload --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Passport Photograph</h5>
        </div>
        <div class="card-body">
            @if($applicant->passport)
            <div class="mb-2">
                <img src="{{ asset('storage/passports/' . $applicant->passport) }}" alt="Passport" style="max-width: 150px;">
                <p class="text-muted small">Current passport photo</p>
            </div>
            @endif
            <input type="file" name="passport" class="form-control" accept="image/*">
            <small class="text-muted">Upload a recent passport photograph (max 2MB)</small>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-success btn-lg">
            <i class="fas fa-save me-2"></i>Update Application
        </button>
        <a href="{{ route('applicant.application') }}" class="btn btn-secondary btn-lg">
            Cancel
        </a>
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
