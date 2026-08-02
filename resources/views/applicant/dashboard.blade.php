@extends('layouts.app')

@section('title', 'Applicant Dashboard')

@section('content')
<div class="page-header">
    <h4>Applicant Dashboard</h4>
</div>

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
            <div class="card-header bg-{{ $applicant->payment_status === 'completed' ? 'success' : 'warning' }} text-white">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment</h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-credit-card fa-3x text-{{ $applicant->payment_status === 'completed' ? 'success' : 'warning' }} mb-3"></i>

                {{-- Payment Status --}}
                <div class="mb-3">
                    @if($applicant->payment_status === 'completed')
                        <span class="badge bg-success fs-6"><i class="fas fa-check me-1"></i> Payment Verified</span>
                    @else
                        <span class="badge bg-warning fs-6"><i class="fas fa-clock me-1"></i> Payment Required</span>
                    @endif
                </div>

                {{-- Pay Now Button - Only show if payment not completed --}}
                @if($applicant->payment_status !== 'completed')
                    @if($applicant->status === 'admitted')
                        <a href="{{ route('applicant.payment.gateway') }}" class="btn btn-success mb-2 w-100">
                            <i class="fas fa-credit-card me-2"></i>Pay Acceptance Fee
                        </a>
                    @else
                        <a href="{{ route('applicant.payment.gateway') }}" class="btn btn-success mb-2 w-100">
                            <i class="fas fa-credit-card me-2"></i>Pay Now
                        </a>
                    @endif

                    {{-- Validate Button - For verifying uploaded payments --}}
                    <a href="{{ url('/applicant/validate-payment') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-check-circle me-1"></i>Validate Payment
                    </a>
                @endif

                @if($applicant->payment_ref)
                <hr>
                <small class="text-muted">Ref: {{ $applicant->payment_ref }}</small>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row mt-3">
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                <h5>View Application</h5>
                <p class="text-muted">View your submitted application details</p>
                <a href="{{ route('applicant.application') }}" class="btn btn-primary">
                    <i class="fas fa-eye me-2"></i>View
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-print fa-3x text-secondary mb-3"></i>
                <h5>Print Application</h5>
                <p class="text-muted">Print a copy of your application form</p>
                <a href="{{ route('applicant.application.print') }}" class="btn btn-secondary" target="_blank">
                    <i class="fas fa-print me-2"></i>Print
                </a>
            </div>
        </div>
    </div>

    @if($applicant->status === 'admitted')
    <div class="col-md-4 mb-3">
        <div class="card h-100 border-success">
            <div class="card-body text-center">
                <i class="fas fa-graduation-cap fa-3x text-success mb-3"></i>
                <h5>Accept Admission</h5>
                <p class="text-muted">Pay acceptance fee to secure your admission</p>
                <a href="{{ route('applicant.payment.gateway') }}" class="btn btn-success">
                    <i class="fas fa-credit-card me-2"></i>Pay Acceptance Fee
                </a>
            </div>
        </div>
    </div>
    @endif

    @if($applicant->status === 'admitted' && $applicant->payment_status === 'completed')
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
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Payment History</h5>
    </div>
    <div class="card-body">
        @if($applicant && $applicant->payment_status === 'completed')
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $applicant->payment_ref ?? $applicant->payment_transaction_id ?? ($externalPayment->transaction_id ?? 'N/A') }}</td>
                    <td>₦{{ number_format($applicant->payment_amount ?? ($externalPayment->amount ?? 0), 2) }}</td>
                    <td>
                        @php
                            $paymentDateRaw = $applicant->payment_date ?? ($externalPayment->payment_date ?? null);
                            $paymentDateFormatted = 'N/A';
                            if ($paymentDateRaw) {
                                try {
                                    $paymentDateFormatted = \Carbon\Carbon::parse($paymentDateRaw)->format('d M Y');
                                } catch (\Throwable $e) {
                                    $paymentDateFormatted = (string) $paymentDateRaw;
                                }
                            }
                        @endphp
                        {{ $paymentDateFormatted }}
                    </td>
                    <td><span class="badge bg-success"><i class="fas fa-check me-1"></i> Verified</span></td>
                </tr>
            </tbody>
        </table>
        @else
        <div class="text-center py-3">
            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
            <p class="text-muted">No payment history yet.</p>
            <a href="{{ route('applicant.payment') }}" class="btn btn-primary">
                <i class="fas fa-credit-card me-2"></i>Make Payment
            </a>
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
                    <a href="{{ route('applicant.apply.payment') }}" class="btn btn-warning btn-lg">
                        <i class="fas fa-credit-card me-2"></i>Pay Application Fee First
                    </a>
                @else
                    <a href="{{ route('applicant.apply') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane me-2"></i>Apply Now
                    </a>
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