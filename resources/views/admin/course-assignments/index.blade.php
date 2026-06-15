@extends('layouts.app')

@section('title', 'OnCourses - Course Assignments')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>OnCourses - Course Assignments</h4>
    <a href="{{ route('admin.course-assignments.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Assign Course to Lecturer
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Department</th>
                        <th>Lecturer</th>
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->course->code ?? 'N/A' }}</td>
                        <td>{{ $assignment->course->title ?? 'N/A' }}</td>
                        <td>{{ $assignment->course->department->name ?? 'N/A' }}</td>
                        <td>{{ $assignment->lecturer->name ?? 'N/A' }}</td>
                        <td>{{ $assignment->session->name ?? 'N/A' }}</td>
                        <td>{{ $assignment->semester }}</td>
                        <td>
                            <a href="{{ route('admin.course-assignments.edit', $assignment) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this assignment">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.course-assignments.destroy', $assignment) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Remove this assignment" onclick="return confirm('Remove this course assignment?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No course assignments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection