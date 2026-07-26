@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-user me-2"></i>My Profile</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Patient Number:</strong> {{ $patient->patient_number }}</p>
                <p><strong>Full Name:</strong> {{ $patient->full_name }}</p>
                <p><strong>Phone:</strong> {{ $patient->phone }}</p>
                <p><strong>Email:</strong> {{ $patient->email ?? 'Not provided' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Gender:</strong> {{ ucfirst($patient->gender ?? 'Not set' }}</p>
                <p><strong>Age:</strong> {{ $patient->age ?? 'Not set' }}</p>
                <p><strong>Address:</strong> {{ $patient->address ?? 'Not provided' }}</p>
                <p><strong>Last Login:</strong> {{ $patient->last_login_at ? $patient->last_login_at->format('d M Y, h:i A') : 'First time' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-warning">
        <h5 class="mb-0"><i class="fas fa-key me-2"></i>Access Code Management</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h6>Your Current Access Code: <strong>{{ $patient->access_code ?? 'Not generated' }}</strong></h6>
            <p class="mb-0">Expires: {{ $patient->access_code_expires_at ? $patient->access_code_expires_at->format('d M Y') : 'N/A' }}</p>
        </div>
        <form method="POST" action="{{ route('patient.regenerate-code') }}">
            @csrf
            <button type="submit" class="btn btn-warning" onclick="return confirm('Generate new code? Current code will stop working.')">
                <i class="fas fa-sync me-2"></i>Generate New Access Code
            </button>
        </form>
        <small class="text-muted">New code will be valid for 30 days</small>
    </div>
</div>

<div class="card">
    <div class="card-header bg-danger">
        <h5 class="mb-0"><i class="fas fa-hospital me-2"></i>Hospital Information</h5>
    </div>
    <div class="card-body">
        <p><strong>Blood Group:</strong> {{ $patient->blood_group ?? 'Not recorded' }}</p>
        <p><strong>Genotype:</strong> {{ $patient->genotype ?? 'Not recorded' }}</p>
        <p><strong>Allergies:</strong> {{ $patient->allergies ?? 'None recorded' }}</p>
        <p><strong>Chronic Conditions:</strong> {{ $patient->chronic_conditions ?? 'None' }}</p>
    </div>
</div>
@endsection
