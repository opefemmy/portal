@extends('layouts.app')

@section('title', 'Admitted Students')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Admitted Students</h4>
    <div>
        <a href="{{ route('registrar.applications.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Applications
        </a>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $students->total() ?? 0 }}</h3>
                <small class="text-muted">Total Admitted</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ \App\Models\Applicant::where('status', 'admitted')->where('payment_status', 'completed')->count() }}</h3>
                <small class="text-muted">Acceptance Fee Paid</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ \App\Models\Applicant::where('status', 'admitted')->where(function($q){ $q->whereNull('payment_status')->orWhere('payment_status', '!=', 'completed'); })->count() }}</h3>
                <small class="text-muted">Pending Acceptance Fee</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search by name / email / app number..." value="{{ request('search') }}">
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
            @if(isset($sessions))
            <div class="col-md-2">
                <select name="session_id" class="form-select">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ request('session_id') == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('registrar.applications.admitted') }}" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Admitted Students Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>App Number</th>
                        <th>Name</th>
                        <th>School</th>
                        <th>Department</th>
                        <th>Programme</th>
                        <th>Session</th>
                        <th>Matric No.</th>
                        <th>Acceptance Fee</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $applicant)
                    <tr>
                        <td><strong>{{ $applicant->application_number }}</strong></td>
                        <td>
                            {{ $applicant->first_name }} {{ $applicant->surname }}
                            <br><small class="text-muted">{{ $applicant->email }}</small>
                        </td>
                        <td>{{ $applicant->school->name ?? 'N/A' }}</td>
                        <td>{{ $applicant->department->name ?? 'N/A' }}</td>
                        <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
                        <td>{{ $applicant->session->name ?? 'N/A' }}</td>
                        <td>
                            @if($applicant->matric_number)
                                <code>{{ $applicant->matric_number }}</code>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($applicant->payment_status === 'completed')
                                <span class="badge bg-success">Paid</span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('registrar.admission.show', $applicant) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('registrar.admission.generateLetter', $applicant) }}" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">No admitted students found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center">
            {{ $students->links() }}
        </div>
    </div>
</div>
@endsection
