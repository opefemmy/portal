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

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Applicants</p>
                        <h3 class="mb-0">{{ $applicants->total() }}</h3>
                    </div>
                    <div class="stat-icon bg-primary-light">
                        <i class="fas fa-users text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Pending</p>
                        <h3 class="mb-0 text-warning">{{ $applicants->where('status', 'pending')->count() }}</h3>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="fas fa-clock text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Admitted</p>
                        <h3 class="mb-0 text-success">{{ $applicants->where('status', 'admitted')->count() }}</h3>
                    </div>
                    <div class="stat-icon bg-success-light">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Rejected</p>
                        <h3 class="mb-0 text-danger">{{ $applicants->where('status', 'rejected')->count() }}</h3>
                    </div>
                    <div class="stat-icon bg-danger-light">
                        <i class="fas fa-times-circle text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or application number">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Filter by Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                    <option value="admitted" {{ request('status') == 'admitted' ? 'selected' : '' }}>Admitted</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="department_id" class="form-label">Department</label>
                <select class="form-select" id="department_id" name="department_id">
                    <option value="">All Departments</option>
                    @foreach(\App\Models\Department::all() as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
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
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Centre</th>
                        <th>Programme</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applicants as $applicant)
                    <tr>
                        <td><code>{{ $applicant->application_number ?? 'N/A' }}</code></td>
                        <td>{{ $applicant->full_name ?? $applicant->user->name ?? 'N/A' }}</td>
                        <td>{{ $applicant->email ?? $applicant->user->email ?? 'N/A' }}</td>
                        <td>{{ $applicant->phone ?? 'N/A' }}</td>
                        <td>{{ $applicant->department->name ?? 'N/A' }}</td>
                        <td>{{ $applicant->centre->name ?? 'N/A' }}</td>
                        <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'pending' ? 'warning' : ($applicant->status === 'rejected' ? 'danger' : 'info')) }}">
                                {{ ucfirst($applicant->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <!-- View Button -->
                                <a href="{{ route('registrar.admission.show', $applicant->id) }}" class="btn btn-sm btn-outline-primary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <!-- Edit Button -->
                                <a href="{{ route('registrar.admission.edit', $applicant->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- Print Button -->
                                <a href="{{ route('applicant.application.print') }}" class="btn btn-sm btn-outline-info" title="Print" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>

                                <!-- Status Dropdown -->
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown" title="Change Status">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <form method="POST" action="{{ route('registrar.admission.updateStatus', $applicant) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" class="dropdown-item">Pending</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('registrar.admission.updateStatus', $applicant) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="status" value="reviewed">
                                                <button type="submit" class="dropdown-item">Reviewed</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('registrar.admission.updateStatus', $applicant) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="status" value="admitted">
                                                <button type="submit" class="dropdown-item text-success">Admit</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('registrar.admission.updateStatus', $applicant) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="dropdown-item text-danger">Reject</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Delete Button -->
                                <form method="POST" action="{{ route('registrar.admission.destroy', $applicant->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this applicant?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
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
        <div class="mt-3">
            {{ $applicants->links() }}
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.stat-card .card-body { padding: 1.25rem; }
.stat-icon {
    width: 48px; height: 48px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
}
.bg-primary-light { background-color: rgba(74, 108, 247, 0.1); }
.bg-success-light { background-color: rgba(34, 197, 94, 0.1); }
.bg-warning-light { background-color: rgba(245, 158, 11, 0.1); }
.bg-danger-light { background-color: rgba(239, 68, 68, 0.1); }
</style>
@endsection