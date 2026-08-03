@extends('layouts.app')

@section('title', 'Patient Login')

@section('content')
<style>
    .portal-page {
        background: url("{{ asset('uploads/backgrounds/login-bg.png') }}") no-repeat center center fixed !important;
        background-size: cover !important;
        min-height: 100vh;
        padding: 50px 0;
    }
    .portal-card-custom {
        background: white !important;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    .portal-card-custom h4 {
        font-weight: 700 !important;
        font-size: 1.3rem !important;
    }
    .portal-card-custom label {
        font-weight: 600 !important;
    }
    .portal-card-custom .btn {
        font-weight: 600 !important;
    }
</style>
<div class="portal-page">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card border-0 shadow-lg portal-card-custom">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-sign-in-alt me-2"></i>Patient Portal Login
                    </h4>
                </div>
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <p class="text-muted mb-4">
                        Enter your patient number and access code to access your portal.
                    </p>

                    <form method="POST" action="{{ route('patient-portal.login.post') }}"
                          autocomplete="off"
                          data-lpignore="true"
                          data-1p-ignore="true"
                          id="portalLoginForm">
                        @csrf

                        {{-- Honeypot for browsers/password-managers that try to
                             auto-fill a hidden username field. Real user data
                             never lands here. --}}
                        <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
                            <label>Username <input type="text" name="username" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Patient Number / Phone <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="patient_number"
                                   class="form-control patient-id-input @error('patient_number') is-invalid @enderror"
                                   value=""
                                   placeholder="e.g., EXT20260001 or 08012345678"
                                   autocomplete="off"
                                   autocapitalize="off"
                                   autocorrect="off"
                                   spellcheck="false"
                                   inputmode="text"
                                   data-lpignore="true"
                                   data-1p-ignore="true"
                                   readonly
                                   onfocus="this.removeAttribute('readonly');"
                                   required>
                            @error('patient_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Enter your Patient Number or Phone Number used during registration</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Access Code <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="access_code"
                                   class="form-control access-code-input @error('access_code') is-invalid @enderror"
                                   value=""
                                   placeholder="Enter your access code"
                                   autocomplete="new-password"
                                   autocapitalize="characters"
                                   autocorrect="off"
                                   spellcheck="false"
                                   inputmode="text"
                                   data-lpignore="true"
                                   data-1p-ignore="true"
                                   readonly
                                   onfocus="this.removeAttribute('readonly');"
                                   required>
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>For your security, browsers and password managers cannot save this code.
                            </small>
                            @error('access_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                            <a href="{{ route('patient-portal.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Portal
                            </a>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="text-muted mb-2">New patient?</p>
                        <a href="{{ route('patient-portal.register') }}" class="btn btn-outline-danger">
                            <i class="fas fa-user-plus me-2"></i>Register Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
/**
 * Defense-in-depth: even when a browser tries to autofill the form
 * (a remembered patient number, etc.) we want to wipe the value so
 * the user types fresh. This also defeats password managers that
 * ignore the data-lpignore attribute.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('portalLoginForm');
    if (!form) return;

    var inputs = form.querySelectorAll('input[type="text"]');
    inputs.forEach(function (input) {
        // Wipe any autofilled value the browser sneaked in.
        input.addEventListener('blur', function () {
            // Leave the user's typed value alone; only wipe on load.
        });
        input.addEventListener('animationstart', function (e) {
            // Chrome fires "onautocomplete" events as animationstart on
            // fields that get autofilled. Force-clear those.
            if (e.animationName === 'onautocomplete') {
                input.value = '';
            }
        });
    });

    // One-time wipe on page load in case the browser populated fields
    // before our readonly-on-focus JS could block it.
    setTimeout(function () {
        inputs.forEach(function (input) {
            // Only wipe if the field has the readonly attr (which our
            // onfocus handler removes once touched) — that's our signal
            // that the user hasn't interacted yet.
            if (input.hasAttribute('readonly')) {
                input.value = '';
            }
        });
    }, 100);
});
</script>
@endpush
@endsection
