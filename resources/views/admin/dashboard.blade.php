@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4 class="mb-0">Dashboard</h4>
        <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}</p>
    </div>
    <div>
        <span class="badge bg-primary fs-6">
            <i class="fas fa-calendar me-1"></i>
            Session: {{ $currentSession->name ?? 'Not Set' }}
        </span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Total Students</h6>
                        <h2 class="mb-0">{{ number_format($stats['total_students']) }}</h2>
                    </div>
                    <div class="icon text-success">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Total Staff</h6>
                        <h2 class="mb-0">{{ number_format($stats['total_staff']) }}</h2>
                    </div>
                    <div class="icon text-info">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Total Fees Expected</h6>
                        <h2 class="mb-0">₦{{ number_format($stats['total_expected_fees'], 0) }}</h2>
                    </div>
                    <div class="icon text-warning">
                        <i class="fas fa-calculator"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Total Payments</h6>
                        <h2 class="mb-0">₦{{ number_format($stats['total_payments'], 0) }}</h2>
                    </div>
                    <div class="icon text-success">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Outstanding Fees</h6>
                        <h2 class="mb-0">₦{{ number_format($stats['outstanding_fees'], 0) }}</h2>
                    </div>
                    <div class="icon text-danger">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Pending Applications</h6>
                        <h2 class="mb-0">{{ number_format($stats['pending_applications']) }}</h2>
                    </div>
                    <div class="icon text-info">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Registered Courses</h6>
                        <h2 class="mb-0">{{ number_format($stats['registered_courses']) }}</h2>
                    </div>
                    <div class="icon text-success">
                        <i class="fas fa-book-open"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Total Courses</h6>
                        <h2 class="mb-0">{{ number_format($stats['total_courses']) }}</h2>
                    </div>
                    <div class="icon text-warning">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Applicants -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>Recent Applications
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentApplicants as $applicant)
                            <tr>
                                <td>{{ $applicant->user->name }}</td>
                                <td>{{ $applicant->department->code }}</td>
                                <td>
                                    <span class="badge badge-status bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($applicant->status) }}
                                    </span>
                                </td>
                                <td>{{ $applicant->created_at->format('d M Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No recent applications</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="fas fa-dollar-sign me-2"></i>Recent Payments
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                            <tr>
                                <td>{{ $payment->student->user->name }}</td>
                                <td>{{ $payment->fee->name }}</td>
                                <td>₦{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    <span class="badge badge-status bg-{{ $payment->status === 'completed' ? 'success' : ($payment->status === 'failed' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No recent payments</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.users.create') }}" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-user-plus fa-2x d-block mb-2"></i>
                            Add New User
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.courses.create') }}" class="btn btn-outline-success w-100 py-3">
                            <i class="fas fa-book fa-2x d-block mb-2"></i>
                            Add New Course
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.fees.create') }}" class="btn btn-outline-warning w-100 py-3">
                            <i class="fas fa-dollar-sign fa-2x d-block mb-2"></i>
                            Configure Fees
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.reports') }}" class="btn btn-outline-info w-100 py-3">
                            <i class="fas fa-chart-bar fa-2x d-block mb-2"></i>
                            View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection