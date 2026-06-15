@extends('layouts.app')

@section('title', 'Student Report')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Student Report</h4>
    <a href="{{ route('admin.reports') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Reports
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.students') }}" class="row g-3">
            <div class="col-md-3">
                <label for="school_id" class="form-label">School</label>
                <select class="form-select" name="school_id" id="school_id">
                    <option value="">All Schools</option>
                    @foreach(\App\Models\School::all() as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="department_id" class="form-label">Department</label>
                <select class="form-select" name="department_id" id="department_id">
                    <option value="">All Departments</option>
                    @foreach(\App\Models\Department::all() as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="level" class="form-label">Level</label>
                <select class="form-select" name="level" id="level">
                    <option value="">All Levels</option>
                    <option value="1">100L / ND1</option>
                    <option value="2">200L / ND</option>
                    <option value="3">300L / HND1</option>
                    <option value="4">400L / HND2</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" name="status" id="status">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="graduated">Graduated</option>
                    <option value="suspended">Suspended</option>
                    <option value="withdrawn">Withdrawn</option>
                </select>
            </div>
            <div class="col-md-2">
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
                        <th>Matric Number</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>School</th>
                        <th>Department</th>
                        <th>Programme</th>
                        <th>Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    <tr>
                        <td>{{ $student->matric_number ?? 'N/A' }}</td>
                        <td>{{ $student->user->name ?? 'N/A' }}</td>
                        <td>{{ $student->user->email ?? 'N/A' }}</td>
                        <td>{{ $student->school->name ?? 'N/A' }}</td>
                        <td>{{ $student->department->name ?? 'N/A' }}</td>
                        <td>{{ $student->programme->name ?? 'N/A' }}</td>
                        <td>{{ $student->levelDisplay }}</td>
                        <td>
                            <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($student->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">No students found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection