@extends('layouts.app')

@section('title', 'View Applicant')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Applicant Details</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('registrar.admission') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
        <a href="{{ route('registrar.admission.edit', $applicant->id) }}" class="btn btn-primary">
            <i class="fas fa-edit me-2"></i>Edit
        </a>
        <a href="#" class="btn btn-info" onclick="window.print()">
            <i class="fas fa-print me-2"></i>Print
        </a>
    </div>
</div>

<div class="row">
    <!-- Personal Information -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Application Number</th>
                        <td><code>{{ $applicant->application_number ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <th>Full Name</th>
                        <td>{{ $applicant->full_name }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $applicant->email }}</td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td>{{ $applicant->phone ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td>{{ $applicant->gender ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Date of Birth</th>
                        <td>{{ $applicant->date_of_birth ? $applicant->date_of_birth->format('d M, Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Blood Group</th>
                        <td>{{ $applicant->blood_group ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Genotype</th>
                        <td>{{ $applicant->genotype ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Programme Selection -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Programme Selection</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">School</th>
                        <td>{{ $applicant->school->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Department</th>
                        <td>{{ $applicant->department->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Programme</th>
                        <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Session</th>
                        <td>{{ $applicant->session->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Preferred Centre</th>
                        <td>{{ $applicant->centre->name ?? 'N/A' }} ({{ $applicant->centre->code ?? '' }})</td>
                    </tr>
                    <tr>
                        <th>Mode of Study</th>
                        <td>{{ $applicant->mode_of_study ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Payment Information -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Payment Status</th>
                        <td>
                            <span class="badge bg-{{ $applicant->payment_status === 'completed' ? 'success' : 'warning' }}">
                                {{ ucfirst($applicant->payment_status ?? 'pending') }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Payment Reference</th>
                        <td><code>{{ $applicant->payment_ref ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td>{{ $applicant->payment_amount ? '₦' . number_format($applicant->payment_amount, 2) : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Payment Date</th>
                        <td>{{ $applicant->payment_date ? $applicant->payment_date->format('d M, Y') : 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Admission Status -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Admission Status</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Status</th>
                        <td>
                            <span class="badge bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : ($applicant->status === 'pending' ? 'warning' : 'info')) }}">
                                {{ ucfirst($applicant->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Matric Number</th>
                        <td><code>{{ $applicant->matric_number ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <th>Application Date</th>
                        <td>{{ $applicant->created_at->format('d M, Y') }}</td>
                    </tr>
                </table>

                <hr>

                <h6>Update Status</h6>
                <form method="POST" action="{{ route('registrar.admission.updateStatus', $applicant) }}" class="d-flex gap-2">
                    @csrf @method('PUT')
                    <select name="status" class="form-select">
                        <option value="pending" {{ $applicant->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="reviewed" {{ $applicant->status == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                        <option value="admitted" {{ $applicant->status == 'admitted' ? 'selected' : '' }}>Admit</option>
                        <option value="rejected" {{ $applicant->status == 'rejected' ? 'selected' : '' }}>Reject</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- O-Level Results -->
@if($applicant->olevel1_subject1)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-book me-2"></i>O-Level Results</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>First Sitting</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 1; $i <= 5; $i++)
                            @if($applicant->{'olevel1_subject' . $i})
                            <tr>
                                <td>{{ $applicant->{'olevel1_subject' . $i} }}</td>
                                <td>{{ $applicant->{'olevel1_grade' . $i} }}</td>
                            </tr>
                            @endif
                        @endfor
                    </tbody>
                </table>
                <p class="text-muted"><small>Exam Year: {{ $applicant->olevel1_exam_year ?? 'N/A' }}</small></p>
            </div>
            @if($applicant->olevel2_subject1)
            <div class="col-md-6">
                <h6>Second Sitting</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 1; $i <= 5; $i++)
                            @if($applicant->{'olevel2_subject' . $i})
                            <tr>
                                <td>{{ $applicant->{'olevel2_subject' . $i} }}</td>
                                <td>{{ $applicant->{'olevel2_grade' . $i} }}</td>
                            </tr>
                            @endif
                        @endfor
                    </tbody>
                </table>
                <p class="text-muted"><small>Exam Year: {{ $applicant->olevel2_exam_year ?? 'N/A' }}</small></p>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

@endsection
