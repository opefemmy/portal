@extends('layouts.app')

@section('title', 'Registration Successful')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-success text-white py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>Registration Successful!
                    </h4>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-4">
                        <i class="fas fa-user-check fa-4x text-success"></i>
                    </div>

                    <h5 class="mb-3">Welcome, {{ $patient->full_name }}!</h5>

                    <p class="text-muted">
                        Your registration was successful. Please save your access code below - you will need it to login to the patient portal.
                    </p>

                    <div class="alert alert-warning">
                        <h6 class="alert-heading">⚠️ Important - Save Your Details</h6>
                        <hr>
                        <div class="row text-start">
                            <div class="col-6">
                                <small class="text-muted">Patient Number:</small><br>
                                <strong class="h4 text-primary">{{ $patient->patient_number }}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Access Code:</small><br>
                                <strong class="h4 text-danger">{{ $patient->access_code }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Your access code expires on <strong>{{ $patient->access_code_expires_at->format('d M Y') }}</strong>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <a href="{{ route('patient-portal.login') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Portal
                        </a>
                        <a href="{{ route('patient-portal.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
