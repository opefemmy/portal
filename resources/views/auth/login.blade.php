@extends('layouts.app')

@section('title', 'Login')

@php
use App\Models\SystemSetting;
$institutionName = SystemSetting::get('institution_name', 'Ekiti State College of Technology');
$institutionShortName = SystemSetting::get('institution_short_name', 'EKSCOTECH');
$institutionLogo = SystemSetting::get('institution_logo');
$institutionTagline = SystemSetting::get('institution_tagline', 'Staff, Student & Admin Login');
@endphp

@section('content')
<style>
    .login-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 50%, var(--accent-wine) 100%);
    }

    .login-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        overflow: hidden;
        max-width: 450px;
        width: 100%;
    }

    .login-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        padding: 30px;
        text-align: center;
    }

    .login-header h3 {
        color: white;
        margin: 0;
        font-weight: 600;
    }

    .login-header p {
        color: rgba(255,255,255,0.8);
        margin: 5px 0 0;
    }

    .role-badges {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 5px;
        margin-top: 10px;
    }

    .role-badges span {
        font-size: 10px;
        padding: 2px 8px;
        background: rgba(255,255,255,0.2);
        border-radius: 10px;
    }

    .login-body {
        padding: 40px;
    }

    .form-control {
        border-radius: 8px;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        transition: all 0.3s;
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(36, 125, 87, 0.1);
    }

    .btn-login {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border: none;
        border-radius: 8px;
        padding: 12px;
        font-weight: 600;
        color: white;
        width: 100%;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(36, 125, 87, 0.4);
    }

    .input-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .input-group input {
        padding-left: 40px;
    }

    .register-link {
        text-align: center;
        margin-top: 20px;
    }

    .register-link a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }

    .register-link a:hover {
        text-decoration: underline;
    }

    .institution-logo {
        font-size: 3rem;
        color: white;
        margin-bottom: 10px;
    }
</style>

<div class="login-page">
    <div class="login-card">
        <div class="login-header">
            @if($institutionLogo)
                <img src="{{ asset('storage/' . $institutionLogo) }}" alt="Logo" style="max-height: 60px; margin-bottom: 10px;">
            @else
                <i class="fas fa-university institution-logo"></i>
            @endif
            <h3>{{ $institutionShortName }} Portal</h3>
            <p>{{ $institutionTagline }}</p>
            <div class="role-badges">
                <span>Admin</span>
                <span>Lecturer</span>
                <span>Student</span>
                <span>Bursar</span>
                <span>Librarian</span>
                <span>Hospital</span>
            </div>
        </div>

        <div class="login-body">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Matric Number / Email</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user-graduate"></i></span>
                        <input type="text" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}"
                               placeholder="Enter matric number or email" required autofocus>
                    </div>
                    <small class="text-muted">Students: Use your matriculation number (e.g., ND/2024/001)</small>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password"
                               placeholder="Enter your password" required>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <div class="mb-4 text-end">
                    <a href="{{ route('password.forgot') }}" style="color: #1a237e; font-size: 0.9rem;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Sign In
                </button>
            </form>

            <div class="register-link">
                <p class="mb-2">Don't have an account?</p>
                <a href="{{ route('applicant.register') }}">Apply Now</a>
            </div>

            <div class="mt-3 text-center">
                <a href="{{ route('public.validate-payment') }}" class="text-muted">
                    <i class="fas fa-check-circle me-1"></i> Already made payment? Validate here
                </a>
            </div>
        </div>
    </div>
</div>
@endsection