@extends('layouts.app')

@section('title', 'Student Dashboard')

@php
$scrollingMessage = \App\Models\Setting::get('scrolling_message');
$loginNotification = session('login_notification');
$showPopup = session('show_popup');
$popupMessage = session('popup_message');
$user = auth()->user();
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
    <a href="{{ route('student.profile') }}">Click here to complete it.</a>
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
@if(session('success') && str_contains(session('success'), 'Welcome'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-hand-sparkles me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Welcome Banner with Passport --}}
<div class="card mb-4" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border: none; border-radius: 15px;">
    <div class="card-body" style="color: white;">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                @if($user->passport)
                    <img src="{{ asset('uploads/passports/' . $user->passport) }}" alt="Passport"
                         class="rounded-circle border border-4 border-white shadow-lg"
                         style="width: 120px; height: 120px; object-fit: cover;">
                @else
                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-lg"
                         style="width: 120px; height: 120px; margin: 0 auto;">
                        <i class="fas fa-user fa-3x text-primary"></i>
                    </div>
                @endif
            </div>
            <div class="col-md-10">
                <h1 class="mb-2 fw-bold" style="color: white;">
                    <i class="fas fa-hand-sparkles me-2"></i>Welcome, {{ $user->name }}!
                </h1>
                <h4 class="mb-3" style="color: white;">
                    @if($student)
                        <span class="badge bg-warning text-dark fs-6">{{ $student->matric_number }}</span>
                        <span class="mx-2">|</span>
                        <span>{{ $student->department->name ?? 'N/A' }}</span>
                        <span class="mx-2">|</span>
                        {{--
                            Level rendering: students.students.level holds the
                            numeric digit (1, 2, 3, 4) — the user-facing
                            string is "{n}00 Level" (e.g. 1 → "100 Level").
                            The previous "Level {{ $student->level }}00"
                            rendered "00 Level" when level was NULL or 0,
                            because Blade concatenated the literal "00"
                            after the (empty) value. Compute the actual
                            100-multiple and fall back to a default — the
                            legacy ND 1 ("100 Level") is the most common
                            for new students so use that as the fallback.
                        --}}
                        <span>Level {{ $student->level_display }}</span>
                        <span class="mx-2">|</span>
                        <span>{{ $student->session->name ?? '' }}</span>
                    @endif
                </h4>
                <p class="mb-0 fs-5" style="color: white;">You are free to explore yourself. Access all your academic information below.</p>
            </div>
        </div>
    </div>
</div>

@if(!$student)
<div class="alert alert-warning">
    <h5><i class="fas fa-exclamation-triangle me-2"></i>Profile Not Set Up</h5>
    <p class="mb-0">Your student profile has not been configured. Please contact the registry/administrator for assistance.</p>
</div>
@else

{{-- Personal-data stat tiles — read from the registry via
     DashboardResolver. Each closure inside registerStudentWidgets()
     scopes to auth()->user()->student so the counts are personal,
     not session-wide. --}}
@include('widgets.render', ['widgets' => $widgets])

{{-- Fees Section --}}
@if($unpaidFees->count() > 0)
<div class="card mt-4 border-warning">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Fees to Pay</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Fee Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($unpaidFees as $fee)
                    <tr>
                        <td><strong>{{ $fee->name }}</strong></td>
                        <td>{{ $fee->description }}</td>
                        <td><span class="text-success">₦{{ number_format($fee->amount, 2) }}</span></td>
                        <td>
                            <a href="{{ route('student.payments.pay', $fee) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-credit-card me-1"></i>Pay
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@elseif($fees->count() > 0)
<div class="card mt-4 border-success">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Fees Status</h5>
    </div>
    <div class="card-body">
        <p class="mb-0"><i class="fas fa-check me-2"></i>All fees have been paid for this session!</p>
    </div>
</div>
@endif

@php
    $studentRow = \App\Models\Student::where('user_id', auth()->id())->first();
    $paidPercent = $studentRow ? \App\Services\SchoolFeeCalculator::maxPercentPaidAcrossRequiredFees($studentRow) : 0;
    // Tuition gate — the binary "have you paid tuition at all" check
    // that the three downstream controllers (ExamClearance, CourseRegistration,
    // Result) enforce. Distinct from $paidPercent: a student can have
    // $paidPercent = 100 (paid all their department fees in full) without
    // ever paying tuition if those fees were application / acceptance /
    // compulsory. Those purposes don't unlock exam clearance / course form
    // / result surfaces, and the badge here must reflect that.
    $tuitionPaid = $studentRow
        ? \App\Services\SchoolFeeCalculator::hasPaidTuition($studentRow)
        : false;
@endphp
@include('partials.student.tuition-badge', [
    'student'     => $studentRow,
    'paidPercent' => $paidPercent,
    'tuitionPaid' => $tuitionPaid,
])

<div class="card mt-4">
    <div class="card-header">
        <h5>Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Course registration is gated by tuition: an unpaid student
                 clicking this link lands on the courses list, but every
                 action under it (register, print form) redirects them back
                 to /student/payments. Disable the button up front so the
                 gate matches what the controller enforces. --}}
            <div class="col-md-4">
                @if($tuitionPaid)
                    <a href="{{ route('student.courses') }}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-book me-2"></i>My Courses
                    </a>
                @else
                    <button type="button" disabled
                            class="btn btn-outline-secondary w-100"
                            title="Pay tuition first to unlock course registration">
                        <i class="fas fa-lock me-2"></i>My Courses <span class="badge bg-danger ms-2">Tuition required</span>
                    </button>
                @endif
            </div>
            {{-- Result checker is gated by tuition — same logic. --}}
            <div class="col-md-4">
                @if($tuitionPaid)
                    <a href="{{ route('student.results') }}" class="btn btn-outline-success w-100">
                        <i class="fas fa-chart-line me-2"></i>My Results
                    </a>
                @else
                    <button type="button" disabled
                            class="btn btn-outline-secondary w-100"
                            title="Pay tuition first to view results">
                        <i class="fas fa-lock me-2"></i>My Results <span class="badge bg-danger ms-2">Tuition required</span>
                    </button>
                @endif
            </div>
            <div class="col-md-4">
                <a href="{{ route('student.payments') }}" class="btn btn-outline-warning w-100">
                    <i class="fas fa-dollar-sign me-2"></i>My Payments
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('student.medical.index') }}" class="btn btn-outline-danger w-100">
                    <i class="fas fa-hospital me-2"></i>Medical/Health
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('student.complaints') }}" class="btn btn-outline-info w-100">
                    <i class="fas fa-exclamation-circle me-2"></i>Complaints
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('student.timetable') }}" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-calendar me-2"></i>Timetable
                </a>
            </div>
            {{-- Admission Letter reprint — only when this student row is
                 linked to an applicant who was admitted AND has paid the
                 acceptance fee. Otherwise the button hides so we don't
                 403 on click. The same gates the controller enforces. --}}
            @php
                $applicantForLetter = $studentRow?->applicant_id
                    ? \App\Models\Applicant::find($studentRow->applicant_id)
                    : null;
            @endphp
            @if($applicantForLetter
                && $applicantForLetter->status === 'admitted'
                && $applicantForLetter->hasPaid(\App\Models\PaymentType::PURPOSE_ACCEPTANCE))
                <div class="col-md-4">
                    <a href="{{ route('student.admission-letter') }}" target="_blank"
                       class="btn btn-outline-dark w-100">
                        <i class="fas fa-envelope-open-text me-2"></i>Admission Letter
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Post-Login Popup Modal --}}
@if($showPopup && $popupMessage)
<div class="modal fade" id="postLoginPopup" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white;">
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