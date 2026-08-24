@extends('layouts.app')

@section('title', 'Apply Now')

@section('content')
<div class="page-header">
    <h4>Application Form</h4>
</div>

{{-- Success message if payment was verified --}}
@if(session('success') && str_contains(session('success'), 'Payment verified'))
<div class="alert alert-success mb-4">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
</div>
@endif

{{-- Validation error summary. Laravel's validate() failure path
     redirects back here with $errors populated and `old()` holding the
     user's input. The summary at the top lets the user see the
     full set of failures without scrolling; per-field feedback is
     rendered inline below each input via @error('FIELD'). --}}
@if ($errors->any())
    <div class="alert alert-danger mb-4" role="alert">
        <h6 class="alert-heading mb-2"><i class="fas fa-exclamation-circle me-2"></i>Please correct the following before submitting:</h6>
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('applicant.apply') }}" enctype="multipart/form-data" novalidate>
    @csrf

    {{-- Personal Information --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Personal Information</h5>
        </div>
        <div class="card-body">
            @php
                // Older signups created the Applicant row without the
                // three split name parts, so $applicant->surname etc.
                // are NULL for those users. users.name was always
                // populated at signup (concatenated from the three
                // parts), so we fall back to parsing it for legacy
                // accounts — splitting on the first whitespace puts the
                // surname on the left and the rest into "first middle".
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
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email ?? $applicant->email ?? '') }}" readonly>
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
                        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                            <option value="{{ $bg }}" {{ old('blood_group', $applicant->blood_group ?? '') == $bg ? 'selected' : '' }}>{{ $bg }}</option>
                        @endforeach
                    </select>
                    @error('blood_group') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Genotype</label>
                    <select name="genotype" class="form-select @error('genotype') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach(['AA','AS','SS','AC'] as $g)
                            <option value="{{ $g }}" {{ old('genotype', $applicant->genotype ?? '') == $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                    @error('genotype') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Disability</label>
                    <select name="disability" id="disability" class="form-select @error('disability') is-invalid @enderror">
                        <option value="none" {{ old('disability', $applicant->disability ?? 'none') == 'none' ? 'selected' : '' }}>None</option>
                        <option value="physical" {{ old('disability', $applicant->disability ?? '') == 'physical' ? 'selected' : '' }}>Physical</option>
                        <option value="visual" {{ old('disability', $applicant->disability ?? '') == 'visual' ? 'selected' : '' }}>Visual</option>
                        <option value="hearing" {{ old('disability', $applicant->disability ?? '') == 'hearing' ? 'selected' : '' }}>Hearing</option>
                        <option value="other" {{ old('disability', $applicant->disability ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('disability') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                @php
                    $disabilityForReveal = old('disability', $applicant->disability ?? '');
                @endphp
                <div class="col-md-12 mb-3" id="disability-details" style="display: {{ in_array($disabilityForReveal, ['physical','visual','hearing','other'], true) ? 'block' : 'none' }};">
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
                    // Server-side rehydration of the dependent LGA
                    // dropdown on a failed postback. After a validation
                    // failure the JS hasn't fired yet to populate LGAs,
                    // so we render the matching set inline so the
                    // previously-selected LGA is still in the list.
                    $oldStateId = old('state_id', $applicant->state_id ?? null);
                    $oldLgaId = old('lga_id', $applicant->lga_id ?? null);
                    $preselectedLgas = ($oldStateId && \Schema::hasTable('local_governments'))
                        ? \App\Models\LocalGovernment::where('state_id', $oldStateId)->orderBy('name')->get()
                        : collect();
                @endphp
                <div class="col-md-4 mb-3">
                    <label class="form-label">State *</label>
                    <select name="state_id" id="state_id" class="form-select @error('state_id') is-invalid @enderror" required>
                        <option value="">Select State</option>
                        @foreach($states as $state)
                        <option value="{{ $state->id }}" {{ (string) $oldStateId === (string) $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                        @endforeach
                    </select>
                    @error('state_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">LGA *</label>
                    <select name="lga_id" id="lga_id" class="form-select @error('lga_id') is-invalid @enderror" required>
                        <option value="">{{ $preselectedLgas->isNotEmpty() ? 'Select LGA' : 'Select State First' }}</option>
                        @foreach($preselectedLgas as $lga)
                            <option value="{{ $lga->id }}" {{ (string) $oldLgaId === (string) $lga->id ? 'selected' : '' }}>{{ $lga->name }}</option>
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
            @php
                // Same rehydration pattern as the LGA dropdown — render
                // departments / programmes inline when the form is
                // re-displayed after a failed postback so the user sees
                // their previous selection.
                $oldSchoolId = old('school_id', $applicant->school_id ?? null);
                $oldDeptId = old('department_id', $applicant->department_id ?? null);
                $preselectedDepts = ($oldSchoolId && \Schema::hasTable('departments'))
                    ? \App\Models\Department::where('school_id', $oldSchoolId)->orderBy('name')->get()
                    : collect();
                $preselectedProgs = ($oldDeptId && \Schema::hasTable('programmes'))
                    ? \App\Models\Programme::where('department_id', $oldDeptId)->orderBy('name')->get()
                    : collect();
            @endphp
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">School *</label>
                    <select name="school_id" id="school_id" class="form-select @error('school_id') is-invalid @enderror" required>
                        <option value="">Select School</option>
                        @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ (string) $oldSchoolId === (string) $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                    @error('school_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department *</label>
                    <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                        <option value="">{{ $preselectedDepts->isNotEmpty() ? 'Select Department' : 'Select School First' }}</option>
                        @foreach($preselectedDepts as $dept)
                            <option value="{{ $dept->id }}" {{ (string) $oldDeptId === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Programme *</label>
                    <select name="programme_id" id="programme_id" class="form-select @error('programme_id') is-invalid @enderror" required>
                        <option value="">{{ $preselectedProgs->isNotEmpty() ? 'Select Programme' : 'Select Department First' }}</option>
                        @foreach($preselectedProgs as $prog)
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
                    @php
                        $centres = \App\Models\AdmissionCentre::active()->orderBy('name')->get();
                    @endphp
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

    {{-- O-Level Results (First Sitting) --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">O-Level Results (First Sitting)</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Examination Type *</label>
                    <select name="olevel1_exam_type" class="form-select @error('olevel1_exam_type') is-invalid @enderror" required>
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
                    <input type="text" name="olevel1_exam_number" class="form-control @error('olevel1_exam_number') is-invalid @enderror" value="{{ old('olevel1_exam_number', $applicant->olevel1_exam_number ?? '') }}" placeholder="Exam Number">
                    @error('olevel1_exam_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exam Year *</label>
                    <select name="olevel1_exam_year" class="form-select @error('olevel1_exam_year') is-invalid @enderror" required>
                        <option value="">Select Year</option>
                        @for($year = date('Y'); $year >= date('Y') - 20; $year--)
                        <option value="{{ $year }}" {{ (string) old('olevel1_exam_year', $applicant->olevel1_exam_year ?? '') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                    @error('olevel1_exam_year') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>
            @php
                $grades = ['A/R'=>'A/R (Awaiting Result)','A1','B2','B3','C4','C5','C6','D7','E8','F9'];
            @endphp
            <div class="row">
                @for($n = 1; $n <= 5; $n++)
                    <div class="col-md-6 mb-2">
                        @include('applicant.partials.olevel-subject-select', [
                            'position' => $n,
                            'name' => 'olevel1_subject' . $n,
                            'subjects' => $olevelSubjects,
                            'lockedValue' => $olevelCompulsory[$n] ?? null,
                            'value' => old('olevel1_subject' . $n, $applicant->{'olevel1_subject' . $n} ?? null),
                        ])
                    </div>
                    <div class="col-md-6 mb-2">
                        <select name="olevel1_grade{{ $n }}" class="form-select @error('olevel1_grade' . $n) is-invalid @enderror">
                            <option value="">Grade</option>
                            @foreach($grades as $gKey => $gLabel)
                                @if(is_string($gKey))
                                    <option value="{{ $gKey }}" {{ old('olevel1_grade' . $n, $applicant->{'olevel1_grade' . $n} ?? '') == $gKey ? 'selected' : '' }}>{{ $gLabel }}</option>
                                @else
                                    <option value="{{ $gLabel }}" {{ old('olevel1_grade' . $n, $applicant->{'olevel1_grade' . $n} ?? '') == $gLabel ? 'selected' : '' }}>{{ $gLabel }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('olevel1_grade' . $n) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                @endfor
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
                    <input type="text" name="olevel2_exam_number" class="form-control @error('olevel2_exam_number') is-invalid @enderror" value="{{ old('olevel2_exam_number', $applicant->olevel2_exam_number ?? '') }}" placeholder="Exam Number">
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
                @for($n = 1; $n <= 5; $n++)
                    <div class="col-md-6 mb-2">
                        @include('applicant.partials.olevel-subject-select', [
                            'position' => $n,
                            'name' => 'olevel2_subject' . $n,
                            'subjects' => $olevelSubjects,
                            'lockedValue' => $olevelCompulsory[$n] ?? null,
                            'value' => old('olevel2_subject' . $n, $applicant->{'olevel2_subject' . $n} ?? null),
                        ])
                    </div>
                    <div class="col-md-6 mb-2">
                        <select name="olevel2_grade{{ $n }}" class="form-select @error('olevel2_grade' . $n) is-invalid @enderror">
                            <option value="">Grade</option>
                            @foreach($grades as $gKey => $gLabel)
                                @if(is_string($gKey))
                                    <option value="{{ $gKey }}" {{ old('olevel2_grade' . $n, $applicant->{'olevel2_grade' . $n} ?? '') == $gKey ? 'selected' : '' }}>{{ $gLabel }}</option>
                                @else
                                    <option value="{{ $gLabel }}" {{ old('olevel2_grade' . $n, $applicant->{'olevel2_grade' . $n} ?? '') == $gLabel ? 'selected' : '' }}>{{ $gLabel }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('olevel2_grade' . $n) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                @endfor
            </div>
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
                    <label class="form-label">Guardian Name *</label>
                    <input type="text" name="guardian_name" class="form-control @error('guardian_name') is-invalid @enderror" value="{{ old('guardian_name', $applicant->guardian_name ?? '') }}" required>
                    @error('guardian_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Relationship *</label>
                    <select name="guardian_relationship" class="form-select @error('guardian_relationship') is-invalid @enderror" required>
                        <option value="">Select Relationship</option>
                        @foreach(['Father','Mother','Guardian','Uncle','Aunt','Brother','Sister','Other'] as $rel)
                            <option value="{{ $rel }}" {{ old('guardian_relationship', $applicant->guardian_relationship ?? '') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                        @endforeach
                    </select>
                    @error('guardian_relationship') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Guardian Phone *</label>
                    <input type="tel" name="guardian_phone" class="form-control @error('guardian_phone') is-invalid @enderror" value="{{ old('guardian_phone', $applicant->guardian_phone ?? '') }}" required>
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

    {{-- Passport Upload --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Passport Photograph</h5>
        </div>
        <div class="card-body">
            <input type="file" name="passport" class="form-control @error('passport') is-invalid @enderror" accept="image/*">
            @error('passport') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
document.addEventListener('DOMContentLoaded', function() {

    // Show disability details if "other" (or any specific disability) is selected
    const disabilitySelect = document.querySelector('select[name="disability"]');
    if (disabilitySelect) {
        disabilitySelect.addEventListener('change', function() {
            const details = document.getElementById('disability-details');
            if (this.value === 'other' || this.value === 'physical' || this.value === 'visual' || this.value === 'hearing') {
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        });
    }

    // After a failed postback the LGAs were already rendered server-side
    // (see the inline @php block above). Skip the JS round-trip when
    // the option list is already populated; only fetch on subsequent
    // state changes.
    const stateSelectEl = document.getElementById('state_id');
    if (stateSelectEl) {
        stateSelectEl.addEventListener('change', function() {
            const stateId = this.value;
            const lgaSelectEl = document.getElementById('lga_id');
            if (!lgaSelectEl) return;
            lgaSelectEl.innerHTML = '<option value="">Loading...</option>';

            fetch('/applicant/lgas/' + stateId)
                .then(response => response.json())
                .then(data => {
                    lgaSelectEl.innerHTML = '<option value="">Select LGA</option>';
                    data.forEach(lga => {
                        const option = document.createElement('option');
                        option.value = lga.id;
                        option.textContent = lga.name;
                        lgaSelectEl.appendChild(option);
                    });
                })
                .catch(error => {
                    lgaSelectEl.innerHTML = '<option value="">Error loading LGA</option>';
                });
        });
    }

    const schoolSelectEl = document.getElementById('school_id');
    if (schoolSelectEl) {
        schoolSelectEl.addEventListener('change', function() {
            const schoolId = this.value;
            const deptSelectEl = document.getElementById('department_id');
            const progSelectEl = document.getElementById('programme_id');

            if (!deptSelectEl) return;

            if (progSelectEl) progSelectEl.innerHTML = '<option value="">Select Programme</option>';
            deptSelectEl.innerHTML = '<option value="">Loading...</option>';

            fetch('/applicant/departments/' + schoolId)
                .then(response => response.json())
                .then(data => {
                    deptSelectEl.innerHTML = '<option value="">Select Department</option>';
                    data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.name;
                        deptSelectEl.appendChild(option);
                    });
                })
                .catch(error => {
                    deptSelectEl.innerHTML = '<option value="">Error loading departments</option>';
                });
        });
    }

    const deptSelectEl = document.getElementById('department_id');
    if (deptSelectEl) {
        deptSelectEl.addEventListener('change', function() {
            const departmentId = this.value;
            const progSelectEl = document.getElementById('programme_id');
            if (!progSelectEl) return;

            progSelectEl.innerHTML = '<option value="">Loading...</option>';

            fetch('/applicant/programmes/' + departmentId)
                .then(response => response.json())
                .then(data => {
                    progSelectEl.innerHTML = '<option value="">Select Programme</option>';
                    data.forEach(prog => {
                        const option = document.createElement('option');
                        option.value = prog.id;
                        option.textContent = prog.name;
                        progSelectEl.appendChild(option);
                    });
                })
                .catch(error => {
                    progSelectEl.innerHTML = '<option value="">Error loading programmes</option>';
                });
        });
    }

}); // End DOMContentLoaded
</script>
@endpush
@endsection
