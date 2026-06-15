@extends('layouts.app')

@section('title', 'Register Courses')

@section('content')
<div class="page-header">
    <h4>Register Courses</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('student.courses.register') }}">
            @csrf
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Units</th>
                            <th>Semester</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($availableCourses as $course)
                        <tr>
                            <td>
                                <input type="checkbox" name="courses[]" value="{{ $course->id }}">
                            </td>
                            <td>{{ $course->code }}</td>
                            <td>{{ $course->title }}</td>
                            <td>{{ $course->units }}</td>
                            <td>{{ ucfirst($course->semester) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No courses available for registration.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Register Selected Courses
            </button>
        </form>
    </div>
</div>
@endsection