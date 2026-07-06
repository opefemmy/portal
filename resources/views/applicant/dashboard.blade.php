@extends('layouts.app')

@section('title', 'Applicant Dashboard')

@section('content')
<div class="page-header">
    <h4>Applicant Dashboard</h4>
</div>

@if($applicant)
<div class="row mb-4">
    <div class="col-md-12">
        <div class="alert alert-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : 'info') }}">
            <h5>
                <i class="fas fa-{{ $applicant->status === 'admitted' ? 'check-circle' : ($applicant->status === 'rejected' ? 'times-circle' : 'clock') }} me-2"></i>
                Application Status:
                <span class="badge bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : 'warning') }}">
                    {{ strtoupper($applicant->status) }}
                </span>
            </h5>
            <p class="mb-0">Application Number: <strong>{{ $applicant->application_number }}</strong></p>
        </div>
    </div>
</div>

<div class="row">
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
                <h5>Make Payment</h5>
                <p class="text-muted">Pay your acceptance fee to secure admission</p>
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
        <table class="table">
            <tr>
                <td><strong>Matric Number:</strong></td>
                <td>{{ $applicant->matric_number }}</td>
            </tr>
            <tr>
                <td><strong>Department:</strong></td>
                <td>{{ $applicant->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Programme:</strong></td>
                <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
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
@endsection