@extends('layouts.app')

@section('title', 'My Courses')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>My Courses</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('student.courses.print') }}" class="btn btn-success" target="_blank">
            <i class="fas fa-print me-2"></i>Print Form
        </a>
        <a href="{{ route('student.courses.register') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Register Courses
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Units</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                    <tr>
                        <td>{{ $course->course->code }}</td>
                        <td>{{ $course->course->title }}</td>
                        <td>{{ $course->course->units }}</td>
                        <td>{{ ucfirst($course->semester) }}</td>
                        <td>
                            <span class="badge bg-{{ $course->status === 'registered' ? 'success' : 'danger' }}">
                                {{ ucfirst($course->status) }}
                            </span>
                        </td>
                        <td>
                            @if($course->status === 'registered')
                            <form method="POST" action="{{ route('student.courses.drop', $course) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Drop this course?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No courses registered yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection