@extends('layouts.app')

@section('title', 'Application Details')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Application Details</h4>
    <div>
        <a href="{{ route('registrar.applications.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>

<div class="row">
    <!-- Left Column - Application Info -->
    <div class="col-md-8">
        <!-- Personal Information -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i> Personal Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Application Number:</strong> {{ $applicant->application_number }}</p>
                        <p><strong>Full Name:</strong> {{ $applicant->first_name }} {{ $applicant->surname }} {{ $applicant->middle_name }}</p>
                        <p><strong>Date of Birth:</strong> {{ $applicant->date_of_birth }}</p>
                        <p><strong>Place of Birth:</strong> {{ $applicant->place_of_birth }}</p>
                        <p><strong>Gender:</strong> {{ $applicant->gender }}</p>
                        <p><strong>Marital Status:</strong> {{ $applicant->marital_status }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Nationality:</strong> {{ $applicant->nationalityRecord->name ?? 'N/A' }}</p>
                        <p><strong>State of Origin:</strong> {{ $applicant->state_of_origin }}</p>
                        <p><strong>LGA:</strong> {{ $applicant->localGovernment->name ?? 'N/A' }}</p>
                        <p><strong>Email:</strong> {{ $applicant->email }}</p>
                        <p><strong>Phone:</strong> {{ $applicant->phone }}</p>
                        <p><strong>Permanent Address:</strong> {{ $applicant->permanent_address }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guardian Information -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i> Guardian Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> {{ $applicant->guardian_name }}</p>
                        <p><strong>Relationship:</strong> {{ $applicant->guardian_relationship }}</p>
                        <p><strong>Phone:</strong> {{ $applicant->guardian_phone }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Email:</strong> {{ $applicant->guardian_email }}</p>
                        <p><strong>Occupation:</strong> {{ $applicant->guardian_occupation }}</p>
                        <p><strong>Address:</strong> {{ $applicant->guardian_address }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Educational Background -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i> Educational Background</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Primary School:</strong> {{ $applicant->primary_school }}</p>
                        <p><strong>Secondary School:</strong> {{ $applicant->secondary_school }}</p>
                        <p><strong>Tertiary Institution:</strong> {{ $applicant->tertiary_institution }}</p>
                        <p><strong>Qualification:</strong> {{ $applicant->tertiary_qualification }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- JAMB Details -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0">UTME/JAMB Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>JAMB Reg Number:</strong> {{ $applicant->jamb_registration_number }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>JAMB Year:</strong> {{ $applicant->jamb_year }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>JAMB Score:</strong> {{ $applicant->jamb_score }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-file me-2"></i> Uploaded Documents</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($applicant->passport)
                    <div class="col-md-3 text-center">
                        <p>Passport</p>
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
                            <img src="{{ asset($passportPath) }}" class="img-thumbnail" style="max-width: 100px;">
                        @else
                            <img src="{{ asset('storage/passports/' . $passportFile) }}" class="img-thumbnail" style="max-width: 100px;" onerror="this.style.display='none'">
                        @endif
                    </div>
                    @endif
                    @if($applicant->olevel_certificate)
                    <div class="col-md-3">
                        <p>O-Level Certificate</p>
                        <a href="{{ asset('storage/certificates/' . $applicant->olevel_certificate) }}" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
                    </div>
                    @endif
                    @if($applicant->birth_certificate)
                    <div class="col-md-3">
                        <p>Birth Certificate</p>
                        <a href="{{ asset('storage/certificates/' . $applicant->birth_certificate) }}" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
                    </div>
                    @endif
                    @if($applicant->jamb_result)
                    <div class="col-md-3">
                        <p>JAMB Result</p>
                        <a href="{{ asset('storage/results/' . $applicant->jamb_result) }}" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column - Status & Actions -->
    <div class="col-md-4">
        <!-- Current Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Application Status</h5>
            </div>
            <div class="card-body text-center">
                @switch($applicant->status)
                    @case('pending')
                        <span class="badge bg-warning badge-lg">Pending</span>
                        @break
                    @case('screening')
                        <span class="badge bg-info badge-lg">Screening</span>
                        @break
                    @case('approved')
                        <span class="badge bg-success badge-lg">Approved</span>
                        @break
                    @case('admitted')
                        <span class="badge bg-primary badge-lg">Admitted</span>
                        @break
                    @case('rejected')
                        <span class="badge bg-danger badge-lg">Rejected</span>
                        @break
                @endswitch

                <hr>
                <p class="text-muted mb-2">Applied on: {{ optional($applicant->created_at)->format('d F Y, h:i A') ?? 'N/A' }}</p>
                @if($applicant->reviewed_at)
                <p class="text-muted">Reviewed on: {{ $applicant->reviewed_at->format('d F Y, h:i A') }}</p>
                @endif
            </div>
        </div>

        <!-- Update Status Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Update Status</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('registrar.applications.updateStatus', $applicant) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select name="status" class="form-select" id="statusSelect" required>
                            <option value="pending" {{ $applicant->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="screening" {{ $applicant->status == 'screening' ? 'selected' : '' }}>Screening</option>
                            <option value="approved" {{ $applicant->status == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="admitted" {{ $applicant->status == 'admitted' ? 'selected' : '' }}>Admitted</option>
                            <option value="rejected" {{ $applicant->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <div class="mb-3" id="rejectionReason" style="display: {{ $applicant->status == 'rejected' ? 'block' : 'none' }}">
                        <label class="form-label">Rejection Reason</label>
                        <textarea name="rejection_reason" class="form-control" rows="3">{{ $applicant->rejection_reason }}</textarea>
                    </div>

                    {{-- Reassign-on-Admit panel.
                         Hidden until the registrar picks status=admitted.
                         Lets them admit an applicant into a department /
                         programme / school DIFFERENT from the one they
                         registered for (e.g. quota in the requested dept
                         is full, JAMB subject combination didn't match,
                         etc.). Sends department_id/programme_id/school_id
                         alongside status; the controller only writes them
                         when status='admitted'. All three selectors
                         default to the applicant's registered placement
                         so the registrar can leave them unchanged by
                         simply picking 'admitted'. --}}
                    @php
                        $schools     = \App\Models\School::orderBy('name')->get(['id', 'name']);
                        $departments = \App\Models\Department::orderBy('name')->get(['id', 'name', 'school_id']);
                        $programmes  = \App\Models\Programme::orderBy('name')->get(['id', 'name', 'department_id']);
                    @endphp
                    <div class="mb-3 p-3 border rounded bg-light" id="reassignBlock" style="display: {{ $applicant->status == 'admitted' ? 'block' : 'none' }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-primary">
                                <i class="fas fa-exchange-alt me-1"></i>Reassign Placement on Admit
                            </strong>
                            <button type="button" class="btn btn-sm btn-link p-0" id="reassignReset">
                                Use registered placement
                            </button>
                        </div>
                        <p class="small text-muted mb-2">
                            Optional. Leave as-is to admit into the same
                            department the applicant registered for; pick
                            new values below to admit into a different
                            one (e.g. quota moved, transfer request).
                        </p>

                        <div class="mb-2">
                            <label class="form-label small mb-1">School</label>
                            <select name="school_id" id="reassignSchool" class="form-select form-select-sm">
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}" {{ $applicant->school_id == $school->id ? 'selected' : '' }}>
                                        {{ $school->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1">Department</label>
                            <select name="department_id" id="reassignDepartment" class="form-select form-select-sm">
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                            data-school-id="{{ $dept->school_id }}"
                                            {{ $applicant->department_id == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1">Programme</label>
                            <select name="programme_id" id="reassignProgramme" class="form-select form-select-sm">
                                @foreach($programmes as $prog)
                                    <option value="{{ $prog->id }}"
                                            data-department-id="{{ $prog->department_id }}"
                                            {{ $applicant->programme_id == $prog->id ? 'selected' : '' }}>
                                        {{ $prog->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="alert alert-warning small py-2 px-2 mb-0 mt-2" id="reassignWarning" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Placement differs from what the applicant registered for.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Update Status
                    </button>
                </form>
            </div>
        </div>

        <!-- Programme Details -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Programme Applied</h5>
            </div>
            <div class="card-body">
                <p><strong>Faculty:</strong> {{ $applicant->school->name ?? 'N/A' }}</p>
                <p><strong>Department:</strong> {{ $applicant->department->name ?? 'N/A' }}</p>
                <p><strong>Programme:</strong> {{ $applicant->programme->name ?? 'N/A' }}</p>
                <p><strong>Session:</strong> {{ $applicant->session->name ?? 'N/A' }}</p>
                <p><strong>Mode of Study:</strong> {{ $applicant->mode_of_study }}</p>
                <p><strong>Entry Level:</strong> {{ $applicant->entry_level }}</p>

                {{-- One-click Reassign Department shortcut.
                     Distinct from the Update Status card above (which
                     also lets you pick placement on admit) — this one
                     is for applicants who are ALREADY in the system
                     (admitted or not) and need to be moved to a
                     different department without touching personal
                     info. Posts to a dedicated route so the matric
                     number is regenerated to match the new dept code.
                     Gated by registrar.applicants.edit; admission
                     officer does NOT have access. --}}
                @can('registrar.applicants.edit')
                <hr>
                <button type="button"
                        class="btn btn-warning w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#reassignModal">
                    <i class="fas fa-exchange-alt me-2"></i>Reassign Department
                </button>
                @endcan
            </div>
        </div>
    </div>
</div>

{{-- Reassign Department modal.
     Single-purpose shortcut for "I admitted this student, but they
     need to move to a different department". Posts to
     registrar.applications.reassignDepartment (or, if the registrar
     arrived via /applicants/, registrar.applicants.reassignDepartment
     — both routes share the same controller method). The cascade
     logic mirrors the Reassign-on-Admit panel above so the same JS
     file works for both. --}}
@can('registrar.applicants.edit')
@php
    $reassignRoute = Route::has('registrar.applications.reassignDepartment')
        ? route('registrar.applications.reassignDepartment', $applicant)
        : (Route::has('registrar.applicants.reassignDepartment')
            ? route('registrar.applicants.reassignDepartment', $applicant)
            : null);
    $rsSchools = \App\Models\School::orderBy('name')->get(['id', 'name']);
    $rsDepts   = \App\Models\Department::orderBy('name')->get(['id', 'name', 'school_id']);
    $rsProgs   = \App\Models\Programme::orderBy('name')->get(['id', 'name', 'department_id']);
@endphp
@if($reassignRoute)
<div class="modal fade" id="reassignModal" tabindex="-1" aria-labelledby="reassignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ $reassignRoute }}" id="reassignForm">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="reassignModalLabel">
                        <i class="fas fa-exchange-alt me-2"></i>Reassign Department
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Move <strong>{{ $applicant->first_name }} {{ $applicant->surname }}</strong>
                        (App #{{ $applicant->application_number }}) to a different department.
                        The matric number will be regenerated so the prefix matches the new
                        department code.
                    </p>

                    <div class="alert alert-info small py-2 mb-3">
                        <strong>Current placement:</strong>
                        {{ $applicant->school->name ?? '—' }}
                        › {{ $applicant->department->name ?? '—' }}
                        › {{ $applicant->programme->name ?? '—' }}
                    </div>

                    <div class="mb-3">
                        <label for="rsSchool" class="form-label">New School <span class="text-danger">*</span></label>
                        <select name="school_id" id="rsSchool" class="form-select" required>
                            @foreach($rsSchools as $school)
                                <option value="{{ $school->id }}" {{ $applicant->school_id == $school->id ? 'selected' : '' }}>
                                    {{ $school->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="rsDept" class="form-label">New Department <span class="text-danger">*</span></label>
                        <select name="department_id" id="rsDept" class="form-select" required>
                            @foreach($rsDepts as $dept)
                                <option value="{{ $dept->id }}"
                                        data-school-id="{{ $dept->school_id }}"
                                        {{ $applicant->department_id == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="rsProg" class="form-label">New Programme <span class="text-danger">*</span></label>
                        <select name="programme_id" id="rsProg" class="form-select" required>
                            @foreach($rsProgs as $prog)
                                <option value="{{ $prog->id }}"
                                        data-department-id="{{ $prog->department_id }}"
                                        {{ $applicant->programme_id == $prog->id ? 'selected' : '' }}>
                                    {{ $prog->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label for="rsRemarks" class="form-label small">Reason / Remarks (optional)</label>
                        <textarea name="remarks" id="rsRemarks" class="form-control" rows="2" maxlength="1000"
                                  placeholder="e.g. Quota rebalance, JAMB subject mismatch, transfer request"></textarea>
                    </div>

                    <div class="alert alert-warning small py-2 px-2 mb-0 mt-2" id="rsWarning" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        This will move the applicant to a different placement.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-exchange-alt me-1"></i>Reassign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endcan

@push('scripts')
<script>
// Reassign modal cascade — independent of the Update Status cascade
// above (different DOM ids, scoped to #reassignModal).
$('#reassignModal').on('shown.bs.modal', function () {
    var $modal = $(this);
    var $school = $modal.find('#rsSchool');
    var $dept   = $modal.find('#rsDept');
    var $prog   = $modal.find('#rsProg');

    function cascade() {
        var schoolId = String($school.val() || '');
        var deptId   = String($dept.val()   || '');

        $dept.find('option').each(function () {
            var matches = !schoolId || $(this).data('school-id') == schoolId;
            $(this).prop('disabled', !matches);
        });
        if ($dept.find('option:selected').prop('disabled')) {
            $dept.find('option:not(:disabled)').first().prop('selected', true);
        }

        var newDeptId = String($dept.val() || '');
        $prog.find('option').each(function () {
            var matches = !newDeptId || $(this).data('department-id') == newDeptId;
            $(this).prop('disabled', !matches);
        });
        if ($prog.find('option:selected').prop('disabled')) {
            $prog.find('option:not(:disabled)').first().prop('selected', true);
        }

        var regSchool = String({{ (int) ($applicant->school_id ?? 0) }});
        var regDept   = String({{ (int) ($applicant->department_id ?? 0) }});
        var regProg   = String({{ (int) ($applicant->programme_id ?? 0) }});
        var changed =
            String($school.val() || '') !== regSchool ||
            String($dept.val()   || '') !== regDept   ||
            String($prog.val()   || '') !== regProg;
        $modal.find('#rsWarning').toggle(changed);
    }

    $school.off('change.rsCascade').on('change.rsCascade', cascade);
    $dept.off('change.rsCascade').on('change.rsCascade', cascade);
    $prog.off('change.rsCascade').on('change.rsCascade', cascade);
    cascade();
});
</script>
@endpush

@push('scripts')
<script>
$('#statusSelect').change(function() {
    if ($(this).val() === 'rejected') {
        $('#rejectionReason').show();
    } else {
        $('#rejectionReason').hide();
    }
    if ($(this).val() === 'admitted') {
        $('#reassignBlock').show();
    } else {
        $('#reassignBlock').hide();
    }
});

// Cascade: when the registrar picks a new school, narrow the
// department dropdown to departments belonging to that school.
$('#reassignSchool').on('change', function () {
    var schoolId = String($(this).val());
    var $dept = $('#reassignDepartment');
    $dept.find('option').each(function () {
        var matches = !schoolId || $(this).data('school-id') == schoolId;
        $(this).prop('disabled', !matches);
    });
    // If the currently-selected department is now disabled, pick the
    // first enabled one so the form doesn't submit a hidden value.
    if ($dept.find('option:selected').prop('disabled')) {
        $dept.find('option:not(:disabled)').first().prop('selected', true);
    }
    $dept.trigger('change');
});

// Cascade: when the department changes, narrow the programme dropdown
// to programmes belonging to that department.
$('#reassignDepartment').on('change', function () {
    var deptId = String($(this).val());
    var $prog = $('#reassignProgramme');
    $prog.find('option').each(function () {
        var matches = !deptId || $(this).data('department-id') == deptId;
        $(this).prop('disabled', !matches);
    });
    if ($prog.find('option:selected').prop('disabled')) {
        $prog.find('option:not(:disabled)').first().prop('selected', true);
    }
    checkReassignChanged();
});

// Surface a warning chip whenever any of the three selectors diverges
// from the applicant's registered placement — keeps the registrar
// honest about a transfer they're about to commit.
var registeredSchool     = String({{ (int) ($applicant->school_id ?? 0) }});
var registeredDepartment = String({{ (int) ($applicant->department_id ?? 0) }});
var registeredProgramme  = String({{ (int) ($applicant->programme_id ?? 0) }});
function checkReassignChanged() {
    var changed =
        String($('#reassignSchool').val()     || '') !== registeredSchool     ||
        String($('#reassignDepartment').val() || '') !== registeredDepartment ||
        String($('#reassignProgramme').val()  || '') !== registeredProgramme;
    $('#reassignWarning').toggle(changed);
}
$('#reassignSchool, #reassignProgramme').on('change', checkReassignChanged);
$('#reassignReset').on('click', function () {
    $('#reassignSchool').val(registeredSchool).trigger('change');
    $('#reassignDepartment').val(registeredDepartment).trigger('change');
    $('#reassignProgramme').val(registeredProgramme).trigger('change');
});

// Initial cascade — pick up whatever the controller rendered.
$('#reassignSchool').trigger('change');
checkReassignChanged();
</script>
@endpush
@endsection