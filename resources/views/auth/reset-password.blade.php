@extends('layouts.app')

@section('title', 'Reset Password')

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

    .login-body {
        padding: 40px;
    }
</style>

<div class="login-page">
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-lock institution-logo" style="font-size: 3rem; color: white; margin-bottom: 10px;"></i>
            <h3>Set New Password</h3>
            <p style="color: rgba(255,255,255,0.8);">Create a new password for your account</p>
        </div>

        <div class="login-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(isset($token))
            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email ?? request('email') }}">

                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" value="{{ $email ?? request('email') }}" readonly>
                    @if($errors->has('email'))
                        <div class="text-danger">{{ $errors->first('email') }}</div>
                    @endif
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @endif"
                           id="password" name="password" required minlength="8" autofocus>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @else
                        <small class="text-muted">Minimum 8 characters</small>
                    @endif
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control"
                           id="password_confirmation" name="password_confirmation" required>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-save me-2"></i>Reset Password
                </button>
            </form>
            @else
            <div class="alert alert-warning">
                Invalid or expired reset link. Please request a new password reset.
            </div>
            <div class="text-center">
                <a href="{{ route('password.forgot') }}" class="btn btn-primary">Request New Reset Link</a>
            </div>
            @endif

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" style="color: var(--primary); text-decoration: none;">Back to Login</a>
            </div>
        </div>
    </div>
</div>
@endsection
