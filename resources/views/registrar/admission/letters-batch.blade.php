@extends('layouts.app')

@section('title', 'Batch Admission Letters')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="fas fa-file-signature me-2"></i>Admission Letters — Batch</h4>
        <p class="text-muted mb-0">{{ $admitted->count() }} admitted applicant(s) ready for letter generation</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('registrar.admission.byDepartment') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
        <a href="{{ route('registrar.admission.letters') }}" class="btn btn-warning">
            <i class="fas fa-cog me-2"></i>Letter Settings
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Department Filter</label>
                <select name="department_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    @foreach(\App\Models\Department::orderBy('name')->get() as $dept)
                        <option value="{{ $dept->id }}" {{ (string)$departmentId === (string)$dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Apply
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Application No.</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Programme</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($admitted as $i => $applicant)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><code>{{ $applicant->application_number }}</code></td>
                        <td>{{ trim(($applicant->surname ?? '') . ' ' . ($applicant->first_name ?? '') . ' ' . ($applicant->middle_name ?? '')) }}</td>
                        <td>{{ $applicant->department->name ?? 'N/A' }}</td>
                        <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
                        <td>
                            <a href="{{ route('registrar.admission.generateLetter', $applicant) }}" class="btn btn-sm btn-success" target="_blank">
                                <i class="fas fa-file-signature me-1"></i>Open Letter
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
