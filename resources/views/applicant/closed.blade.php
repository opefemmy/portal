@extends('layouts.app')

@section('title', 'Application Closed')

@section('content')
<div class="page-header">
    <h4>Application Closed</h4>
</div>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-lock fa-3x text-danger mb-3"></i>
        <h3>Application Portal Closed</h3>
        @if(isset($applicant))
            <p class="lead">Dear <strong>{{ $applicant->first_name ?? 'Applicant' }} {{ $applicant->last_name ?? '' }}</strong>,</p>
            <p>We regret to inform you that the application portal for the current admission cycle is now closed.</p>
            @if(isset($applicant->application_number))
                <p>Your application reference number is: <strong>{{ $applicant->application_number }}</strong></p>
            @endif
        @else
            <p class="lead">Dear Applicant,</p>
            <p>We regret to inform you that the application portal for the current admission cycle is now closed.</p>
        @endif
        <hr>
        <div class="mt-4">
            <h5>Need Help?</h5>
            <p class="mb-1">For inquiries, please contact the admissions office:</p>
            <p class="mb-1"><i class="fas fa-envelope"></i> admissions@portal.example</p>
            <p class="mb-1"><i class="fas fa-phone"></i> +234-XXX-XXX-XXXX</p>
        </div>
        <div class="mt-4">
            <a href="{{ url('/') }}" class="btn btn-primary">Back to Home</a>
        </div>
    </div>
</div>
@endsection
