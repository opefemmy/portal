@extends('layouts.app')

@section('title', 'Login')

@section('content')
<style>
    .login-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1a237e 0%, #0d1442 50%, #6a1b9a 100%);
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
        background: linear-gradient(135deg, #1a237e, #6a1b9a);
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
        border-color: #1a237e;
        box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
    }

    .btn-login {
        background: linear-gradient(135deg, #1a237e, #6a1b9a);
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
        box-shadow: 0 5px 20px rgba(26, 35, 126, 0.4);
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
        color: #1a237e;
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
            <i class="fas fa-university institution-logo"></i>
            <h3>EKSCOTECH Portal</h3>
            <p>Sign in to continue</p>
        </div>

        <div class="login-body">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}"
                               placeholder="Enter your email" required autofocus>
                    </div>
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
                <a href="{{ route('register') }}">Create Student Account</a>
                <span class="mx-2">|</span>
                <a href="{{ route('applicant.register') }}">Apply Now</a>
            </div>
        </div>
    </div>
</div>
@endsection