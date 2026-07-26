@extends('layouts.app')

@section('title', 'Patient Login')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-sign-in-alt me-2"></i>Patient Portal Login
                    </h4>
                </div>
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <p class="text-muted mb-4">
                        Enter your patient number and access code to access your portal.
                    </p>

                    <form method="POST" action="{{ route('patient-portal.login.post') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Patient Number <span class="text-danger">*</span></label>
                            <input type="text" name="patient_number" class="form-control @error('patient_number') is-invalid @enderror"
                                   value="{{ old('patient_number') }}" placeholder="e.g., EXT20260001" required>
                            @error('patient_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Access Code <span class="text-danger">*</span></label>
                            <input type="text" name="access_code" class="form-control @error('access_code') is-invalid @enderror"
                                   placeholder="Enter your access code" required>
                            @error('access_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                            <a href="{{ route('patient-portal.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Portal
                            </a>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="text-muted mb-2">New patient?</p>
                        <a href="{{ route('patient-portal.register') }}" class="btn btn-outline-danger">
                            <i class="fas fa-user-plus me-2"></i>Register Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
