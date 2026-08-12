@extends('layouts.app')

@section('title', 'Lecturer Dashboard')

@section('content')
<div class="page-header">
    <h4>Lecturer Dashboard</h4>
</div>

@include('widgets.render', ['widgets' => $widgets])

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
                        <td><strong>{{ $assignment->course->code ?? 'N/A' }}</strong></td>
                        <td>{{ $assignment->course->title ?? 'N/A' }}</td>
                        <td>{{ $assignment->course->department->name ?? 'N/A' }}</td>
                        <td>{{ $assignment->course->level ?? 'N/A' }}</td>
                        <td>{{ $assignment->session->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-primary">{{ $assignment->studentCourses->count() ?? 0 }}</span>
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