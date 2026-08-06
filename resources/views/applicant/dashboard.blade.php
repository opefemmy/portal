@extends('layouts.app')

@section('title', 'Applicant Dashboard')

@section('content')
<div class="page-header">
    <h4>Applicant Dashboard</h4>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('info'))
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($applicant)
{{-- Admission Status Alert --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-3" style="border-color: {{ $applicant->status === 'admitted' ? '#198754' : ($applicant->status === 'rejected' ? '#dc3545' : '#0dcaf0') }};">
            <div class="card-header bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : 'warning') }} text-white">
                <h5 class="mb-0"><i class="fas fa-{{ $applicant->status === 'admitted' ? 'check-circle' : ($applicant->status === 'rejected' ? 'times-circle' : 'clock') }} me-2"></i>Admission Status: {{ strtoupper($applicant->status) }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Application Number:</strong></p>
                        <h4>{{ $applicant->application_number }}</h4>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Screening Status:</strong></p>
                        <h4>
                            @if($applicant->screening_status === 'passed')
                            <span class="badge bg-success">PASSED</span>
                            @elseif($applicant->screening_status === 'failed')
                            <span class="badge bg-danger">FAILED</span>
                            @else
                            <span class="badge bg-warning text-dark">PENDING</span>
                            @endif
                        </h4>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Admission Status:</strong></p>
                        <h4>
                            @if($applicant->status === 'admitted')
                            <span class="badge bg-success">ADMITTED</span>
                            @elseif($applicant->status === 'rejected')
                            <span class="badge bg-danger">REJECTED</span>
                            @else
                            <span class="badge bg-warning text-dark">UNDER PROCESSING</span>
                            @endif
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Action Cards --}}
<div class="row">
    {{-- Check Screening Result --}}
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Screening Result</h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-clipboard-check fa-3x text-info mb-3"></i>
                <p class="text-muted">Check your screening/examination result</p>
                @if($applicant->screening_status === 'passed')
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Congratulations! You passed the screening.
                </div>
                @elseif($applicant->screening_status === 'failed')
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>You did not meet the screening requirements.
                </div>
                @else
                <div class="alert alert-warning">
                    <i class="fas fa-clock me-2"></i>Screening result not yet released.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Check Admission Status --}}
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-graduation-cap me-2"></i>Admission Status</h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-user-graduation-cap fa-3x text-primary mb-3"></i>
                <p class="text-muted">Check your admission status</p>
                @if($applicant->status === 'admitted')
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i>ADMITTED!</h5>
                    <p class="mb-0">Congratulations on your admission. Please proceed to pay your acceptance fee.</p>
                </div>
                @elseif($applicant->status === 'rejected')
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>Your application was not successful.
                </div>
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Your application is being processed. Check back later.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Payment / Requery --}}
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Progress</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-file-alt me-2 text-primary"></i>Application Fee</span>
                        @if($applicant->hasPaid(\App\Models\PaymentType::PURPOSE_APPLICATION))
                            <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i> Paid</span>
                        @else
                            <span class="badge bg-warning text-dark rounded-pill">Pending</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-graduation-cap me-2 text-success"></i>Acceptance Fee</span>
                        @if($applicant->hasPaid(\App\Models\PaymentType::PURPOSE_ACCEPTANCE))
                            <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i> Paid</span>
                        @else
                            <span class="badge bg-secondary rounded-pill">Locked</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-user-graduate me-2 text-primary"></i>Compulsory Fee</span>
                        @if($applicant->hasPaid(\App\Models\PaymentType::PURPOSE_SCHOOL_FEE))
                            <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i> Paid</span>
                        @else
                            <span class="badge bg-secondary rounded-pill">Locked</span>
                        @endif
                    </li>
                </ul>

                @php $nextPurpose = $applicant->nextPayablePurpose(); @endphp
                @if($nextPurpose)
                    <a href="{{ route('applicant.payment.gateway') }}?purpose={{ $nextPurpose }}" class="btn btn-success w-100">
                        <i class="fas fa-credit-card me-2"></i>{{ $applicant->nextPayableLabel() }}
                    </a>
                    <a href="{{ url('/applicant/validate-payment') }}" class="btn btn-outline-primary btn-sm w-100 mt-2">
                        <i class="fas fa-check-circle me-1"></i>Validate Bank Transfer
                    </a>
                @else
                    <div class="alert alert-success mb-0 py-2 text-center">
                        <i class="fas fa-check-circle me-1"></i> All fees paid
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row mt-3">
    @php
        // View/print only become active once the applicant has actually
        // submitted the form (status flips from 'draft' to 'pending' on
        // submit). Before submission there is nothing worth viewing or
        // printing — the apply form is still the place to be.
        $canViewOrPrint = $applicant && !in_array($applicant->status, ['draft'], true);
    @endphp
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-file-alt fa-3x {{ $canViewOrPrint ? 'text-primary' : 'text-muted' }} mb-3"></i>
                <h5>View Application</h5>
                <p class="text-muted">
                    @if($canViewOrPrint)
                        View your submitted application details
                    @else
                        Available once you submit your application form
                    @endif
                </p>
                @if($canViewOrPrint)
                    <a href="{{ route('applicant.application') }}" class="btn btn-primary">
                        <i class="fas fa-eye me-2"></i>View
                    </a>
                @else
                    <button type="button" class="btn btn-outline-secondary" disabled aria-disabled="true">
                        <i class="fas fa-eye me-2"></i>View
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-print fa-3x {{ $canViewOrPrint ? 'text-secondary' : 'text-muted' }} mb-3"></i>
                <h5>Print Application</h5>
                <p class="text-muted">
                    @if($canViewOrPrint)
                        Print a copy of your application form
                    @else
                        Available once you submit your application form
                    @endif
                </p>
                @if($canViewOrPrint)
                    <a href="{{ route('applicant.application.print') }}" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-print me-2"></i>Print
                    </a>
                @else
                    <button type="button" class="btn btn-outline-secondary" disabled aria-disabled="true">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if($applicant->status === 'admitted')
    @php $acceptanceType = \App\Models\PaymentType::findByPurpose(\App\Models\PaymentType::PURPOSE_ACCEPTANCE); @endphp
    <div class="col-md-4 mb-3">
        <div class="card h-100 border-success">
            <div class="card-body text-center">
                <i class="fas fa-graduation-cap fa-3x text-success mb-3"></i>
                <h5>Accept Admission</h5>
                <p class="text-muted">Pay {{ $acceptanceType?->name ?: ($acceptanceType?->display_label ?? 'acceptance fee') }} to secure your admission</p>
                @if($applicant->hasPaid(\App\Models\PaymentType::PURPOSE_ACCEPTANCE))
                    <button class="btn btn-success w-100" disabled>
                        <i class="fas fa-check-circle me-2"></i>{{ $acceptanceType?->name ?: ($acceptanceType?->display_label ?? 'Acceptance Fee') }} Paid
                    </button>
                    <a href="{{ route('applicant.admission-letter') }}" class="btn btn-outline-success mt-2 w-100" target="_blank">
                        <i class="fas fa-print me-2"></i>Print Admission Letter
                    </a>
                @else
                    <a href="{{ route('applicant.payment.gateway') }}?purpose=acceptance" class="btn btn-success w-100">
                        <i class="fas fa-credit-card me-2"></i>Pay {{ $acceptanceType?->name ?: ($acceptanceType?->display_label ?? 'Acceptance Fee') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Migration-trigger fee (Compulsory / School Fees) — only after acceptance paid, triggers migration to student portal --}}
    @if($applicant->status === 'admitted' && $applicant->hasPaid(\App\Models\PaymentType::PURPOSE_ACCEPTANCE) && !$applicant->isMigrated())
        @php $migrationType = \App\Models\PaymentType::findByPurpose(\App\Models\PaymentType::PURPOSE_SCHOOL_FEE)
            ?? \App\Models\PaymentType::findByPurpose(\App\Models\PaymentType::PURPOSE_COMPULSORY)
            ?? \App\Models\PaymentType::findByPurpose(\App\Models\PaymentType::PURPOSE_SCHOOL_FEE_PRODUCTION); @endphp
    <div class="col-md-4 mb-3">
        <div class="card h-100 border-primary">
            <div class="card-body text-center">
                <i class="fas fa-user-graduate fa-3x text-primary mb-3"></i>
                <h5>Pay {{ $migrationType?->name ?: ($migrationType?->display_label ?? 'Compulsory Fee') }}</h5>
                <p class="text-muted">Complete your migration to the student portal and receive your matric number.</p>
                <a href="{{ route('applicant.payment.gateway') }}?purpose={{ $migrationType?->purpose ?? \App\Models\PaymentType::PURPOSE_SCHOOL_FEE }}" class="btn btn-primary w-100">
                    <i class="fas fa-credit-card me-2"></i>Pay {{ $migrationType?->name ?: ($migrationType?->display_label ?? 'Compulsory Fee') }}
                </a>
            </div>
        </div>
    </div>
    @endif

    @if($applicant->status === 'admitted' && $applicant->hasPaid(\App\Models\PaymentType::PURPOSE_ACCEPTANCE))
    <div class="col-md-4 mb-3">
        <div class="card h-100 border-success">
            <div class="card-body text-center">
                <i class="fas fa-file-signature fa-3x text-success mb-3"></i>
                <h5>Admission Letter</h5>
                <p class="text-muted">Print your official admission letter</p>
                <a href="{{ route('applicant.admission-letter') }}" class="btn btn-success" target="_blank">
                    <i class="fas fa-print me-2"></i>Print Admission Letter
                </a>
            </div>
        </div>
    </div>
    @endif

    @if($applicant->isMigrated())
    <div class="col-md-4 mb-3">
        <div class="card h-100 border-success">
            <div class="card-body text-center">
                <i class="fas fa-id-badge fa-3x text-success mb-3"></i>
                <h5>Student Portal</h5>
                <p class="text-muted">You are now a student. Continue to the student portal.</p>
                <a href="{{ route('student.dashboard') }}" class="btn btn-success w-100">
                    <i class="fas fa-arrow-right me-2"></i>Go to Student Portal
                </a>
            </div>
        </div>
    </div>
    @endif
</div>

@if($applicant->matric_number)
<div class="card mt-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Admission Details</h5>
    </div>
    <div class="card-body">
        <table class="table table-striped">
            <tr>
                <td><strong>Matric Number:</strong></td>
                <td><span class="badge bg-warning text-dark fs-6">{{ $applicant->matric_number }}</span></td>
            </tr>
            <tr>
                <td><strong>Department:</strong></td>
                <td>{{ $applicant->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Programme:</strong></td>
                <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>School:</strong></td>
                <td>{{ $applicant->school->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Level:</strong></td>
                <td>{{ $applicant->level }}00 Level</td>
            </tr>
        </table>
    </div>
</div>
@endif

{{-- Payment History --}}
<div class="card mt-4">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Payment History</h5>
        <a href="{{ route('applicant.payments.history') }}" class="btn btn-sm btn-light">
            View all <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body">
        @php $history = $applicant->transactionHistory(); @endphp
        @if($history->isEmpty())
            <div class="text-center py-3">
                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">No payment history yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Purpose</th>
                            <th>Amount</th>
                            <th>Channel</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history->take(5) as $row)
                            <tr>
                                <td><code>{{ $row['reference'] }}</code></td>
                                <td><span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $row['purpose'])) }}</span></td>
                                <td>₦{{ number_format((float) $row['amount'], 2) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $row['channel'])) }}</td>
                                <td>
                                    @if($row['paid_at'])
                                        {{ $row['paid_at'] instanceof \Illuminate\Support\Carbon ? $row['paid_at']->format('d M Y') : \Illuminate\Support\Carbon::parse($row['paid_at'])->format('d M Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $row['status'] === 'completed' ? 'success' : 'warning' }}">
                                        {{ ucfirst($row['status']) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@else
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-file-circle-plus fa-4x text-muted mb-4"></i>
                <h4>No Application Submitted</h4>
                <p class="text-muted">You haven't submitted an application yet.
                    @php
                    $requireFee = \App\Models\SystemSetting::get(\App\Models\SystemSetting::ADMISSION_REQUIRE_FEE, 'false') === 'true';
                    $feeAmount = \App\Models\SystemSetting::get(\App\Models\SystemSetting::ADMISSION_FEE_AMOUNT, 0);
                    @endphp
                    @if($requireFee && $feeAmount > 0)
                        <br><small class="text-danger">Application fee of ₦{{ number_format($feeAmount) }} is required before you can apply.</small>
                    @else
                        Apply now to get started.
                    @endif
                </p>

                {{-- Check if payment is required and redirect accordingly --}}
                @if($requireFee && $feeAmount > 0)
                    @if($applicant && $applicant->status === 'admitted')
                        <a href="{{ route('applicant.payment.gateway') }}?purpose=acceptance" class="btn btn-success btn-lg">
                            <i class="fas fa-credit-card me-2"></i>Pay Acceptance Fee
                        </a>
                    @elseif($applicant && !in_array($applicant->status, ['draft', 'pending']))
                        <a href="{{ route('applicant.application') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-eye me-2"></i>View Submitted Application
                        </a>
                    @else
                        <a href="{{ route('applicant.apply.payment') }}" class="btn btn-warning btn-lg">
                            <i class="fas fa-credit-card me-2"></i>Pay Application Fee First
                        </a>
                    @endif
                @else
                    @if($applicant && $applicant->status === 'admitted')
                        <a href="{{ route('applicant.payment.gateway') }}?purpose=acceptance" class="btn btn-success btn-lg">
                            <i class="fas fa-credit-card me-2"></i>Pay Acceptance Fee
                        </a>
                    @elseif($applicant && !in_array($applicant->status, ['draft', 'pending']))
                        <a href="{{ route('applicant.application') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-eye me-2"></i>View Submitted Application
                        </a>
                    @else
                        <a href="{{ route('applicant.apply') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Apply Now
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
function requeryPayment() {
    // This would typically call an API to check payment status
    alert('Requerying payment status...');
}
</script>
@endpush
@endsection