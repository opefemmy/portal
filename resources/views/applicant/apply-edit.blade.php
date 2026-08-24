@extends('layouts.app')

@section('title', 'Edit Application')

@section('content')
<div class="page-header">
    <h4>Edit Application</h4>
</div>

{{-- Validation error summary. The update endpoint runs the same
     Validator() pipeline as the create endpoint — Laravel redirects
     back here with $errors populated and `old()` holding the user's
     input. Inline feedback below each input is rendered via @error()
     so the user can see exactly which field needs attention. --}}
@if ($errors->any())
    <div class="alert alert-danger mb-4" role="alert">
        <h6 class="alert-heading mb-2"><i class="fas fa-exclamation-circle me-2"></i>Please correct the following before saving:</h6>
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('applicant.application.update') }}" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')

    {{-- Personal Information --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Personal Information</h5>
        </div>
        <div class="card-body">
            @php
                // Same fallback as apply.blade.php: legacy Applicants
                // created before the signup-time name split have NULL
                // surname/first_name/middle_name. users.name was always
                // populated at signup (concatenated from the three parts
                // for new signups, or whatever the user typed for legacy
                // signups), so parse it to fill the three slots.
                $userName = trim((string) (auth()->user()->name ?? ''));
                $fallbackSurname = $applicant->surname ?? '';
                $fallbackFirst = $applicant->first_name ?? '';
                $fallbackMiddle = $applicant->middle_name ?? '';
                if ($userName !== '' && ($fallbackSurname === '' || $fallbackFirst === '')) {
                    $parts = preg_split('/\s+/', $userName, 3);
                    if (count($parts) >= 1 && $fallbackSurname === '') {
                        $fallbackSurname = $parts[0];
                    }
                    if (count($parts) >= 2 && $fallbackFirst === '') {
                        $fallbackFirst = $parts[1];
                    }
                    if (count($parts) >= 3 && $fallbackMiddle === '') {
                        $fallbackMiddle = $parts[2];
                    }
                }
            @endphp
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Surname *</label>
                    <input type="text" name="surname" class="form-control" value="{{ old('surname', $fallbackSurname) }}" readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $fallbackFirst) }}" readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $fallbackMiddle) }}" readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $applicant->phone ?? '') }}" required>
                    @error('phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Gender *</label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                        <option value="">Select Gender</option>
                        <option value="Male" {{ old('gender', $applicant->gender ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender', $applicant->gender ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ old('gender', $applicant->gender ?? '') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('gender') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $applicant->date_of_birth ?? '') }}">
                    @error('date_of_birth') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Place of Birth</label>
                    <input type="text" name="place_of_birth" class="form-control @error('place_of_birth') is-invalid @enderror" value="{{ old('place_of_birth', $applicant->place_of_birth ?? '') }}">
                    @error('place_of_birth') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Religion</label>
                    <input type="text" name="religion" class="form-control @error('religion') is-invalid @enderror" value="{{ old('religion', $applicant->religion ?? '') }}" placeholder="e.g., Christianity, Islam">
                    @error('religion') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
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
                    @error('blood_group') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Genotype</label>
                    <select name="genotype" class="form-select @error('genotype') is-invalid @enderror">
                        <option value="">Select</option>
                        <option value="AA" {{ old('genotype', $applicant->genotype ?? '') == 'AA' ? 'selected' : '' }}>AA</option>
                        <option value="AS" {{ old('genotype', $applicant->genotype ?? '') == 'AS' ? 'selected' : '' }}>AS</option>
                        <option value="SS" {{ old('genotype', $applicant->genotype ?? '') == 'SS' ? 'selected' : '' }}>SS</option>
                        <option value="AC" {{ old('genotype', $applicant->genotype ?? '') == 'AC' ? 'selected' : '' }}>AC</option>
                    </select>
                    @error('genotype') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Disability</label>
                    @php
                        $disabilityForReveal = old('disability', $applicant->disability ?? 'none');
                    @endphp
                    <select name="disability" class="form-select @error('disability') is-invalid @enderror" id="disability">
                        <option value="none" {{ $disabilityForReveal == 'none' ? 'selected' : '' }}>None</option>
                        <option value="physical" {{ $disabilityForReveal == 'physical' ? 'selected' : '' }}>Physical</option>
                        <option value="visual" {{ $disabilityForReveal == 'visual' ? 'selected' : '' }}>Visual</option>
                        <option value="hearing" {{ $disabilityForReveal == 'hearing' ? 'selected' : '' }}>Hearing</option>
                        <option value="other" {{ $disabilityForReveal == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('disability') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-12 mb-3" id="disability-details" style="{{ in_array($disabilityForReveal, ['physical', 'visual', 'hearing', 'other'], true) ? 'display:block' : 'display:none' }}">
                    <label class="form-label">Describe Disability</label>
                    <textarea name="disability_details" class="form-control @error('disability_details') is-invalid @enderror" rows="2">{{ old('disability_details', $applicant->disability_details ?? '') }}</textarea>
                    @error('disability_details') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address', $applicant->address ?? '') }}</textarea>
                    @error('address') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                @php
                    // Same rehydration pattern as apply.blade.php — after
                    // a failed postback we re-render the LGA list inline
                    // so the previously-selected LGA is still in the
                    // dropdown before the JS change handler fires.
                    $oldStateIdE = old('state_id', $applicant->state_id ?? null);
                    $oldLgaIdE = old('lga_id', $applicant->lga_id ?? null);
                    $preselectedLgasE = ($oldStateIdE && \Schema::hasTable('local_governments'))
                        ? \App\Models\LocalGovernment::where('state_id', $oldStateIdE)->orderBy('name')->get()
                        : collect();
                @endphp
                <div class="col-md-4 mb-3">
                    <label class="form-label">State *</label>
                    <select name="state_id" id="state_id" class="form-select @error('state_id') is-invalid @enderror" required>
                        <option value="">Select State</option>
                        @foreach($states as $state)
                        <option value="{{ $state->id }}" {{ (string) $oldStateIdE === (string) $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                        @endforeach
                    </select>
                    @error('state_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">LGA *</label>
                    <select name="lga_id" id="lga_id" class="form-select @error('lga_id') is-invalid @enderror" required>
                        <option value="">{{ $preselectedLgasE->isNotEmpty() ? 'Select LGA' : 'Select State First' }}</option>
                        @foreach($preselectedLgasE as $lga)
                            <option value="{{ $lga->id }}" {{ (string) $oldLgaIdE === (string) $lga->id ? 'selected' : '' }}>{{ $lga->name }}</option>
                        @endforeach
                    </select>
                    @error('lga_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nationality *</label>
                    <select name="nationality_id" class="form-select @error('nationality_id') is-invalid @enderror" required>
                        <option value="">Select Nationality</option>
                        @foreach($nationalities as $nation)
                        <option value="{{ $nation->id }}" {{ (string) old('nationality_id', $applicant->nationality_id ?? '') === (string) $nation->id ? 'selected' : '' }}>{{ $nation->name }}</option>
                        @endforeach
                    </select>
                    @error('nationality_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
                    @php
                        $oldSchoolIdE = old('school_id', $applicant->school_id ?? null);
                        $oldDeptIdE = old('department_id', $applicant->department_id ?? null);
                        $preselectedDeptsE = ($oldSchoolIdE && \Schema::hasTable('departments'))
                            ? \App\Models\Department::where('school_id', $oldSchoolIdE)->orderBy('name')->get()
                            : collect();
                        $preselectedProgsE = ($oldDeptIdE && \Schema::hasTable('programmes'))
                            ? \App\Models\Programme::where('department_id', $oldDeptIdE)->orderBy('name')->get()
                            : collect();
                    @endphp
                    <select name="school_id" id="school_id" class="form-select @error('school_id') is-invalid @enderror" required>
                        <option value="">Select School</option>
                        @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ (string) $oldSchoolIdE === (string) $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                    @error('school_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department *</label>
                    <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                        <option value="">{{ $preselectedDeptsE->isNotEmpty() ? 'Select Department' : 'Select School First' }}</option>
                        @foreach($preselectedDeptsE as $dept)
                            <option value="{{ $dept->id }}" {{ (string) $oldDeptIdE === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Programme *</label>
                    <select name="programme_id" id="programme_id" class="form-select @error('programme_id') is-invalid @enderror" required>
                        <option value="">{{ $preselectedProgsE->isNotEmpty() ? 'Select Programme' : 'Select Department First' }}</option>
                        @foreach($preselectedProgsE as $prog)
                            <option value="{{ $prog->id }}" {{ (string) old('programme_id', $applicant->programme_id ?? '') === (string) $prog->id ? 'selected' : '' }}>{{ $prog->name }}</option>
                        @endforeach
                    </select>
                    @error('programme_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Session *</label>
                    <select name="session_id" class="form-select @error('session_id') is-invalid @enderror" required>
                        <option value="">Select Session</option>
                        @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string) old('session_id', $applicant->session_id ?? '') === (string) $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                        @endforeach
                    </select>
                    @error('session_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Preferred Centre for Lectures *</label>
                    <select name="centre_id" class="form-select @error('centre_id') is-invalid @enderror" required>
                        <option value="">Select Centre</option>
                        @foreach($centres as $centre)
                        <option value="{{ $centre->id }}" {{ (string) old('centre_id', $applicant->centre_id ?? '') === (string) $centre->id ? 'selected' : '' }}>{{ $centre->name }} ({{ $centre->code }})</option>
                        @endforeach
                    </select>
                    @error('centre_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <small class="text-muted">Select where you would like to attend lectures</small>
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
                    <select name="olevel1_exam_type" class="form-select @error('olevel1_exam_type') is-invalid @enderror">
                        <option value="">Select Exam Type</option>
                        <option value="WAEC" {{ old('olevel1_exam_type', $applicant->olevel1_exam_type ?? '') == 'WAEC' ? 'selected' : '' }}>WAEC</option>
                        <option value="NECO" {{ old('olevel1_exam_type', $applicant->olevel1_exam_type ?? '') == 'NECO' ? 'selected' : '' }}>NECO</option>
                        <option value="NABTEB" {{ old('olevel1_exam_type', $applicant->olevel1_exam_type ?? '') == 'NABTEB' ? 'selected' : '' }}>NABTEB</option>
                        <option value="GCE" {{ old('olevel1_exam_type', $applicant->olevel1_exam_type ?? '') == 'GCE' ? 'selected' : '' }}>GCE (A'Level)</option>
                    </select>
                    @error('olevel1_exam_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Examination Number</label>
                    <input type="text" name="olevel1_exam_number" class="form-control @error('olevel1_exam_number') is-invalid @enderror" value="{{ old('olevel1_exam_number', $applicant->olevel1_exam_number ?? '') }}">
                    @error('olevel1_exam_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exam Year</label>
                    <select name="olevel1_exam_year" class="form-select @error('olevel1_exam_year') is-invalid @enderror">
                        <option value="">Select Year</option>
                        @for($year = date('Y'); $year >= date('Y') - 20; $year--)
                        <option value="{{ $year }}" {{ (string) old('olevel1_exam_year', $applicant->olevel1_exam_year ?? '') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                    @error('olevel1_exam_year') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-2">
                    @include('applicant.partials.olevel-subject-select', [
                        'position' => 1,
                        'name' => 'olevel1_subject1',
                        'subjects' => $olevelSubjects,
                        'lockedValue' => $olevelCompulsory[1] ?? null,
                        'value' => old('olevel1_subject1', $applicant->olevel1_subject1 ?? null),
                    ])
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade1" class="form-select @error('olevel1_grade1') is-invalid @enderror">
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
                    @error('olevel1_grade1') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-2">
                    @include('applicant.partials.olevel-subject-select', [
                        'position' => 2,
                        'name' => 'olevel1_subject2',
                        'subjects' => $olevelSubjects,
                        'lockedValue' => $olevelCompulsory[2] ?? null,
                        'value' => old('olevel1_subject2', $applicant->olevel1_subject2 ?? null),
                    ])
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade2" class="form-select @error('olevel1_grade2') is-invalid @enderror">
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
                    @error('olevel1_grade2') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-2">
                    @include('applicant.partials.olevel-subject-select', [
                        'position' => 3,
                        'name' => 'olevel1_subject3',
                        'subjects' => $olevelSubjects,
                        'lockedValue' => null,
                        'value' => old('olevel1_subject3', $applicant->olevel1_subject3 ?? null),
                    ])
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade3" class="form-select @error('olevel1_grade3') is-invalid @enderror">
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
                    @error('olevel1_grade3') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-2">
                    @include('applicant.partials.olevel-subject-select', [
                        'position' => 4,
                        'name' => 'olevel1_subject4',
                        'subjects' => $olevelSubjects,
                        'lockedValue' => null,
                        'value' => old('olevel1_subject4', $applicant->olevel1_subject4 ?? null),
                    ])
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade4" class="form-select @error('olevel1_grade4') is-invalid @enderror">
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
                    @error('olevel1_grade4') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-2">
                    @include('applicant.partials.olevel-subject-select', [
                        'position' => 5,
                        'name' => 'olevel1_subject5',
                        'subjects' => $olevelSubjects,
                        'lockedValue' => null,
                        'value' => old('olevel1_subject5', $applicant->olevel1_subject5 ?? null),
                    ])
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel1_grade5" class="form-select @error('olevel1_grade5') is-invalid @enderror">
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
                    @error('olevel1_grade5') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
                    <select name="olevel2_exam_type" class="form-select @error('olevel2_exam_type') is-invalid @enderror">
                        <option value="">Select Exam Type</option>
                        <option value="WAEC" {{ old('olevel2_exam_type', $applicant->olevel2_exam_type ?? '') == 'WAEC' ? 'selected' : '' }}>WAEC</option>
                        <option value="NECO" {{ old('olevel2_exam_type', $applicant->olevel2_exam_type ?? '') == 'NECO' ? 'selected' : '' }}>NECO</option>
                        <option value="NABTEB" {{ old('olevel2_exam_type', $applicant->olevel2_exam_type ?? '') == 'NABTEB' ? 'selected' : '' }}>NABTEB</option>
                        <option value="GCE" {{ old('olevel2_exam_type', $applicant->olevel2_exam_type ?? '') == 'GCE' ? 'selected' : '' }}>GCE (A'Level)</option>
                    </select>
                    @error('olevel2_exam_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Examination Number</label>
                    <input type="text" name="olevel2_exam_number" class="form-control @error('olevel2_exam_number') is-invalid @enderror" value="{{ old('olevel2_exam_number', $applicant->olevel2_exam_number ?? '') }}">
                    @error('olevel2_exam_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exam Year</label>
                    <select name="olevel2_exam_year" class="form-select @error('olevel2_exam_year') is-invalid @enderror">
                        <option value="">Select Year</option>
                        @for($year = date('Y'); $year >= date('Y') - 20; $year--)
                        <option value="{{ $year }}" {{ (string) old('olevel2_exam_year', $applicant->olevel2_exam_year ?? '') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                    @error('olevel2_exam_year') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-2">
                    @include('applicant.partials.olevel-subject-select', [
                        'position' => 1,
                        'name' => 'olevel2_subject1',
                        'subjects' => $olevelSubjects,
                        'lockedValue' => $olevelCompulsory[1] ?? null,
                        'value' => old('olevel2_subject1', $applicant->olevel2_subject1 ?? null),
                    ])
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel2_grade1" class="form-select @error('olevel2_grade1') is-invalid @enderror">
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
                    @error('olevel2_grade1') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-2">
                    @include('applicant.partials.olevel-subject-select', [
                        'position' => 2,
                        'name' => 'olevel2_subject2',
                        'subjects' => $olevelSubjects,
                        'lockedValue' => $olevelCompulsory[2] ?? null,
                        'value' => old('olevel2_subject2', $applicant->olevel2_subject2 ?? null),
                    ])
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel2_grade2" class="form-select @error('olevel2_grade2') is-invalid @enderror">
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
                    @error('olevel2_grade2') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-2">
                    @include('applicant.partials.olevel-subject-select', [
                        'position' => 3,
                        'name' => 'olevel2_subject3',
                        'subjects' => $olevelSubjects,
                        'lockedValue' => null,
                        'value' => old('olevel2_subject3', $applicant->olevel2_subject3 ?? null),
                    ])
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel2_grade3" class="form-select @error('olevel2_grade3') is-invalid @enderror">
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
                    @error('olevel2_grade3') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-2">
                    @include('applicant.partials.olevel-subject-select', [
                        'position' => 4,
                        'name' => 'olevel2_subject4',
                        'subjects' => $olevelSubjects,
                        'lockedValue' => null,
                        'value' => old('olevel2_subject4', $applicant->olevel2_subject4 ?? null),
                    ])
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel2_grade4" class="form-select @error('olevel2_grade4') is-invalid @enderror">
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
                    @error('olevel2_grade4') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-2">
                    @include('applicant.partials.olevel-subject-select', [
                        'position' => 5,
                        'name' => 'olevel2_subject5',
                        'subjects' => $olevelSubjects,
                        'lockedValue' => null,
                        'value' => old('olevel2_subject5', $applicant->olevel2_subject5 ?? null),
                    ])
                </div>
                <div class="col-md-6 mb-2">
                    <select name="olevel2_grade5" class="form-select @error('olevel2_grade5') is-invalid @enderror">
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
                    @error('olevel2_grade5') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <input type="number" name="olevel2_exam_year" class="form-control @error('olevel2_exam_year') is-invalid @enderror" placeholder="Exam Year" min="2000" max="2030" value="{{ old('olevel2_exam_year', $applicant->olevel2_exam_year ?? '') }}">
                    @error('olevel2_exam_year') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
            <textarea name="extra_curricular" class="form-control @error('extra_curricular') is-invalid @enderror" rows="3" placeholder="List any clubs, sports, competitions, etc.">{{ old('extra_curricular', $applicant->extra_curricular ?? '') }}</textarea>
            @error('extra_curricular') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
                    <input type="text" name="guardian_name" class="form-control @error('guardian_name') is-invalid @enderror" value="{{ old('guardian_name', $applicant->guardian_name ?? '') }}">
                    @error('guardian_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Relationship</label>
                    <select name="guardian_relationship" class="form-select @error('guardian_relationship') is-invalid @enderror">
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
                    @error('guardian_relationship') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Guardian Phone</label>
                    <input type="tel" name="guardian_phone" class="form-control @error('guardian_phone') is-invalid @enderror" value="{{ old('guardian_phone', $applicant->guardian_phone ?? '') }}">
                    @error('guardian_phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Guardian Email</label>
                    <input type="email" name="guardian_email" class="form-control @error('guardian_email') is-invalid @enderror" value="{{ old('guardian_email', $applicant->guardian_email ?? '') }}">
                    @error('guardian_email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="guardian_occupation" class="form-control @error('guardian_occupation') is-invalid @enderror" value="{{ old('guardian_occupation', $applicant->guardian_occupation ?? '') }}">
                    @error('guardian_occupation') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Guardian Address</label>
                    <textarea name="guardian_address" class="form-control @error('guardian_address') is-invalid @enderror" rows="1">{{ old('guardian_address', $applicant->guardian_address ?? '') }}</textarea>
                    @error('guardian_address') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
                @php
                    $passportPath = '';
                    $passportFile = $applicant->passport;
                    if (file_exists(public_path('storage/passports/' . $passportFile))) {
                        $passportPath = 'storage/passports/' . $passportFile;
                    } elseif (file_exists(public_path('storage/app/public/passports/' . $passportFile))) {
                        $passportPath = 'storage/app/public/passports/' . $passportFile;
                    } elseif (file_exists(public_path('uploads/passports/' . $passportFile))) {
                        $passportPath = 'uploads/passports/' . $passportFile;
                    }
                @endphp
                @if($passportPath)
                    <img src="{{ asset($passportPath) }}" alt="Passport" style="max-width: 150px;">
                @else
                    <img src="{{ asset('storage/passports/' . $passportFile) }}" alt="Passport" style="max-width: 150px;" onerror="this.style.display='none'">
                @endif
                <p class="text-muted small">Current passport photo</p>
            </div>
            @endif
            <input type="file" name="passport" class="form-control @error('passport') is-invalid @enderror" accept="image/*">
            @error('passport') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
