@extends('layouts.app')

@section('title', 'Lecturer Dashboard')

@section('content')
<div class="page-header">
    <h4>Lecturer Dashboard</h4>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card stat-card success">
            <div class="card-body">
                <h6>Assigned Courses</h6>
                <h2>{{ $assignments->count() }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5>My Courses</h5>
    </div>
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Title</th>
                    <th>Session</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $assignment)
                <tr>
                    <td>{{ $assignment->course->code }}</td>
                    <td>{{ $assignment->course->title }}</td>
                    <td>{{ $assignment->session->name }}</td>
                    <td>
                        <a href="{{ route('lecturer.courses.students', $assignment->course) }}" class="btn btn-sm btn-outline-primary">Students</a>
                        <a href="{{ route('lecturer.courses.results', $assignment->course) }}" class="btn btn-sm btn-outline-success">Enter Results</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No courses assigned yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection