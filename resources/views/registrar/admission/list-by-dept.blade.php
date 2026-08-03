@extends('layouts.app')

@section('title', 'Admission List by Department')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="fas fa-layer-group me-2"></i>Admission List by Department</h4>
        <p class="text-muted mb-0">Filter and review admitted applicants by department</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('registrar.admission') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Admission List
        </a>
        <a href="{{ route('registrar.admission.print') }}" class="btn btn-success" target="_blank">
            <i class="fas fa-print me-2"></i>Print All
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Filter by Department</label>
                <select name="department_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string)$departmentId === (string)$dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Apply Filter
                </button>
            </div>
            @if($departmentId)
                <div class="col-md-3">
                    <a href="{{ route('registrar.admission.byDepartment') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-2"></i>Clear Filter
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Showing <strong>{{ $admitted->count() }}</strong> admitted applicant(s)
            @if($departmentId)
                @php
                    $currentDept = $departments->firstWhere('id', $departmentId);
                @endphp
                for department <strong>{{ $currentDept->name ?? 'Unknown' }}</strong>
            @else
                across all departments
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Application No.</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Programme</th>
                        <th>School</th>
                        <th>Admitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admitted as $i => $applicant)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><code>{{ $applicant->application_number }}</code></td>
                        <td>
                            <strong>{{ $applicant->surname }} {{ $applicant->first_name }} {{ $applicant->middle_name }}</strong>
                        </td>
                        <td>{{ $applicant->email ?? 'N/A' }}</td>
                        <td>{{ $applicant->phone ?? 'N/A' }}</td>
                        <td>{{ $applicant->department->name ?? 'N/A' }}</td>
                        <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
                        <td>{{ $applicant->school->name ?? 'N/A' }}</td>
                        <td>
                            @if($applicant->admission_date)
                                @php
                                    try {
                                        $admissionDate = \Carbon\Carbon::parse($applicant->admission_date)->format('d M Y');
                                    } catch (\Throwable $e) {
                                        $admissionDate = (string) $applicant->admission_date;
                                    }
                                @endphp
                                <span class="badge bg-success">{{ $admissionDate }}</span>
                            @else
                                <span class="badge bg-secondary">Pending</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('registrar.admission.show', $applicant) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('registrar.admission.generateLetter', $applicant) }}" class="btn btn-sm btn-outline-success" title="Print Letter" target="_blank">
                                <i class="fas fa-file-signature"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted">No admitted applicants found for the selected filter.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
