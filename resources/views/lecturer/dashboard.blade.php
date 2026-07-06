@extends('layouts.app')

@section('title', 'Lecturer Dashboard')

@section('content')
<div class="page-header">
    <h4>Lecturer Dashboard</h4>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card success">
            <div class="card-body">
                <h6>Assigned Courses</h6>
                <h2>{{ $assignments->count() }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card info">
            <div class="card-body">
                <h6>Total Students</h6>
                <h2>{{ $assignments->sum(function($a) { return $a->studentCourses->count(); }) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6>Pending Results</h6>
                <h2>{{ $assignments->sum(function($a) { return $a->results->where('status', 'pending_approval')->count(); }) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5>My Courses</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Level</th>
                        <th>Session</th>
                        <th>Registered Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)
                    <tr>
                        <td><strong>{{ $assignment->course->code }}</strong></td>
                        <td>{{ $assignment->course->title }}</td>
                        <td>{{ $assignment->department->name ?? 'N/A' }}</td>
                        <td>{{ $assignment->course->level }}</td>
                        <td>{{ $assignment->session->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-primary">{{ $assignment->studentCourses->count() }}</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('lecturer.courses.students', $assignment->course) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-users me-1"></i>Students
                                </a>
                                <a href="{{ route('lecturer.courses.results', $assignment->course) }}" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-edit me-1"></i>Enter Results
                                </a>
                                <a href="{{ route('lecturer.courses.template', $assignment->course) }}" class="btn btn-sm btn-outline-dark">
                                    <i class="fas fa-download me-1"></i>Template
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">No courses assigned yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection