@extends('layouts.app')

@section('title', 'Assign Courses')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-user-plus me-2"></i>Assign Courses to Lecturers</h4>
    <a href="{{ route('hod.courses') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Courses
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
        <h5 class="mb-0">Assign Course to Lecturer</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('hod.courses.assign.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Select Course <span class="text-danger">*</span></label>
                    <select name="course_id" class="form-select" required>
                        <option value="">Select Course</option>
                        @foreach($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Select Lecturer <span class="text-danger">*</span></label>
                    <select name="lecturer_id" class="form-select" required>
                        <option value="">Select Lecturer</option>
                        @foreach($lecturers as $lecturer)
                        <option value="{{ $lecturer->id }}">{{ $lecturer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Session <span class="text-danger">*</span></label>
                    <select name="session_id" class="form-select" required>
                        <option value="">Select Session</option>
                        @php
                        $sessions = \App\Models\Session::orderBy('name', 'desc')->get();
                        @endphp
                        @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ $session->is_current ? 'selected' : '' }}>{{ $session->name }} {{ $session->is_current ? '(Current)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Assign Course
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Current Assignments -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Current Assignments</h5>
    </div>
    <div class="card-body">
        @if($assignments->isEmpty())
        <p class="text-muted">No course assignments yet.</p>
        @else
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Lecturer</th>
                        <th>Session</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->course->code ?? 'N/A' }} - {{ $assignment->course->title ?? 'N/A' }}</td>
                        <td>
                            @if($assignment->lecturer)
                                <span class="badge bg-primary">{{ $assignment->lecturer->name }}</span>
                            @else
                                <span class="badge bg-warning">Not Assigned</span>
                            @endif
                        </td>
                        <td>{{ $assignment->session->name ?? 'N/A' }}</td>
                        <td>
                            <form method="POST" action="{{ route('hod.courses.remove', $assignment->id) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this assignment?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
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
