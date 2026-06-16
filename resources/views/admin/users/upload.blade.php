@extends('layouts.app')

@section('title', 'Upload Users')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Upload Users</h4>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to Users</a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5>Upload Instructions</h5>
    </div>
    <div class="card-body">
        <p>Upload a CSV file with the following columns:</p>
        <ol>
            <li><strong>Email</strong> (required)</li>
            <li><strong>Name</strong> (required)</li>
            <li><strong>School ID</strong> (optional - leave empty if not applicable)</li>
            <li><strong>Department ID</strong> (optional - leave empty if not applicable)</li>
        </ol>
        <p class="text-muted">Default password for all uploaded users: <strong>password123</strong></p>
    </div>
</div>

<form action="{{ route('admin.users.upload.process') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="card mb-4">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Select Role *</label>
                <select name="role_id" class="form-select" required>
                    <option value="">Select Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Upload CSV File *</label>
                <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Available Schools (for School ID)</label>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Code</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                        <tr>
                            <td>{{ $school->id }}</td>
                            <td>{{ $school->name }}</td>
                            <td>{{ $school->code }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3">No schools available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mb-3">
                <label class="form-label">Available Departments (for Department ID)</label>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>School</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                        <tr>
                            <td>{{ $dept->id }}</td>
                            <td>{{ $dept->name }}</td>
                            <td>{{ $dept->school->name ?? 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3">No departments available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-upload me-2"></i>Upload Users
    </button>
</form>
@endsection