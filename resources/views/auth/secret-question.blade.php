@extends('layouts.app')

@section('title', 'Secret Question')

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
            <i class="fas fa-shield-alt institution-logo" style="font-size: 3rem; color: white; margin-bottom: 10px;"></i>
            <h3>Security Verification</h3>
            <p style="color: rgba(255,255,255,0.8);">Answer your secret question to proceed</p>
        </div>

        <div class="login-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('password.verify-secret') }}">
                @csrf

                <div class="mb-4">
                    <label class="form-label">Secret Question</label>
                    <div class="alert alert-info">
                        <strong>{{ $user->secret_question }}</strong>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="secret_answer" class="form-label">Your Answer</label>
                    <input type="text" class="form-control @error('secret_answer') is-invalid @enderror"
                           id="secret_answer" name="secret_answer" required autofocus>
                    @error('secret_answer')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-login" style="background: linear-gradient(135deg, #1a237e, #6a1b9a); border: none; border-radius: 8px; padding: 12px; font-weight: 600; color: white; width: 100%;">
                    <i class="fas fa-check me-2"></i>Verify Answer
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="{{ route('password.forgot') }}" style="color: #1a237e; text-decoration: none;">Start Over</a>
            </div>
        </div>
    </div>
</div>
@endsection