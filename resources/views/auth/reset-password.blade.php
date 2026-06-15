@extends('layouts.app')

@section('title', 'Reset Password')

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

    .login-body {
        padding: 40px;
    }
</style>

<div class="login-page">
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-lock institution-logo" style="font-size: 3rem; color: white; margin-bottom: 10px;"></i>
            <h3>Set New Password</h3>
            <p style="color: rgba(255,255,255,0.8);">Enter your new password</p>
        </div>

        <div class="login-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(session('info'))
                <div class="alert alert-info">{{ session('info') }}</div>
            @endif

            <form method="POST" action="{{ route('password.reset') }}">
                @csrf

                <div class="mb-4">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                           id="password" name="password" required minlength="8">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control"
                           id="password_confirmation" name="password_confirmation" required>
                </div>

                <button type="submit" class="btn btn-login" style="background: linear-gradient(135deg, #1a237e, #6a1b9a); border: none; border-radius: 8px; padding: 12px; font-weight: 600; color: white; width: 100%;">
                    <i class="fas fa-save me-2"></i>Reset Password
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" style="color: #1a237e; text-decoration: none;">Back to Login</a>
            </div>
        </div>
    </div>
</div>
@endsection