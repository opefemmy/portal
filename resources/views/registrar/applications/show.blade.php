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
                        <p><strong>Nationality:</strong> {{ $applicant->nationality }}</p>
                        <p><strong>State of Origin:</strong> {{ $applicant->state_of_origin }}</p>
                        <p><strong>LGA:</strong> {{ $applicant->lga }}</p>
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
                        <img src="{{ asset('storage/passports/' . $applicant->passport) }}" class="img-thumbnail" style="max-width: 100px;">
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
                <p class="text-muted mb-2">Applied on: {{ $applicant->created_at->format('d F Y, h:i A') }}</p>
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
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#statusSelect').change(function() {
    if ($(this).val() === 'rejected') {
        $('#rejectionReason').show();
    } else {
        $('#rejectionReason').hide();
    }
});
</script>
@endpush