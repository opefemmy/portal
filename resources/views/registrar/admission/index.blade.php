@extends('layouts.app')

@section('title', 'Admission Management')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Admission Management</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('registrar.admission.settings') }}" class="btn btn-outline-primary">
            <i class="fas fa-cog me-2"></i>Settings
        </a>
        <a href="{{ route('registrar.admission.track') }}" class="btn btn-outline-info">
            <i class="fas fa-search me-2"></i>Track Admission
        </a>
        <a href="{{ route('registrar.admission.print') }}" class="btn btn-success" target="_blank">
            <i class="fas fa-print me-2"></i>Print List
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Filter by Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                    <option value="admitted" {{ request('status') == 'admitted' ? 'selected' : '' }}>Admitted</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>App Number</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Programme</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applicants as $applicant)
                    <tr>
                        <td>{{ $applicant->application_number ?? 'N/A' }}</td>
                        <td>{{ $applicant->full_name ?? $applicant->user->name ?? 'N/A' }}</td>
                        <td>{{ $applicant->email ?? $applicant->user->email ?? 'N/A' }}</td>
                        <td>{{ $applicant->department->name ?? 'N/A' }}</td>
                        <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'pending' ? 'warning' : ($applicant->status === 'rejected' ? 'danger' : 'info')) }}">
                                {{ ucfirst($applicant->status) }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('registrar.admission.updateStatus', $applicant) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <select name="status" class="form-select form-select-sm d-inline" style="width: auto;" onchange="this.form.submit()">
                                    <option value="pending" {{ $applicant->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="reviewed" {{ $applicant->status == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                                    <option value="admitted" {{ $applicant->status == 'admitted' ? 'selected' : '' }}>Admit</option>
                                    <option value="rejected" {{ $applicant->status == 'rejected' ? 'selected' : '' }}>Reject</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No applicants found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection