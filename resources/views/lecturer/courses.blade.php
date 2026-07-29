@extends('layouts.app')

@section('title', 'My Courses')

@section('content')
<div class="page-header">
    <h4>My Assigned Courses</h4>
</div>

@if($assignments->isEmpty())
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    No courses have been assigned to you yet. Please contact your HOD.
</div>
@else
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Department</th>
                        <th>Level</th>
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
                        <td>{{ $assignment->course->department->name ?? 'N/A' }}</td>
                        <td>{{ $assignment->course->level ?? 'N/A' }}</td>
                        <td>{{ $assignment->session->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-primary">{{ $assignment->studentCourses->count() ?? 0 }}</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('lecturer.courses.students', $assignment->course_id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-users"></i> Students
                                </a>
                                <a href="{{ route('lecturer.courses.results', $assignment->course_id) }}" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-edit"></i> Enter Results
                                </a>
                                <a href="{{ route('lecturer.courses.template', $assignment->course_id) }}" class="btn btn-sm btn-outline-dark">
                                    <i class="fas fa-download"></i> Template
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
