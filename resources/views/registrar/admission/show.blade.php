@extends('layouts.app')

@section('title', 'View Applicant')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Applicant Details</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('registrar.admission') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
        <a href="{{ route('registrar.admission.edit', $applicant->id) }}" class="btn btn-primary">
            <i class="fas fa-edit me-2"></i>Edit
        </a>
        <a href="#" class="btn btn-info" onclick="window.print()">
            <i class="fas fa-print me-2"></i>Print
        </a>
    </div>
</div>

<div class="row">
    <!-- Personal Information -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Application Number</th>
                        <td><code>{{ $applicant->application_number ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <th>Full Name</th>
                        <td>{{ $applicant->full_name }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $applicant->email }}</td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td>{{ $applicant->phone ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td>{{ $applicant->gender ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Date of Birth</th>
                        <td>{{ optional($applicant->date_of_birth)->format('d M, Y') ?? $applicant->date_of_birth ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Blood Group</th>
                        <td>{{ $applicant->blood_group ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Genotype</th>
                        <td>{{ $applicant->genotype ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Programme Selection -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Programme Selection</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">School</th>
                        <td>{{ $applicant->school->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Department</th>
                        <td>{{ $applicant->department->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Programme</th>
                        <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Session</th>
                        <td>{{ $applicant->session->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Preferred Centre</th>
                        <td>{{ $applicant->centre->name ?? 'N/A' }} ({{ $applicant->centre->code ?? '' }})</td>
                    </tr>
                    <tr>
                        <th>Mode of Study</th>
                        <td>{{ $applicant->mode_of_study ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Payment Information -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Payment Status</th>
                        <td>
                            <span class="badge bg-{{ $applicant->payment_status === 'completed' ? 'success' : 'warning' }}">
                                {{ ucfirst($applicant->payment_status ?? 'pending') }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Payment Reference</th>
                        <td><code>{{ $applicant->payment_ref ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td>{{ $applicant->payment_amount ? '₦' . number_format($applicant->payment_amount, 2) : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Payment Date</th>
                        <td>{{ $applicant->payment_date ? $applicant->payment_date->format('d M, Y') : 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Admission Status -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Admission Status</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Status</th>
                        <td>
                            <span class="badge bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : ($applicant->status === 'pending' ? 'warning' : 'info')) }}">
                                {{ ucfirst($applicant->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Matric Number</th>
                        <td><code>{{ $applicant->matric_number ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <th>Application Date</th>
                        <td>{{ optional($applicant->created_at)->format('d M, Y') ?? 'N/A' }}</td>
                    </tr>
                </table>

                <hr>

                <h6>Actions</h6>
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <!-- Update Status -->
                    <form method="POST" action="{{ route('registrar.admission.updateStatus', $applicant) }}" class="d-flex gap-2">
                        @csrf @method('PUT')
                        <select name="status" class="form-select">
                            <option value="pending" {{ $applicant->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="reviewed" {{ $applicant->status == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                            <option value="admitted" {{ $applicant->status == 'admitted' ? 'selected' : '' }}>Admit</option>
                            <option value="rejected" {{ $applicant->status == 'rejected' ? 'selected' : '' }}>Reject</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>

                    {{-- Reassign Department shortcut — one-click modal
                         that moves them to a new school/department/
                         programme without touching personal info. Same
                         route + controller method as the applications/
                         show page; gated by registrar.applicants.edit
                         (super_admin + registrar have it, admission
                         officer does not). --}}
                    @php
                        $reassignRoute = Route::has('registrar.admission.reassignDepartment')
                            ? route('registrar.admission.reassignDepartment', $applicant)
                            : (Route::has('registrar.applicants.reassignDepartment')
                                ? route('registrar.applicants.reassignDepartment', $applicant)
                                : null);
                    @endphp
                    @can('registrar.applicants.edit')
                    @if($reassignRoute)
                    <button type="button"
                            class="btn btn-warning"
                            data-bs-toggle="modal"
                            data-bs-target="#reassignModal">
                        <i class="fas fa-exchange-alt me-1"></i> Reassign Department
                    </button>
                    @endif
                    @endcan

                    <!-- Reset Password -->
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                        <i class="fas fa-key me-1"></i> Reset Password
                    </button>

                    {{--
                        Auto-login link generator.

                        Only meaningful after admission + student creation —
                        without a Student row there's no portal account yet.
                        Clicking opens a modal that exposes the one-time URL
                        the registrar can send to the student (SMS, email,
                        WhatsApp, etc.).
                    --}}
                    @php
                        // Prefer student_id (set by ApplicantPaymentService when the
                        // compulsory fee is paid); fall back to matric lookup for the
                        // legacy flow where student_created was set but no row
                        // migration happened yet.
                        $studentRow = null;
                        if ($applicant->student_id) {
                            $studentRow = \App\Models\Student::find($applicant->student_id);
                        }
                        if (! $studentRow && $applicant->matric_number) {
                            $studentRow = \App\Models\Student::where('matric_number', $applicant->matric_number)->first();
                        }
                    @endphp
                    @if($studentRow && $studentRow->user)
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#autoLoginModal">
                        <i class="fas fa-magic me-1"></i> Generate Auto-Login Link
                    </button>
                    @endif
                </div>

                <!-- Reset Password Modal -->
                <div class="modal fade" id="resetPasswordModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Reset Applicant Password</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="{{ route('registrar.admission.resetPassword', $applicant) }}">
                                @csrf
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" required minlength="8">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="new_password_confirmation" class="form-control" required minlength="8">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning">Reset Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Reassign Department Modal -->
                @can('registrar.applicants.edit')
                @if(isset($reassignRoute) && $reassignRoute)
                @php
                    $rsSchools = \App\Models\School::orderBy('name')->get(['id', 'name']);
                    $rsDepts   = \App\Models\Department::orderBy('name')->get(['id', 'name', 'school_id']);
                    $rsProgs   = \App\Models\Programme::orderBy('name')->get(['id', 'name', 'department_id']);
                @endphp
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
                                        Move <strong>{{ $applicant->full_name }}</strong>
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

                <!-- Auto-Login Link Modal -->
                @if($studentRow && $studentRow->user)
                <div class="modal fade" id="autoLoginModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-magic me-1"></i> Student Auto-Login Link
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>
                                    Send this one-time link to <strong>{{ $applicant->full_name }}</strong>
                                    (<code>{{ $studentRow->matric_number }}</code>). Opening it signs them
                                    in and forces a password change.
                                </p>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-clock me-1"></i> Expires 7 days after generation.
                                </p>
                                <div class="input-group">
                                    <input type="text" id="autoLoginUrl" class="form-control" readonly
                                           value="{{ \App\Http\Controllers\Student\AutoLoginController::generateForStudent($studentRow) }}">
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="(function(){
                                                var i=document.getElementById('autoLoginUrl');
                                                i.select(); document.execCommand('copy');
                                                this.innerHTML='<i class=\'fas fa-check\'></i> Copied';
                                                var b=this; setTimeout(function(){ b.innerHTML='<i class=\'fas fa-copy\'></i> Copy'; }, 1500);
                                            })()">
                                        <i class="fas fa-copy me-1"></i> Copy
                                    </button>
                                </div>
                                <div class="mt-3 small text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    The link is bound to this specific student account and can only be used once.
                                    Sharing it with anyone other than the student is at your own risk.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- O-Level Results -->
@if($applicant->olevel1_subject1)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-book me-2"></i>O-Level Results</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>First Sitting</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 1; $i <= 5; $i++)
                            @if($applicant->{'olevel1_subject' . $i})
                            <tr>
                                <td>{{ $applicant->{'olevel1_subject' . $i} }}</td>
                                <td>{{ $applicant->{'olevel1_grade' . $i} }}</td>
                            </tr>
                            @endif
                        @endfor
                    </tbody>
                </table>
                <p class="text-muted"><small>Exam Year: {{ $applicant->olevel1_exam_year ?? 'N/A' }}</small></p>
            </div>
            @if($applicant->olevel2_subject1)
            <div class="col-md-6">
                <h6>Second Sitting</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 1; $i <= 5; $i++)
                            @if($applicant->{'olevel2_subject' . $i})
                            <tr>
                                <td>{{ $applicant->{'olevel2_subject' . $i} }}</td>
                                <td>{{ $applicant->{'olevel2_grade' . $i} }}</td>
                            </tr>
                            @endif
                        @endfor
                    </tbody>
                </table>
                <p class="text-muted"><small>Exam Year: {{ $applicant->olevel2_exam_year ?? 'N/A' }}</small></p>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
// Reassign modal cascade — same shape as on applications/show but
// scoped to #reassignModal so it doesn't collide with other dropdowns
// on this page.
$('#reassignModal').on('shown.bs.modal', function () {
    var $modal = $(this);
    var $school = $modal.find('#rsSchool');
    var $dept   = $modal.find('#rsDept');
    var $prog   = $modal.find('#rsProg');

    function cascade() {
        var schoolId = String($school.val() || '');

        $dept.find('option').each(function () {
            var matches = !schoolId || $(this).data('school-id') == schoolId;
            $(this).prop('disabled', !matches);
        });
        if ($dept.find('option:selected').prop('disabled')) {
            $dept.find('option:not(:disabled)').first().prop('selected', true);
        }

        var deptId = String($dept.val() || '');
        $prog.find('option').each(function () {
            var matches = !deptId || $(this).data('department-id') == deptId;
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

@endsection
