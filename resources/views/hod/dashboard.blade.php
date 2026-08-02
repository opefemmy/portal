@extends('layouts.app')

@section('title', 'HOD Dashboard')

@section('content')
<div class="page-header">
    <h4>Head of Department Dashboard</h4>
    <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}</p>
</div>

@if(!$departmentId)
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    You are not assigned to any department. Please contact the administrator.
</div>
@else
<div class="row mb-4">
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card info h-100">
            <div class="card-body">
                <h6 class="text-muted"><i class="fas fa-book me-2"></i>Department Courses</h6>
                <h2 class="mb-0">{{ $stats['total_courses'] }}</h2>
                <a href="{{ route('hod.courses') }}" class="small">View courses <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card success h-100">
            <div class="card-body">
                <h6 class="text-muted"><i class="fas fa-user-tie me-2"></i>Lecturers</h6>
                <h2 class="mb-0">{{ $stats['total_lecturers'] }}</h2>
                <small class="text-muted">Assigned to courses</small>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card primary h-100">
            <div class="card-body">
                <h6 class="text-muted"><i class="fas fa-user-graduate me-2"></i>Students</h6>
                <h2 class="mb-0">{{ $stats['total_students'] }}</h2>
                <small class="text-muted">In this department</small>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card warning h-100">
            <div class="card-body">
                <h6 class="text-muted"><i class="fas fa-clipboard-check me-2"></i>Pending Results</h6>
                <h2 class="mb-0">{{ $stats['pending_results'] }}</h2>
                <a href="{{ route('hod.results.index') }}" class="small">Review <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-book-reader me-2"></i>Recent Assignments</h5>
                <a href="{{ route('hod.courses') }}" class="btn btn-sm btn-outline-primary">All</a>
            </div>
            <div class="card-body">
                @if($recentAssignments->isEmpty())
                <p class="text-muted mb-0">No course assignments yet.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Lecturer</th>
                                <th>Session</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAssignments as $a)
                            <tr>
                                <td><strong>{{ $a->course->code ?? 'N/A' }}</strong></td>
                                <td>{{ $a->lecturer->name ?? 'N/A' }}</td>
                                <td>{{ $a->session->name ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Pending Results</h5>
                <a href="{{ route('hod.results.index') }}" class="btn btn-sm btn-outline-primary">All</a>
            </div>
            <div class="card-body">
                @if($pendingResultsList->isEmpty())
                <p class="text-muted mb-0">No pending results for approval.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Student</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingResultsList as $r)
                            <tr>
                                <td>{{ $r->course->code ?? 'N/A' }}</td>
                                <td>{{ $r->studentCourse->student->matric_number ?? 'N/A' }}</td>
                                <td><span class="badge bg-warning">Pending</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('hod.courses.assign') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Assign Course
                    </a>
                    <a href="{{ route('hod.courses') }}" class="btn btn-outline-primary">
                        <i class="fas fa-book me-2"></i>Manage Courses
                    </a>
                    <a href="{{ route('hod.results.index') }}" class="btn btn-outline-warning">
                        <i class="fas fa-clipboard-check me-2"></i>Review Results
                    </a>
                    <a href="{{ route('hod.timetable') }}" class="btn btn-outline-info">
                        <i class="fas fa-calendar-alt me-2"></i>View Timetable
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection