@extends('layouts.app')

@section('title', 'Courses')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Courses</h4>
    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Course
    </a>
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
                        <th>School</th>
                        <th>Department</th>
                        <th>Level</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                    <tr>
                        <td>{{ $course->code }}</td>
                        <td>{{ $course->title }}</td>
                        <td>{{ $course->units }}</td>
                        <td>{{ $course->school->code ?? 'N/A' }}</td>
                        <td>{{ $course->department->code ?? 'N/A' }}</td>
                        <td>{{ \App\Models\Course::getLevelName($course->level) }}</td>
                        <td>
                            <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this course">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete this course" onclick="return confirm('Delete this course?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No courses found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection