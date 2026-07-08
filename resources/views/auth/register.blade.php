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
    .info-box {
        background: #f8f9fa;
        border-left: 4px solid #1a237e;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    .info-box h5 { color: #1a237e; margin-bottom: 10px; }
    .info-box p { margin-bottom: 0; color: #666; font-size: 14px; }
</style>

<div class="register-page">
    <div class="register-card">
        <div class="register-header">
            <h3 class="text-white">Student Registration</h3>
            <p class="text-white-50">Create your student account</p>
        </div>
        <div class="register-body">
            <div class="info-box">
                <h5><i class="fas fa-info-circle me-2"></i>Self-Registration Disabled</h5>
                <p>Student self-registration has been disabled. Please use the login credentials provided by the school administration.</p>
                <p class="mb-0">If you don't have login credentials, please contact your department or the IT office.</p>
            </div>

            <div class="text-center">
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                </a>
            </div>

            <hr class="my-4">

            <div class="text-center">
                <p class="text-muted mb-2">Want to apply for admission?</p>
                <a href="{{ route('applicant.register') }}" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-2"></i>Apply Now
                </a>
            </div>
        </div>
    </div>
</div>
@endsection