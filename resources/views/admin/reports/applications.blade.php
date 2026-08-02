@extends('layouts.app')

@section('title', 'Application Report')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Application Report</h4>
    <a href="{{ route('admin.reports') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Reports
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.applications') }}" class="row g-3">
            <div class="col-md-3">
                <label for="school_id" class="form-label">School</label>
                <select class="form-select" name="school_id" id="school_id">
                    <option value="">All Schools</option>
                    @foreach(\App\Models\School::all() as $school)
                        <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="department_id" class="form-label">Department</label>
                <select class="form-select" name="department_id" id="department_id">
                    <option value="">All Departments</option>
                    @foreach(\App\Models\Department::all() as $department)
                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" name="status" id="status">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="screening" {{ request('status') == 'screening' ? 'selected' : '' }}>Screening</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="admitted" {{ request('status') == 'admitted' ? 'selected' : '' }}>Admitted</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <a href="{{ route('admin.reports.applications') }}" class="btn btn-secondary w-100">Reset</a>
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
                        <th>Application No.</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>School</th>
                        <th>Department</th>
                        <th>Programme</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $applicant)
                    <tr>
                        <td><code>{{ $applicant->application_number }}</code></td>
                        <td>{{ $applicant->first_name }} {{ $applicant->surname }}</td>
                        <td>{{ $applicant->email }}</td>
                        <td>{{ $applicant->phone ?? 'N/A' }}</td>
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
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($applicant->status) }}</span>
                            @endswitch
                        </td>
                        <td>{{ optional($applicant->created_at)->format('d/m/Y') ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">No applications found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection