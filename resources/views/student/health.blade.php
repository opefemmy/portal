@extends('layouts.app')

@section('title', 'Health Portal')

@section('content')
<div class="page-header"><h4><i class="fas fa-hospital me-2"></i>Health Portal</h4></div>

<div class="row">
    <div class="col-md-4 mb-3">
        <a href="{{ route('student.medical.book') }}" class="card text-center p-4 text-decoration-none shadow-sm h-100">
            <i class="fas fa-calendar-plus fa-3x text-primary mb-3"></i>
            <strong>Book Appointment</strong>
            <small class="text-muted">Schedule a clinic visit</small>
        </a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="{{ route('student.medical.appointments') }}" class="card text-center p-4 text-decoration-none shadow-sm h-100">
            <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
            <strong>My Appointments</strong>
            <small class="text-muted">{{ $upcoming ?? 0 }} upcoming</small>
        </a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="{{ route('student.medical.history') }}" class="card text-center p-4 text-decoration-none shadow-sm h-100">
            <i class="fas fa-file-medical fa-3x text-info mb-3"></i>
            <strong>Medical History</strong>
            <small class="text-muted">Past visits & diagnoses</small>
        </a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="{{ route('student.medical.prescriptions') }}" class="card text-center p-4 text-decoration-none shadow-sm h-100">
            <i class="fas fa-prescription fa-3x text-warning mb-3"></i>
            <strong>Prescriptions</strong>
            <small class="text-muted">Active medications</small>
        </a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="{{ route('student.medical.lab-results') }}" class="card text-center p-4 text-decoration-none shadow-sm h-100">
            <i class="fas fa-vial fa-3x text-danger mb-3"></i>
            <strong>Lab Results</strong>
            <small class="text-muted">Tests & results</small>
        </a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="{{ route('student.medical.admissions') }}" class="card text-center p-4 text-decoration-none shadow-sm h-100">
            <i class="fas fa-procedures fa-3x text-secondary mb-3"></i>
            <strong>Admissions</strong>
            <small class="text-muted">In-patient records</small>
        </a>
    </div>
</div>
@endsection
