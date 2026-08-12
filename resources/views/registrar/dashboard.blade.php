@extends('layouts.app')

@section('title', 'Registrar Dashboard')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-tachometer-alt me-2"></i>Registrar Dashboard</h4>
    <span class="text-muted small">Admission pipeline at a glance</span>
</div>

{{-- Filter by School --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('registrar.dashboard') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter by School</label>
                <select name="school_id" class="form-select">
                    <option value="">All Schools</option>
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ (string) $schoolId === (string) $school->id ? 'selected' : '' }}>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
            </div>
            @if($schoolId)
                <div class="col-md-2">
                    <a href="{{ route('registrar.dashboard') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-undo me-1"></i>Clear
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

{{-- Stat tiles (Total Applicants / Pending Review / Admitted / Rejected)
     — read from the registry via DashboardResolver. The Pending Review
     and Admitted tiles carry a coloured CTA button. The pipeline flow
     strip below and the recent-applications / recent-admissions tables
     stay in chrome. --}}
@include('widgets.render', ['widgets' => $widgets])

{{-- Pipeline breakdown (small numbers) --}}
@if($stats['screening'] || $stats['approved'])
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap gap-4 align-items-center">
                    <span class="text-muted small">Pipeline:</span>
                    <span><strong>{{ $stats['pending'] }}</strong> Pending</span>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <span><strong>{{ $stats['screening'] }}</strong> Screening</span>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <span><strong>{{ $stats['approved'] }}</strong> Approved</span>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <span class="text-success"><strong>{{ $stats['admitted'] }}</strong> Admitted</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    {{-- Recent Applicants --}}
    <div class="col-lg-7 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Recent Applicants</h5>
                <a href="{{ route('registrar.applications.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt me-1"></i>All Applications
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Application #</th>
                                <th>Name</th>
                                <th>School</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentApplicants as $applicant)
                            <tr>
                                <td><code>{{ $applicant->application_number }}</code></td>
                                <td>{{ $applicant->full_name ?? $applicant->user->name ?? 'N/A' }}</td>
                                <td>{{ $applicant->school->name ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $badge = match($applicant->status) {
                                            'admitted'  => 'success',
                                            'rejected'  => 'danger',
                                            'screening' => 'info',
                                            'approved'  => 'primary',
                                            default     => 'warning',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ucfirst($applicant->status ?? 'pending') }}</span>
                                </td>
                                <td>{{ optional($applicant->created_at)->diffForHumans() ?? 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No applicants yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Admissions --}}
    <div class="col-lg-5 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Recent Admissions</h5>
                <a href="{{ route('registrar.admission') }}" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-list me-1"></i>Admission List
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Application #</th>
                                <th>Name</th>
                                <th>Programme</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAdmissions as $admission)
                            <tr>
                                <td><code>{{ $admission->application_number }}</code></td>
                                <td>{{ $admission->full_name ?? $admission->user->name ?? 'N/A' }}</td>
                                <td>{{ $admission->programme->name ?? 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No admissions yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
