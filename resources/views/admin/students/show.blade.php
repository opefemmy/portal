@extends('layouts.app')

@section('title', 'Student Details')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Student Details</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
        <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-primary">
            <i class="fas fa-edit me-2"></i>Edit
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($student->user && $student->user->passport)
                <img src="{{ asset('uploads/passports/' . $student->user->passport) }}" alt="Photo" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                @else
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px;">
                    <i class="fas fa-user-graduate fa-5x text-muted"></i>
                </div>
                @endif
                <h4>{{ $student->user->name ?? 'N/A' }}</h4>
                <p class="text-muted mb-1">{{ $student->matric_number }}</p>
                <span class="badge bg-{{ $student->status == 'active' ? 'success' : 'warning' }} fs-6">
                    {{ ucfirst($student->status) }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Student Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="35%">Matric Number:</th>
                        <td><code>{{ $student->matric_number }}</code></td>
                    </tr>
                    <tr>
                        <th>Full Name:</th>
                        <td>{{ $student->user->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{{ $student->user->email ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>School:</th>
                        <td>{{ $student->school->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Department:</th>
                        <td>{{ $student->department->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Programme:</th>
                        <td>{{ $student->programme->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Level:</th>
                        <td>{{ $student->level_display ?? $student->level }}</td>
                    </tr>
                    <tr>
                        <th>Session:</th>
                        <td>{{ $student->session->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge bg-{{ $student->status == 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($student->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Enrolled:</th>
                        <td>{{ optional($student->created_at)->format('d M Y') ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
            <div class="card-footer d-flex gap-2">
                <form method="POST" action="{{ route('admin.students.reset_password', $student) }}" onsubmit="return confirm('Reset password for this student?')">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>Reset Password
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('Delete this student?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection