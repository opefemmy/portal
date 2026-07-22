@extends('layouts.app')

@section('title', 'Verify Email - EKSCOTECH Portal')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-5">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-envelope-check me-2"></i>
                        Verify Your Email Address
                    </h4>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-envelope-circle-check text-primary" style="font-size: 4rem;"></i>
                    </div>

                    <h5 class="mb-3">Thank you for registering!</h5>

                    <p class="text-muted mb-4">
                        We have sent a verification link to your email address:
                        <strong>{{ auth()->user()->email }}</strong>
                    </p>

                    <p>Please check your email and click the verification link to activate your account.</p>

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="mt-4">
                        <form method="POST" action="{{ route('verification.resend') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>
                                Resend Verification Email
                            </button>
                        </form>

                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Logout
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body bg-light">
                    <h6 class="text-muted mb-2">
                        <i class="fas fa-info-circle me-2"></i>
                        Didn't receive the email?
                    </h6>
                    <ul class="text-muted small mb-0">
                        <li>Check your spam/junk folder</li>
                        <li>Make sure your email address is correct</li>
                        <li>Wait a few minutes for the email to arrive</li>
                        <li>Contact the ICT department if the issue persists</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection