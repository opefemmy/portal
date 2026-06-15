@extends('layouts.app')

@section('title', 'Applicant Dashboard')

@section('content')
<div class="page-header">
    <h4>Applicant Dashboard</h4>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-body">
                @if($applicant)
                <h5>Application Status:
                    <span class="badge bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : 'warning') }}">
                        {{ ucfirst($applicant->status) }}
                    </span>
                </h5>
                <p>Application Number: {{ $applicant->application_number }}</p>
                <a href="{{ route('applicant.application') }}" class="btn btn-primary">View Application</a>
                @else
                <p>You haven't submitted an application yet.</p>
                <a href="{{ route('applicant.apply') }}" class="btn btn-primary">Apply Now</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection