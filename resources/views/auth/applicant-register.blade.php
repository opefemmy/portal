@extends('layouts.app')

@section('title', 'Applicant Registration')

@section('content')
<style>
    .register-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 50%, var(--accent-wine) 100%);
    }
    .register-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        max-width: 500px;
        width: 100%;
    }
    .register-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        padding: 30px;
        text-align: center;
        border-radius: 15px 15px 0 0;
    }
    .register-body { padding: 40px; }
    .form-control { border-radius: 8px; padding: 12px; border: 2px solid #e9ecef; }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(36,125,87,0.1); }
    .btn-register { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border: none; border-radius: 8px; padding: 12px; font-weight: 600; color: white; width: 100%; }
    .btn-register:hover { background: linear-gradient(135deg, var(--primary-dark), var(--primary)); }
</style>

<div class="register-page">
    <div class="register-card">
        <div class="register-header">
            <h3 class="text-white">Applicant Registration</h3>
            <p class="text-white-50">Create your account to apply</p>
        </div>
        <div class="register-body">
            <form method="POST" action="{{ route('applicant.register') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Surname <span class="text-danger">*</span></label>
                    <input type="text" name="surname" class="form-control" value="{{ old('surname') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <button type="submit" class="btn-register">Create Account</button>
            </form>
            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="text-primary">Already have an account? Login</a>
            </div>
        </div>
    </div>
</div>
@endsection