@extends('layouts.app')

@section('title', 'Student Dashboard')

@php
$scrollingMessage = \App\Models\Setting::get('scrolling_message');
$loginNotification = session('login_notification');
$showPopup = session('show_popup');
$popupMessage = session('popup_message');
@endphp

@section('content')
{{-- Error Message --}}
@if(isset($error))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ $error }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Profile Incomplete Warning --}}
@if(isset($profileIncomplete) && $profileIncomplete && $student)
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>Your profile is incomplete.
    <a href="{{ route('student.profile.edit') }}">Click here to complete it.</a>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Scrolling Message --}}
@if($scrollingMessage)
<div class="alert alert-info mb-3 p-0" style="background: #0dcaf0; color: white;">
    <marquee class="py-2" behavior="scroll" direction="left">
        {{ $scrollingMessage }}
    </marquee>
</div>
@endif

{{-- Login Notification --}}
@if($loginNotification)
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ $loginNotification }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="page-header">
    <h4 class="mb-0">Student Dashboard</h4>
    <p class="text-muted mb-0">Welcome, {{ auth()->user()->name }}@if($student)<span class="mx-2">|</span>{{ $student->matric_number ?? 'N/A' }}@endif</p>
</div>

@if(!$student)
<div class="alert alert-warning">
    <h5><i class="fas fa-exclamation-triangle me-2"></i>Profile Not Set Up</h5>
    <p class="mb-0">Your student profile has not been configured. Please contact the registry/administrator for assistance.</p>
</div>
@else

<div class="row">
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card success h-100">
            <div class="card-body">
                <h6 class="text-muted">Registered Courses</h6>
                <h2>{{ $registeredCourses->count() }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card info h-100">
            <div class="card-body">
                <h6 class="text-muted">Total Payments</h6>
                <h2>{{ $payments->count() }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5>Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <a href="{{ route('student.courses') }}" class="btn btn-outline-primary w-100">
                    <i class="fas fa-book me-2"></i>My Courses
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('student.results') }}" class="btn btn-outline-success w-100">
                    <i class="fas fa-chart-line me-2"></i>My Results
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('student.payments') }}" class="btn btn-outline-warning w-100">
                    <i class="fas fa-dollar-sign me-2"></i>My Payments
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Post-Login Popup Modal --}}
@if($showPopup && $popupMessage)
<div class="modal fade" id="postLoginPopup" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a237e, #6a1b9a); color: white;">
                <h5 class="modal-title"><i class="fas fa-bell me-2"></i>Important Information</h5>
            </div>
            <div class="modal-body">
                {!! nl2br(e($popupMessage)) !!}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var popupModal = new bootstrap.Modal(document.getElementById('postLoginPopup'));
    popupModal.show();
});
</script>
@endif
@endif
@endsection