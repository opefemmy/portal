@extends('layouts.app')

@section('title', 'Patient Registration')

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
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg portal-card-custom">
                <div class="card-header bg-danger text-white py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Patient Registration
                    </h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">
                        Register to get your unique access code. You can then use this code to access your portal,
                        track appointments, view medical records, and make payments.
                    </p>

                    <form method="POST" action="{{ route('patient-portal.register.store') }}"
                          autocomplete="off"
                          data-lpignore="true"
                          data-1p-ignore="true">
                        @csrf

                        <h6 class="fw-bold text-danger mb-3">Personal Information</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name"
                                       class="form-control @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name') }}"
                                       autocomplete="off"
                                       autocapitalize="words"
                                       autocorrect="off"
                                       spellcheck="false"
                                       data-lpignore="true"
                                       data-1p-ignore="true"
                                       required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name"
                                       class="form-control @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name') }}"
                                       autocomplete="off"
                                       autocapitalize="words"
                                       autocorrect="off"
                                       spellcheck="false"
                                       data-lpignore="true"
                                       data-1p-ignore="true"
                                       required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}"
                                       autocomplete="off"
                                       autocapitalize="off"
                                       autocorrect="off"
                                       spellcheck="false"
                                       inputmode="tel"
                                       data-lpignore="true"
                                       data-1p-ignore="true"
                                       required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}"
                                       autocomplete="off"
                                       autocapitalize="off"
                                       autocorrect="off"
                                       spellcheck="false"
                                       data-lpignore="true"
                                       data-1p-ignore="true">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select @error('gender') is-invalid @enderror"
                                        autocomplete="off">
                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth"
                                       class="form-control @error('date_of_birth') is-invalid @enderror"
                                       value="{{ old('date_of_birth') }}"
                                       autocomplete="off">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"
                                      autocomplete="off" autocorrect="off" spellcheck="false">{{ old('address') }}</textarea>
                        </div>

                        <h6 class="fw-bold text-danger mb-3 mt-4">Medical Information</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select name="blood_group" class="form-select">
                                    <option value="">Select</option>
                                    <option value="A+" {{ old('blood_group') == 'A+' ? 'selected' : '' }}>A+</option>
                                    <option value="A-" {{ old('blood_group') == 'A-' ? 'selected' : '' }}>A-</option>
                                    <option value="B+" {{ old('blood_group') == 'B+' ? 'selected' : '' }}>B+</option>
                                    <option value="B-" {{ old('blood_group') == 'B-' ? 'selected' : '' }}>B-</option>
                                    <option value="AB+" {{ old('blood_group') == 'AB+' ? 'selected' : '' }}>AB+</option>
                                    <option value="AB-" {{ old('blood_group') == 'AB-' ? 'selected' : '' }}>AB-</option>
                                    <option value="O+" {{ old('blood_group') == 'O+' ? 'selected' : '' }}>O+</option>
                                    <option value="O-" {{ old('blood_group') == 'O-' ? 'selected' : '' }}>O-</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Genotype</label>
                                <select name="genotype" class="form-select">
                                    <option value="">Select</option>
                                    <option value="AA" {{ old('genotype') == 'AA' ? 'selected' : '' }}>AA</option>
                                    <option value="AS" {{ old('genotype') == 'AS' ? 'selected' : '' }}>AS</option>
                                    <option value="SS" {{ old('genotype') == 'SS' ? 'selected' : '' }}>SS</option>
                                    <option value="AC" {{ old('genotype') == 'AC' ? 'selected' : '' }}>AC</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Allergies (if any)</label>
                            <textarea name="allergies" class="form-control" rows="2" placeholder="List any allergies...">{{ old('allergies') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chronic Conditions (if any)</label>
                            <textarea name="chronic_conditions" class="form-control" rows="2" placeholder="List any chronic conditions...">{{ old('chronic_conditions') }}</textarea>
                        </div>

                        <h6 class="fw-bold text-danger mb-3 mt-4">Emergency Contact</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Name</label>
                                <input type="text" name="emergency_contact_name" class="form-control"
                                       value="{{ old('emergency_contact_name') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" name="emergency_contact_phone" class="form-control"
                                       value="{{ old('emergency_contact_phone') }}">
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Register & Get Access Code
                            </button>
                            <a href="{{ route('patient-portal.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Portal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
