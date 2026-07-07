@extends('layouts.app')

@section('title', 'Manage Students')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Manage Students</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.students.import') }}" class="btn btn-outline-success">
            <i class="fas fa-upload me-2"></i>Import CSV
        </a>
        <a href="{{ route('admin.students.import.template') }}" class="btn btn-outline-secondary">
            <i class="fas fa-download me-2"></i>Template
        </a>
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Student
        </a>
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
                        <th>Department</th>
                        <th>Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    <tr>
                        <td>{{ $student->matric_number }}</td>
                        <td>{{ $student->user->name ?? 'N/A' }}</td>
                        <td>{{ $student->user->email ?? 'N/A' }}</td>
                        <td>{{ $student->department->name ?? 'N/A' }}</td>
                        <td>{{ $student->level_display }}</td>
                        <td>
                            <span class="badge bg-{{ $student->status == 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($student->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit student">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.students.reset_password', $student) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Reset password" onclick="return confirm('Reset password for this student?')">
                                    <i class="fas fa-key"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.students.destroy', $student) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete student" onclick="return confirm('Delete this student?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No students found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection