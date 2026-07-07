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
<div class="card">
    <div class="card-body">
        <h5>Application Details</h5>
        <table class="table">
            <tr>
                <th>Application Number:</th>
                <td>{{ $applicant->application_number }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span class="badge bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : 'warning') }}">
                        {{ ucfirst($applicant->status) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>School:</th>
                <td>{{ $applicant->school->name ?? 'N/A' }}</td>
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
                <th>Session:</th>
                <td>{{ $applicant->session->name ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>
</div>
@endif
@endsection