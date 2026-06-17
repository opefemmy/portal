@extends('layouts.app')

@section('title', 'Applications')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Application Management</h4>
    <div>
        <a href="{{ route('registrar.applications.export') }}" class="btn btn-success">
            <i class="fas fa-download me-2"></i>Export CSV
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card stat-card">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $stats['total'] ?? $applications->total() }}</h3>
                <small class="text-muted">Total</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ \App\Models\Applicant::where('status', 'pending')->count() }}</h3>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card info">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ \App\Models\Applicant::where('status', 'screening')->count() }}</h3>
                <small class="text-muted">Screening</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ \App\Models\Applicant::where('status', 'approved')->count() }}</h3>
                <small class="text-muted">Approved</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card" style="border-left-color: #6a1b9a;">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ \App\Models\Applicant::where('status', 'admitted')->count() }}</h3>
                <small class="text-muted">Admitted</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card danger">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ \App\Models\Applicant::where('status', 'rejected')->count() }}</h3>
                <small class="text-muted">Rejected</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search by name, email, app number..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="screening" {{ request('status') == 'screening' ? 'selected' : '' }}>Screening</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="admitted" {{ request('status') == 'admitted' ? 'selected' : '' }}>Admitted</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="school_id" class="form-select">
                    <option value="">All Schools</option>
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('registrar.applications.index') }}" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Applications Table -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('registrar.applications.bulk') }}" id="bulkForm">
            @csrf
            <div class="mb-3">
                <button type="button" class="btn btn-warning btn-sm" onclick="bulkAction('screening')">
                    <i class="fas fa-search"></i> Send to Screening
                </button>
                <button type="button" class="btn btn-success btn-sm" onclick="bulkAction('approved')">
                    <i class="fas fa-check"></i> Approve Selected
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="bulkAction('admitted')">
                    <i class="fas fa-user-plus"></i> Admit Selected
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="bulkAction('rejected')">
                    <i class="fas fa-times"></i> Reject Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table datatable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>App Number</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>School</th>
                            <th>Department</th>
                            <th>Programme</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $applicant)
                        <tr>
                            <td><input type="checkbox" name="applications[]" value="{{ $applicant->id }}" class="application-checkbox"></td>
                            <td><strong>{{ $applicant->application_number }}</strong></td>
                            <td>
                                {{ $applicant->first_name }} {{ $applicant->surname }}
                                <br><small class="text-muted">{{ $applicant->email }}</small>
                            </td>
                            <td>{{ $applicant->gender }}</td>
                            <td>{{ $applicant->school->name ?? 'N/A' }}</td>
                            <td>{{ $applicant->department->name ?? 'N/A' }}</td>
                            <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
                            <td>
                                @switch($applicant->status)
                                    @case('pending')
                                        <span class="badge bg-warning">Pending</span>
                                        @break
                                    @case('screening')
                                        <span class="badge bg-info">Screening</span>
                                        @break
                                    @case('approved')
                                        <span class="badge bg-success">Approved</span>
                                        @break
                                    @case('admitted')
                                        <span class="badge bg-primary">Admitted</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                        @break
                                @endswitch
                            </td>
                            <td>{{ $applicant->created_at->format('d/m/Y') }}</td>
                            <td>
                                <a href="{{ route('registrar.applications.show', $applicant) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">No applications found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center">
                {{ $applications->links() }}
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#selectAll').change(function() {
    $('.application-checkbox').prop('checked', $(this).prop('checked'));
});

function bulkAction(action) {
    if ($('.application-checkbox:checked').length === 0) {
        alert('Please select at least one application');
        return;
    }
    if (confirm('Are you sure you want to ' + action + ' selected applications?')) {
        $('#bulkForm').append('<input type="hidden" name="action" value="' + action + '">');
        $('#bulkForm').submit();
    }
}
</script>
@endpush