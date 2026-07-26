@extends('layouts.app')

@section('title', 'EKSCOTECH Hospital Patient Portal')

@push('styles')
<style>
    .portal-page {
        background: url('{{ asset('uploads/backgrounds/login-bg.png') }}') no-repeat center center fixed;
        background-size: cover;
        min-height: 100vh;
        padding-bottom: 50px;
    }
    .portal-header {
        padding: 40px 20px;
        margin-bottom: 30px;
    }
    .portal-header h1 {
        color: #247D57 !important;
        text-shadow: 2px 2px 4px rgba(255,255,255,0.8);
        font-weight: 800 !important;
    }
    .portal-header p {
        color: #333 !important;
        font-weight: 500;
    }
    .portal-card {
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .portal-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
    }
    .portal-icon {
        width: 100px !important;
        height: 100px !important;
    }
    .feature-icon {
        width: 60px !important;
        height: 60px !important;
    }
    .portal-features .col-md-6 {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .portal-features h5 {
        color: #247D57 !important;
        font-weight: 700 !important;
    }
    .portal-features p {
        color: #666 !important;
    }
</style>
@endpush

@section('content')
<div class="portal-page">
<!-- Header Banner -->
<div class="portal-header text-center">
    <div class="container">
        @php
        $logo = \App\Models\SystemSetting::get('institution_logo');
        @endphp
        @if($logo)
            <img src="{{ asset('storage/' . $logo) }}" alt="Logo" style="max-height: 70px; margin-bottom: 15px;">
        @else
            <i class="fas fa-hospital fa-4x mb-3" style="color: #247D57;"></i>
        @endif
        <h1 class="display-3 fw-bold">EKSCOTECH Hospital Patient Portal</h1>
        <p class="lead fs-4">Access your health records, appointments, and payments online</p>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <!-- Quick Actions -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-lg portal-card">
                        <div class="card-body text-center p-5">
                            <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-4 portal-icon">
                                <i class="fas fa-user-plus fa-3x text-danger"></i>
                            </div>
                            <h4 class="card-title fw-bold">New Patient?</h4>
                            <p class="card-text text-muted mb-4">Register to get your unique access code</p>
                            <a href="{{ route('patient-portal.register') }}" class="btn btn-danger btn-lg px-5">
                                <i class="fas fa-user-plus me-2"></i>Register Now
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-lg portal-card">
                        <div class="card-body text-center p-5">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-4 portal-icon">
                                <i class="fas fa-sign-in-alt fa-3x text-primary"></i>
                            </div>
                            <h4 class="card-title fw-bold">Already Registered?</h4>
                            <p class="card-text text-muted mb-4">Login with your patient number and access code</p>
                            <a href="{{ route('patient-portal.login') }}" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-lg portal-card">
                        <div class="card-body text-center p-5">
                            <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-4 portal-icon">
                                <i class="fas fa-credit-card fa-3x text-success"></i>
                            </div>
                            <h4 class="card-title fw-bold">Quick Payment</h4>
                            <p class="card-text text-muted mb-4">Make payment without registration</p>
                            <button type="button" class="btn btn-success btn-lg px-4" data-bs-toggle="modal" data-bs-target="#hospitalPaymentModal">
                                <i class="fas fa-credit-card me-2"></i>Pay Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="row g-4 mt-5 portal-features">
                <div class="col-md-6">
                    <div class="d-flex align-items-start p-4 bg-white rounded shadow-sm">
                        <div class="bg-danger bg-opacity-10 rounded p-3 me-3 feature-icon d-flex align-items-center justify-content-center">
                            <i class="fas fa-calendar-check fa-2x text-danger"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold">Book Appointments</h5>
                            <p class="text-muted mb-0">Schedule appointments with our doctors online</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <div class="bg-primary bg-opacity-10 rounded p-3 me-3">
                            <i class="fas fa-file-medical text-primary"></i>
                        </div>
                        <div>
                            <h5>View Medical Records</h5>
                            <p class="text-muted mb-0">Access your medical history and prescriptions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <div class="bg-success bg-opacity-10 rounded p-3 me-3">
                            <i class="fas fa-receipt text-success"></i>
                        </div>
                        <div>
                            <h5>Track Payments</h5>
                            <p class="text-muted mb-0">View all your payment history and receipts</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <div class="bg-warning bg-opacity-10 rounded p-3 me-3">
                            <i class="fas fa-bell text-warning"></i>
                        </div>
                        <div>
                            <h5>Get Notifications</h5>
                            <p class="text-muted mb-0">Receive updates about your appointments</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
