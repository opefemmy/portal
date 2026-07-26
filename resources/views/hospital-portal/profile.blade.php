@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<style>
    .portal-page {
        background: url("{{ asset('uploads/backgrounds/login-bg.png') }}") no-repeat center center fixed !important;
        background-size: cover !important;
        min-height: 100vh;
        padding: 20px 0;
    }
    .portal-card-custom {
        background: white !important;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
</style>
<div class="portal-page">
<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm portal-card-custom">
                <div class="card-header bg-dark text-white py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-user-cog me-2"></i>Edit Profile
                    </h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('patient-portal.profile.update') }}">
                        @csrf
                        @method('PUT')

                        <h6 class="fw-bold text-danger mb-3">Personal Information</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" value="{{ $patient->first_name }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" value="{{ $patient->last_name }}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control" value="{{ $patient->phone }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ $patient->email }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="male" {{ $patient->gender == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ $patient->gender == 'female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" value="{{ $patient->date_of_birth ? $patient->date_of_birth->format('Y-m-d') : '' }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ $patient->address }}</textarea>
                        </div>

                        <h6 class="fw-bold text-danger mb-3 mt-4">Medical Information</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select name="blood_group" class="form-select">
                                    <option value="">Select</option>
                                    <option value="A+" {{ $patient->blood_group == 'A+' ? 'selected' : '' }}>A+</option>
                                    <option value="A-" {{ $patient->blood_group == 'A-' ? 'selected' : '' }}>A-</option>
                                    <option value="B+" {{ $patient->blood_group == 'B+' ? 'selected' : '' }}>B+</option>
                                    <option value="B-" {{ $patient->blood_group == 'B-' ? 'selected' : '' }}>B-</option>
                                    <option value="AB+" {{ $patient->blood_group == 'AB+' ? 'selected' : '' }}>AB+</option>
                                    <option value="AB-" {{ $patient->blood_group == 'AB-' ? 'selected' : '' }}>AB-</option>
                                    <option value="O+" {{ $patient->blood_group == 'O+' ? 'selected' : '' }}>O+</option>
                                    <option value="O-" {{ $patient->blood_group == 'O-' ? 'selected' : '' }}>O-</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Genotype</label>
                                <select name="genotype" class="form-select">
                                    <option value="">Select</option>
                                    <option value="AA" {{ $patient->genotype == 'AA' ? 'selected' : '' }}>AA</option>
                                    <option value="AS" {{ $patient->genotype == 'AS' ? 'selected' : '' }}>AS</option>
                                    <option value="SS" {{ $patient->genotype == 'SS' ? 'selected' : '' }}>SS</option>
                                    <option value="AC" {{ $patient->genotype == 'AC' ? 'selected' : '' }}>AC</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Allergies</label>
                            <textarea name="allergies" class="form-control" rows="2">{{ $patient->allergies }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chronic Conditions</label>
                            <textarea name="chronic_conditions" class="form-control" rows="2">{{ $patient->chronic_conditions }}</textarea>
                        </div>

                        <h6 class="fw-bold text-danger mb-3 mt-4">Emergency Contact</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Name</label>
                                <input type="text" name="emergency_contact_name" class="form-control" value="{{ $patient->emergency_contact_name }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" name="emergency_contact_phone" class="form-control" value="{{ $patient->emergency_contact_phone }}">
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <a href="{{ route('patient-portal.dashboard') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
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
