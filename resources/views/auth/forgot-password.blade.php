@extends('layouts.app')

@section('title', 'Forgot Password')

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
            <i class="fas fa-key institution-logo" style="font-size: 3rem; color: white; margin-bottom: 10px;"></i>
            <h3>Forgot Password</h3>
            <p style="color: rgba(255,255,255,0.8);">Enter your email to receive a password reset link</p>
        </div>

        <div class="login-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if(session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control @error('email') is-invalid @endif"
                           id="email" name="email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">We'll send a password reset link to this email</small>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" style="color: var(--primary); text-decoration: none;">Back to Login</a>
            </div>
        </div>
    </div>
</div>
@endsection
