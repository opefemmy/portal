@extends('layouts.app')

@section('title', 'My Application')

@section('content')
<div class="page-header">
    <h4>My Application</h4>
</div>

@if(!$applicant)
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-file-circle-plus fa-4x text-muted mb-4"></i>
                <h4>No Application Yet</h4>
                <p class="text-muted">You haven't submitted an application yet.</p>
                <a href="{{ route('applicant.apply') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane me-2"></i>Apply Now
                </a>
            </div>
        </div>
    </div>
</div>
@else
{{-- Payment Verification Section --}}
@if($applicant->payment_status !== 'completed')
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Payment Required</h5>
    </div>
    <div class="card-body">
        <p>To complete your application, you need to verify your payment. If you made a payment outside the portal, please enter your payment details below.</p>

        <form method="POST" action="{{ route('applicant.payment.verify-external') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Payment Reference / Transaction ID</label>
                <input type="text" name="payment_ref" class="form-control" placeholder="Enter payment reference" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Amount Paid</label>
                <input type="number" name="amount" class="form-control" placeholder="Amount" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Payment Date</label>
                <input type="date" name="payment_date" class="form-control" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-warning w-100">
                    <i class="fas fa-check me-1"></i> Verify
                </button>
            </div>
        </form>

        <hr>
        <p class="mb-2">Or pay through the portal:</p>
        <a href="{{ route('applicant.apply.fee') }}" class="btn btn-success">
            <i class="fas fa-credit-card me-2"></i>Pay Application Fee Online
        </a>
    </div>
</div>
@endif

{{-- Application Details --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Application Details</h5>
        <div>
            @if($applicant->status === 'pending' || $applicant->status === 'draft')
            <a href="{{ route('applicant.application.edit') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit me-1"></i> Edit Application
            </a>
            @endif
            <a href="{{ route('applicant.application.print') }}" class="btn btn-info btn-sm" target="_blank">
                <i class="fas fa-print me-1"></i> Print
            </a>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <tr>
                <th width="30%">Application Number:</th>
                <td><strong>{{ $applicant->application_number }}</strong></td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    @if($applicant->status === 'admitted')
                        <span class="badge bg-success">ADMITTED</span>
                    @elseif($applicant->status === 'rejected')
                        <span class="badge bg-danger">REJECTED</span>
                    @elseif($applicant->status === 'approved')
                        <span class="badge bg-info">APPROVED</span>
                    @else
                        <span class="badge bg-warning">{{ strtoupper($applicant->status) }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Payment Status:</th>
                <td>
                    @if($applicant->payment_status === 'completed')
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i> Paid</span>
                    @else
                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Not Paid</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- Personal Information --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Personal Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <strong>Surname:</strong> {{ $applicant->surname ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>First Name:</strong> {{ $applicant->first_name ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>Middle Name:</strong> {{ $applicant->middle_name ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>Gender:</strong> {{ $applicant->gender ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>Date of Birth:</strong> {{ $applicant->date_of_birth ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>Place of Birth:</strong> {{ $applicant->place_of_birth ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>Religion:</strong> {{ $applicant->religion ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>Blood Group:</strong> {{ $applicant->blood_group ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>Genotype:</strong> {{ $applicant->genotype ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>Disability:</strong> {{ $applicant->disability === 'none' ? 'None' : ($applicant->disability ?? 'None') }}
            </div>
            <div class="col-md-6 mb-3">
                <strong>Email:</strong> {{ $applicant->email ?? 'N/A' }}
            </div>
            <div class="col-md-6 mb-3">
                <strong>Phone:</strong> {{ $applicant->phone ?? 'N/A' }}
            </div>
        </div>
    </div>
</div>

{{-- Contact Information --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Contact Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-3">
                <strong>Address:</strong> {{ $applicant->address ?? 'N/A' }}
            </div>
            <div class="col-md-4 mb-3">
                <strong>State:</strong> {{ $applicant->state->name ?? 'N/A' }}
            </div>
            <div class="col-md-4 mb-3">
                <strong>LGA:</strong> {{ $applicant->lga->name ?? 'N/A' }}
            </div>
            <div class="col-md-4 mb-3">
                <strong>Nationality:</strong> {{ $applicant->nationality->name ?? 'N/A' }}
            </div>
        </div>
    </div>
</div>

{{-- Programme Selection --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Programme Selection</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <strong>School:</strong> {{ $applicant->school->name ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>Department:</strong> {{ $applicant->department->name ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>Programme:</strong> {{ $applicant->programme->name ?? 'N/A' }}
            </div>
            <div class="col-md-3 mb-3">
                <strong>Session:</strong> {{ $applicant->session->name ?? 'N/A' }}
            </div>
        </div>
    </div>
</div>

{{-- O-Level Results --}}
@if($applicant->olevel1_subject1 || $applicant->olevel2_subject1)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">O-Level Results</h5>
    </div>
    <div class="card-body">
        <h6>First Sitting</h6>
        <div class="row mb-3">
            <div class="col-md-4">
                <strong>Exam Type:</strong> {{ $applicant->olevel1_exam_type ?? 'N/A' }}
            </div>
            <div class="col-md-4">
                <strong>Exam Number:</strong> {{ $applicant->olevel1_exam_number ?? 'N/A' }}
            </div>
            <div class="col-md-4">
                <strong>Exam Year:</strong> {{ $applicant->olevel1_exam_year ?? 'N/A' }}
            </div>
        </div>
        <table class="table table-sm table-bordered mb-4">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @for($i = 1; $i <= 5; $i++)
                    @php $subject = 'olevel1_subject' . $i; $grade = 'olevel1_grade' . $i; @endphp
                    @if($applicant->$subject)
                    <tr>
                        <td>{{ $applicant->$subject }}</td>
                        <td>{{ $applicant->$grade }}</td>
                    </tr>
                    @endif
                @endfor
            </tbody>
        </table>

        @if($applicant->olevel2_subject1)
        <h6>Second Sitting</h6>
        <div class="row mb-3">
            <div class="col-md-4">
                <strong>Exam Type:</strong> {{ $applicant->olevel2_exam_type ?? 'N/A' }}
            </div>
            <div class="col-md-4">
                <strong>Exam Number:</strong> {{ $applicant->olevel2_exam_number ?? 'N/A' }}
            </div>
            <div class="col-md-4">
                <strong>Exam Year:</strong> {{ $applicant->olevel2_exam_year ?? 'N/A' }}
            </div>
        </div>
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @for($i = 1; $i <= 5; $i++)
                    @php $subject = 'olevel2_subject' . $i; $grade = 'olevel2_grade' . $i; @endphp
                    @if($applicant->$subject)
                    <tr>
                        <td>{{ $applicant->$subject }}</td>
                        <td>{{ $applicant->$grade }}</td>
                    </tr>
                    @endif
                @endfor
                @if($applicant->olevel2_exam_year)
                <tr>
                    <td><strong>Exam Year:</strong></td>
                    <td>{{ $applicant->olevel2_exam_year }}</td>
                </tr>
                @endif
            </tbody>
        </table>
        @endif
    </div>
</div>
@endif

{{-- Extra Curricular --}}
@if($applicant->extra_curricular)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Extra Curricular Activities</h5>
    </div>
    <div class="card-body">
        {{ $applicant->extra_curricular }}
    </div>
</div>
@endif

{{-- Guardian Information --}}
@if($applicant->guardian_name)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Guardian / Parent Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <strong>Name:</strong> {{ $applicant->guardian_name ?? 'N/A' }}
            </div>
            <div class="col-md-4 mb-3">
                <strong>Relationship:</strong> {{ $applicant->guardian_relationship ?? 'N/A' }}
            </div>
            <div class="col-md-4 mb-3">
                <strong>Phone:</strong> {{ $applicant->guardian_phone ?? 'N/A' }}
            </div>
            <div class="col-md-4 mb-3">
                <strong>Email:</strong> {{ $applicant->guardian_email ?? 'N/A' }}
            </div>
            <div class="col-md-4 mb-3">
                <strong>Occupation:</strong> {{ $applicant->guardian_occupation ?? 'N/A' }}
            </div>
            <div class="col-md-4 mb-3">
                <strong>Address:</strong> {{ $applicant->guardian_address ?? 'N/A' }}
            </div>
        </div>
    </div>
</div>
@endif

{{-- Action Buttons --}}
<div class="text-center mb-4">
    @if($applicant->payment_status === 'completed' && $applicant->status === 'pending')
    <span class="badge bg-info fs-6">Application Submitted - Awaiting Review</span>
    @elseif($applicant->status === 'admitted')
    <div class="alert alert-success">
        <h5><i class="fas fa-check-circle me-2"></i>Congratulations! You have been admitted!</h5>
        <p>Your matriculation number: <strong>{{ $applicant->matric_number ?? 'Pending' }}</strong></p>
    </div>
    @endif
</div>
@endif
@endsection
