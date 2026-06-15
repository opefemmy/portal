@extends('layouts.app')

@section('title', 'Track Admission')

@section('content')
<div class="page-header">
    <h4>Track Admission Status</h4>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <label for="application_number" class="form-label">Application Number or Email</label>
                <input type="text" class="form-control" id="application_number" name="application_number"
                       value="{{ request('application_number') }}" placeholder="Enter your application number or email">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Track
                </button>
            </div>
        </form>
    </div>
</div>

@if(isset($applicant))
<div class="card">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Application Status</h5>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <tr>
                <th width="30%">Application Number:</th>
                <td>{{ $applicant->application_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Full Name:</th>
                <td>{{ $applicant->full_name ?? $applicant->user->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Email:</th>
                <td>{{ $applicant->email ?? $applicant->user->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Department:</th>
                <td>{{ $applicant->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Programme:</th>
                <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span class="badge bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'pending' ? 'warning' : ($applicant->status === 'rejected' ? 'danger' : 'info')) }}">
                        {{ strtoupper($applicant->status) }}
                    </span>
                </td>
            </tr>
            @if($applicant->status === 'admitted')
            <tr>
                <th>Matric Number:</th>
                <td>{{ $applicant->student->matric_number ?? 'To be assigned' }}</td>
            </tr>
            @endif
        </table>
    </div>
</div>
@elseif(request('application_number'))
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    No application found with that application number or email. Please check and try again.
</div>
@else
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Enter your application number or email address to check your admission status.
</div>
@endif
@endsection