@extends('layouts.app')

@section('title', 'Student Registration')

@section('content')
<style>
    .register-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1a237e 0%, #0d1442 50%, #6a1b9a 100%);
    }
    .register-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        max-width: 500px;
        width: 100%;
    }
    .register-header {
        background: linear-gradient(135deg, #1a237e, #6a1b9a);
        padding: 30px;
        text-align: center;
        border-radius: 15px 15px 0 0;
    }
    .register-body { padding: 40px; }
    .form-control { border-radius: 8px; padding: 12px; border: 2px solid #e9ecef; }
    .form-control:focus { border-color: #1a237e; box-shadow: 0 0 0 3px rgba(26,35,126,0.1); }
    .btn-register { background: linear-gradient(135deg, #1a237e, #6a1b9a); border: none; border-radius: 8px; padding: 12px; font-weight: 600; color: white; width: 100%; }
</style>

<div class="register-page">
    <div class="register-card">
        <div class="register-header">
            <h3 class="text-white">Student Registration</h3>
            <p class="text-white-50">Create your student account</p>
        </div>
        <div class="register-body">
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Matric Number (Optional)</label>
                    <input type="text" name="matric_number" class="form-control" placeholder="e.g., ND/2024/001">
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