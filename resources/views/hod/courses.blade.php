@extends('layouts.app')

@section('title', 'Course Management')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-book me-2"></i>Course Management</h4>
    <a href="{{ route('hod.courses.assign') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Assign New Course
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Assigned Courses</h5>
    </div>
    <div class="card-body">
        @if($assignments->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No courses have been assigned yet. Click "Assign New Course" to assign courses to lecturers.
        </div>
        @else
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Lecturer</th>
                        <th>Session</th>
                        <th>Registered Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $assignment)
                    <tr>
                        <td><strong>{{ $assignment->course->code ?? 'N/A' }}</strong></td>
                        <td>{{ $assignment->course->title ?? 'N/A' }}</td>
                        <td>
                            @if($assignment->lecturer)
                                <span class="badge bg-primary">{{ $assignment->lecturer->name }}</span>
                            @else
                                <span class="badge bg-warning">Not Assigned</span>
                            @endif
                        </td>
                        <td>{{ $assignment->session->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $assignment->studentCourses->count() ?? 0 }}</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reassignModal{{ $assignment->id }}">
                                    <i class="fas fa-user-edit"></i> Reassign
                                </button>
                                <form method="POST" action="{{ route('hod.courses.remove', $assignment->id) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Reassign Modal -->
                            <div class="modal fade" id="reassignModal{{ $assignment->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reassign Lecturer</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('hod.courses.reassign', $assignment->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Course</label>
                                                    <input type="text" class="form-control" value="{{ $assignment->course->code ?? '' }} - {{ $assignment->course->title ?? '' }}" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Select Lecturer</label>
                                                    <select name="lecturer_id" class="form-select" required>
                                                        <option value="">Select Lecturer</option>
                                                        @php
                                                        $lecturerRole = \App\Models\Role::where('slug', 'lecturer')->first();
                                                        $lecturers = $lecturerRole ? \App\Models\User::where('role_id', $lecturerRole->id)->get() : [];
                                                        @endphp
                                                        @foreach($lecturers as $lecturer)
                                                        <option value="{{ $lecturer->id }}" {{ $assignment->lecturer_id == $lecturer->id ? 'selected' : '' }}>
                                                            {{ $lecturer->name }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Reassign</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
