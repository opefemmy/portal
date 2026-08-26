{{--
    Tuition status badge + CTA bar for the student dashboard.

    Inputs:
      $student        — App\Models\Student (the auth'd user's student row)
      $paidPercent    — int, max percent_paid across the student's required fees
      $tuitionPaid    — bool, SchoolFeeCalculator::hasPaidTuition($student)

    The badge copy and the Exam Clearance CTA both key off $tuitionPaid,
    NOT $paidPercent. The two diverge: a student can have $paidPercent=100
    (every required fee row paid in full) yet $tuitionPaid=false, when the
    fees they paid were application/acceptance/compulsory (which don't
    unlock exam clearance or course registration). Mirrors the gates the
    ExamClearanceController / CourseRegistrationController / ResultController
    enforce — keeping the dashboard in lock-step with the controller.
--}}
@php
    $paymentBadge = match(true) {
        $tuitionPaid && $paidPercent >= 100 => ['success', '100% paid', 'Both semesters + exam clearance enabled.'],
        $tuitionPaid && $paidPercent >= 60  => ['warning', '60% paid', 'First semester enabled. Pay the 40% balance to unlock second semester.'],
        $tuitionPaid                        => ['info',    'Tuition paid', 'Course registration + exam clearance unlocked.'],
        default                             => ['danger',  'School fee unpaid', 'Course registration, exam clearance and result checker are locked until you pay tuition.'],
    };
@endphp
<div class="card mt-4 border-{{ $paymentBadge[0] }}">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <span class="badge bg-{{ $paymentBadge[0] }} mb-2">{{ $paymentBadge[1] }}</span>
            <div class="text-muted small">{{ $paymentBadge[2] }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student.payments') }}" class="btn btn-outline-{{ $paymentBadge[0] }}">
                <i class="fas fa-dollar-sign me-1"></i>Pay Fees
            </a>
            @if($tuitionPaid)
                <a href="{{ route('student.exam-clearance') }}" class="btn btn-{{ $paymentBadge[0] }}">
                    <i class="fas fa-file-alt me-1"></i>Exam Clearance
                </a>
            @endif
        </div>
    </div>
</div>
