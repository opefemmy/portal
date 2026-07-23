@extends('layouts.app')

@section('title', 'Applicant Dashboard')

@section('content')
<div class="page-header">
    <h4>Applicant Dashboard</h4>
</div>

@if($applicant)
{{-- Admission Status Alert --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-3" style="border-color: {{ $applicant->status === 'admitted' ? '#198754' : ($applicant->status === 'rejected' ? '#dc3545' : '#0dcaf0') }};">
            <div class="card-header bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : 'warning') }} text-white">
                <h5 class="mb-0"><i class="fas fa-{{ $applicant->status === 'admitted' ? 'check-circle' : ($applicant->status === 'rejected' ? 'times-circle' : 'clock') }} me-2"></i>Admission Status: {{ strtoupper($applicant->status) }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Application Number:</strong></p>
                        <h4>{{ $applicant->application_number }}</h4>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Screening Status:</strong></p>
                        <h4>
                            @if($applicant->screening_status === 'passed')
                            <span class="badge bg-success">PASSED</span>
                            @elseif($applicant->screening_status === 'failed')
                            <span class="badge bg-danger">FAILED</span>
                            @else
                            <span class="badge bg-warning text-dark">PENDING</span>
                            @endif
                        </h4>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Admission Status:</strong></p>
                        <h4>
                            @if($applicant->status === 'admitted')
                            <span class="badge bg-success">ADMITTED</span>
                            @elseif($applicant->status === 'rejected')
                            <span class="badge bg-danger">REJECTED</span>
                            @else
                            <span class="badge bg-warning text-dark">UNDER PROCESSING</span>
                            @endif
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Action Cards --}}
<div class="row">
    {{-- Check Screening Result --}}
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Screening Result</h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-clipboard-check fa-3x text-info mb-3"></i>
                <p class="text-muted">Check your screening/examination result</p>
                @if($applicant->screening_status === 'passed')
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Congratulations! You passed the screening.
                </div>
                @elseif($applicant->screening_status === 'failed')
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>You did not meet the screening requirements.
                </div>
                @else
                <div class="alert alert-warning">
                    <i class="fas fa-clock me-2"></i>Screening result not yet released.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Check Admission Status --}}
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-graduation-cap me-2"></i>Admission Status</h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-user-graduation-cap fa-3x text-primary mb-3"></i>
                <p class="text-muted">Check your admission status</p>
                @if($applicant->status === 'admitted')
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i>ADMITTED!</h5>
                    <p class="mb-0">Congratulations on your admission. Please proceed to pay your acceptance fee.</p>
                </div>
                @elseif($applicant->status === 'rejected')
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>Your application was not successful.
                </div>
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Your application is being processed. Check back later.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Payment / Requery --}}
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment / Validate</h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-credit-card fa-3x text-warning mb-3"></i>
                <p class="text-muted">Validate payment or check payment status</p>
                @if($applicant->payment_status === 'completed')
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Payment Completed
                    <br><small>Ref: {{ $applicant->payment_ref ?? 'N/A' }}</small>
                </div>
                @else
                <a href="{{ route('applicant.validate-payment') }}" class="btn btn-warning">
                    <i class="fas fa-check-circle me-2"></i>Validate Payment
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row mt-3">
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                <h5>View Application</h5>
                <p class="text-muted">View your submitted application details</p>
                <a href="{{ route('applicant.application') }}" class="btn btn-primary">
                    <i class="fas fa-eye me-2"></i>View
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-print fa-3x text-secondary mb-3"></i>
                <h5>Print Application</h5>
                <p class="text-muted">Print a copy of your application form</p>
                <a href="{{ route('applicant.application.print') }}" class="btn btn-secondary" target="_blank">
                    <i class="fas fa-print me-2"></i>Print
                </a>
            </div>
        </div>
    </div>

    @if($applicant->status === 'admitted')
    <div class="col-md-4 mb-3">
        <div class="card h-100 border-success">
            <div class="card-body text-center">
                <i class="fas fa-graduation-cap fa-3x text-success mb-3"></i>
                <h5>Accept Admission</h5>
                <p class="text-muted">Pay acceptance fee to secure your admission</p>
                <a href="{{ route('student.payments') }}" class="btn btn-success">
                    <i class="fas fa-credit-card me-2"></i>Pay Now
                </a>
            </div>
        </div>
    </div>
    @endif
</div>

@if($applicant->matric_number)
<div class="card mt-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Admission Details</h5>
    </div>
    <div class="card-body">
        <table class="table table-striped">
            <tr>
                <td><strong>Matric Number:</strong></td>
                <td><span class="badge bg-warning text-dark fs-6">{{ $applicant->matric_number }}</span></td>
            </tr>
            <tr>
                <td><strong>Department:</strong></td>
                <td>{{ $applicant->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Programme:</strong></td>
                <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>School:</strong></td>
                <td>{{ $applicant->school->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Level:</strong></td>
                <td>{{ $applicant->level }}00 Level</td>
            </tr>
        </table>
    </div>
</div>
@endif

@else
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-file-circle-plus fa-4x text-muted mb-4"></i>
                <h4>No Application Submitted</h4>
                <p class="text-muted">You haven't submitted an application yet. Apply now to get started.</p>
                <a href="{{ route('applicant.apply') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane me-2"></i>Apply Now
                </a>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
function requeryPayment() {
    // This would typically call an API to check payment status
    alert('Requerying payment status...');
}
</script>
@endpush
@endsection