@extends('layouts.app')

@section('title', 'Hospital Patient Portal')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold">
                    <i class="fas fa-hospital text-danger me-2"></i>Hospital Patient Portal
                </h1>
                <p class="lead text-muted">Access your health records, appointments, and payments online</p>
            </div>

            <!-- Quick Actions -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-user-plus fa-2x text-danger"></i>
                            </div>
                            <h5 class="card-title">New Patient?</h5>
                            <p class="card-text text-muted">Register to get your unique access code</p>
                            <a href="{{ route('patient-portal.register') }}" class="btn btn-danger">
                                <i class="fas fa-user-plus me-2"></i>Register Now
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-sign-in-alt fa-2x text-primary"></i>
                            </div>
                            <h5 class="card-title">Already Registered?</h5>
                            <p class="card-text text-muted">Login with your patient number and access code</p>
                            <a href="{{ route('patient-portal.login') }}" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-credit-card fa-2x text-success"></i>
                            </div>
                            <h5 class="card-title">Quick Payment</h5>
                            <p class="card-text text-muted">Make payment without registration</p>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#hospitalPaymentModal">
                                <i class="fas fa-credit-card me-2"></i>Pay Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="row g-4 mt-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <div class="bg-danger bg-opacity-10 rounded p-3 me-3">
                            <i class="fas fa-calendar-check text-danger"></i>
                        </div>
                        <div>
                            <h5>Book Appointments</h5>
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
@endsection
