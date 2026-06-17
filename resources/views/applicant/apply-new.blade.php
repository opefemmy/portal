@extends('layouts.app')

@section('title', 'Application Form - University Admission')

@section('content')
<div class="page-header">
    <h4>Application Form - University Admission</h4>
    <p class="text-muted">Fill all fields correctly. Fields marked with * are required.</p>
</div>

<form method="POST" action="{{ route('applicant.submit') }}" enctype="multipart/form-data" id="applicationForm">
    @csrf

    <!-- Personal Information -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user me-2"></i> Personal Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Surname/Family Name *</label>
                        <input type="text" name="surname" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Date of Birth *</label>
                        <input type="date" name="date_of_birth" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Place of Birth *</label>
                        <input type="text" name="place_of_birth" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Gender *</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Nationality *</label>
                        <input type="text" name="nationality" class="form-control" value="Nigerian" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">State of Origin</label>
                        <input type="text" name="state_of_origin" class="form-control">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Permanent Address *</label>
                        <textarea name="permanent_address" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Contact Address</label>
                        <textarea name="contact_address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Phone Number *</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Passport Photograph</label>
                        <input type="file" name="passport" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guardian Information -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-users me-2"></i> Guardian / Next of Kin</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Guardian's Name *</label>
                        <input type="text" name="guardian_name" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Relationship *</label>
                        <select name="guardian_relationship" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Guardian">Guardian</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Guardian's Phone *</label>
                        <input type="text" name="guardian_phone" class="form-control" required>
                    </div>
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
                    <div class="mb-3">
                        <label class="form-label">Secondary School *</label>
                        <input type="text" name="secondary_school" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">O-Level Certificate</label>
                        <input type="file" name="olevel_certificate" class="form-control">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tertiary Institution (if any)</label>
                        <input type="text" name="tertiary_institution" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Qualification</label>
                        <select name="tertiary_qualification" class="form-select">
                            <option value="">Select</option>
                            <option value="ND">ND</option>
                            <option value="HND">HND</option>
                            <option value="Bachelor">Bachelor</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Programme Selection -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-book me-2"></i> Programme Selection</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Faculty *</label>
                        <select name="school_id" class="form-select" id="schoolSelect" required>
                            <option value="">Select Faculty</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}">{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Department *</label>
                        <select name="department_id" class="form-select" id="departmentSelect" required>
                            <option value="">Select Department</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Programme *</label>
                        <select name="programme_id" class="form-select" required>
                            <option value="">Select Programme</option>
                            @foreach($programmes as $programme)
                                <option value="{{ $programme->id }}">{{ $programme->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Session *</label>
                        <select name="session_id" class="form-select" required>
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Mode of Study</label>
                        <select name="mode_of_study" class="form-select">
                            <option value="Full Time">Full Time</option>
                            <option value="Part Time">Part Time</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JAMB Details -->
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <h5 class="mb-0">UTME/JAMB Details (Optional)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">JAMB Reg Number</label>
                        <input type="text" name="jamb_registration_number" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">JAMB Year</label>
                        <input type="text" name="jamb_year" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">JAMB Score</label>
                        <input type="number" name="jamb_score" class="form-control" placeholder="0-400">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">JAMB Result</label>
                        <input type="file" name="jamb_result" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Birth Certificate</label>
                        <input type="file" name="birth_certificate" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Declaration -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="declaration" required>
                <label class="form-check-label" for="declaration">
                    I declare that all information provided is true and accurate.
                </label>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-paper-plane me-2"></i> Submit Application
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#schoolSelect').change(function() {
        var schoolId = $(this).val();
        var departmentSelect = $('#departmentSelect');
        departmentSelect.html('<option value="">Loading...</option>');

        if (schoolId) {
            $.get('/applicant/departments/' + schoolId, function(data) {
                departmentSelect.html('<option value="">Select Department</option>');
                $.each(data, function(index, dept) {
                    departmentSelect.append('<option value="' + dept.id + '">' + dept.name + '</option>');
                });
            });
        }
    });
});
</script>
@endpush