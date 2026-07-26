@extends('layouts.app')

@section('title', 'Patient Portal Login')

@section('content')
<style>
    .patient-login {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #dc3545 0%, #a71d2a 50%, #1a237e 100%);
    }
    .login-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        max-width: 450px;
        width: 100%;
    }
    .login-header {
        background: linear-gradient(135deg, #dc3545, #b21f3d);
        padding: 30px;
        text-align: center;
        border-radius: 15px 15px 0 0;
    }
</style>

<div class="patient-login">
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-hospital-user fa-3x mb-3" style="color: white;"></i>
            <h3 class="text-white">Patient Portal</h3>
            <p class="text-white-50">Enter your unique access code to view your records</p>
        </div>
        <div class="card-body p-4">
            @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('patient.login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Access Code</label>
                    <input type="text" name="access_code" class="form-control form-control-lg text-center"
                           placeholder="e.g., ABC12345"
                           required autofocus
                           style="letter-spacing: 3px; font-weight: bold;">
                    <small class="text-muted">Get your code from the hospital reception</small>
                </div>
                <button type="submit" class="btn btn-danger btn-lg w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Access My Portal
                </button>
            </form>

            <hr>
            <div class="text-center">
                <p class="text-muted mb-2">Need medical services?</p>
                <a href="{{ url('/login') }}" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-2"></i>Register as New Patient
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
